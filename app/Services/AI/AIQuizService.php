<?php

namespace App\Services\AI;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Slidebook;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AIQuizService
{
    public function __construct(private AIContentService $aiContentService) {}

    /** @param array{total_questions: int, difficulty: string, type: string, custom_instructions?: string} $settings */
    public function generate(Slidebook $slidebook, User $teacher, array $settings): Quiz
    {
        $slides = $slidebook->slides()->orderBy('id')->get()->map(fn ($slide): array => [
            'number' => $slide->order,
            'text' => trim(html_entity_decode(strip_tags(implode("\n", [$slide->title, $slide->subtitle, $slide->content, $slide->summary])), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        ])->filter(fn (array $slide): bool => $slide['text'] !== '')->values()->all();

        if ($slides === []) {
            throw ValidationException::withMessages(['slidebook_id' => 'Materi Slidebook belum cukup untuk membuat Quiz.']);
        }

        if (config('ai.provider') === 'mock') {
            throw ValidationException::withMessages(['quiz' => 'Generator Quiz memerlukan provider AI OpenAI atau Gemini yang telah dikonfigurasi.']);
        }

        $generationId = (string) Str::uuid();
        $schema = $this->schema();
        $teacherPrompt = trim((string) ($settings['custom_instructions'] ?? ''));
        $configExtra = trim((string) Config::get('ai.quiz_prompt_extra', ''));

        $systemInstruction = <<<'SYSTEM'
Anda adalah AI pembuat soal pembelajaran.

Gunakan HANYA materi Slidebook yang diberikan.
Jangan membuat fakta yang tidak terdapat pada materi.
Ikuti instruksi guru selama tidak bertentangan dengan isi materi.
Setiap soal harus memiliki jawaban yang jelas berdasarkan materi.
Jangan membuat soal duplikat atau ambigu.
Gunakan bahasa yang sama dengan materi sumber.

Multiple choice memiliki empat pilihan unik; true_false memiliki dua pilihan benar/salah dalam bahasa sumber.
Tepat satu opsi benar, distractor harus masuk akal.
Sertakan explanation, source_slide_number, dan source_quote berupa kutipan persis dari slide yang mendukung jawaban.

Jika instruksi guru meminta topik yang tidak ada di materi Slidebook, kembalikan questions kosong.
Jika materi tidak cukup untuk jumlah soal unik yang diminta, kembalikan questions kosong.
SYSTEM;

        $prompt = $systemInstruction
            .($configExtra !== '' ? "\n\n".$configExtra : '')
            ."\n\n---\n\nINSTRUKSI GURU:\n".$teacherPrompt
            ."\n\n---\n\nKONFIGURASI:\nJumlah soal: ".$settings['total_questions']."\nTipe: ".$settings['type']."\nDifficulty: ".$settings['difficulty'];

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $output = $this->aiContentService->process(
                processType: 'quiz_generation',
                sourceModel: $slidebook,
                systemPrompt: $prompt,
                userContent: json_encode(['task' => 'quiz_generation', 'generation_id' => $generationId, 'attempt' => $attempt, 'settings' => $settings, 'title' => $slidebook->title, 'slides' => $slides], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                schemaDefinition: $schema,
            );

            try {
                $questions = $this->validateOutput($output['parsed_data'], $settings, $slides);
                break;
            } catch (ValidationException $exception) {
                if ($attempt === 2) {
                    throw ValidationException::withMessages(['quiz' => 'Output AI tidak valid atau materi belum cukup. Silakan coba lagi.']);
                }
            }
        }

        return DB::transaction(function () use ($slidebook, $teacher, $settings, $questions, $teacherPrompt): Quiz {
            $course = $slidebook->material->section->course;
            $bank = QuestionBank::create([
                'instructor_id' => $teacher->id,
                'course_id' => $course->id,
                'title' => mb_substr('Quiz AI: '.$slidebook->title, 0, 255),
                'description' => "Sumber Slidebook #{$slidebook->id}: {$slidebook->title}",
                'status' => QuestionBank::STATUS_ACTIVE,
            ]);
            $quiz = $course->quizzes()->create([
                'section_id' => $slidebook->material->section_id,
                'title' => mb_substr('Quiz: '.$slidebook->title, 0, 255),
                'description' => mb_substr('AI Instruksi: '.$teacherPrompt, 0, 65535),
                'total_questions' => $settings['total_questions'],
                'status' => 'draft',
                'published_at' => null,
            ]);
            foreach ($questions as $index => $data) {
                $question = $bank->questions()->create([
                    'question_text' => $data['question_text'],
                    'type' => $data['type'],
                    'difficulty' => $data['difficulty'],
                    'topic' => 'Source: Slide '.$data['source_slide_number'],
                    'explanation' => $data['explanation'],
                    'points' => 10,
                    'order' => $index + 1,
                    'needs_review' => true,
                    'answer_source' => Question::SOURCE_INFERRED,
                    'status' => Question::STATUS_REVIEW,
                ]);
                foreach ($data['options'] as $optionIndex => $option) {
                    $question->options()->create([...$option, 'order' => $optionIndex + 1]);
                }
                $quiz->quizQuestions()->create(['question_id' => $question->id, 'points' => 10, 'order' => $index + 1]);
            }

            return $quiz;
        });
    }

    /**
     * @param  array<string, mixed>  $output
     * @param  array{total_questions: int, difficulty: string, type: string}  $settings
     * @param  list<array{number: int, text: string}>  $slides
     * @return list<array<string, mixed>>
     */
    private function validateOutput(array $output, array $settings, array $slides): array
    {
        Validator::make($output, [
            'questions' => ['required', 'array', 'list', 'size:'.$settings['total_questions']],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:'.$settings['type']],
            'questions.*.difficulty' => ['required', 'in:'.($settings['difficulty'] === 'mixed' ? 'easy,medium,hard' : $settings['difficulty'])],
            'questions.*.explanation' => ['required', 'string'],
            'questions.*.source_slide_number' => ['required', 'integer'],
            'questions.*.source_quote' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'list', 'size:'.($settings['type'] === 'true_false' ? 2 : 4)],
            'questions.*.options.*' => ['required', 'array:option_text,is_correct'],
            'questions.*.options.*.option_text' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean:strict'],
        ])->validate();

        $seen = [];
        foreach ($output['questions'] as $question) {
            $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $question['question_text'])));
            $options = collect($question['options']);
            $source = collect($slides)->firstWhere('number', $question['source_slide_number']);
            if (isset($seen[$text]) || $options->whereStrict('is_correct', true)->count() !== 1
                || $options->map(fn (array $option): string => mb_strtolower(trim($option['option_text'])))->unique()->count() !== $options->count()
                || ! $source || ! str_contains($source['text'], $question['source_quote'])) {
                throw ValidationException::withMessages(['quiz' => 'Output AI tidak valid.']);
            }
            $seen[$text] = true;
        }

        return $output['questions'];
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        $option = ['type' => 'object', 'additionalProperties' => false, 'properties' => [
            'option_text' => ['type' => 'string'], 'is_correct' => ['type' => 'boolean'],
        ], 'required' => ['option_text', 'is_correct']];
        $properties = [
            'question_text' => ['type' => 'string'],
            'type' => ['type' => 'string', 'enum' => ['multiple_choice', 'true_false']],
            'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
            'explanation' => ['type' => 'string'],
            'source_slide_number' => ['type' => 'integer'],
            'source_quote' => ['type' => 'string'],
            'options' => ['type' => 'array', 'items' => $option],
        ];

        return ['title' => 'quiz_generation', 'type' => 'object', 'additionalProperties' => false,
            'properties' => ['questions' => ['type' => 'array', 'items' => [
                'type' => 'object', 'additionalProperties' => false, 'properties' => $properties, 'required' => array_keys($properties),
            ]]], 'required' => ['questions']];
    }
}
