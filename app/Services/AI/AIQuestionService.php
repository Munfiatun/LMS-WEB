<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AIQuestionService
{
    public function __construct(
        protected AIContentService $aiContentService
    ) {}

    /**
     * Extract structured multiple-choice questions from document text into the question bank.
     *
     * @return Collection<int, Question>
     */
    public function extractQuestions(QuestionBank $bank, string $documentText): Collection
    {
        if (trim($documentText) === '') {
            throw new RuntimeException('Teks dokumen soal kosong atau tidak dapat diekstraksi.');
        }

        $systemPrompt = <<<'PROMPT'
Anda adalah asisten evaluasi pembelajaran profesional. Tugas Anda adalah menganalisis dokumen naskah soal dan mengekstrak butir-butir pertanyaan pilihan ganda (Multiple Choice) ke dalam format JSON terstruktur.

Aturan Kritis (Answer Key Safety):
1. Periksa apakah kunci jawaban tertera secara eksplisit pada naskah soal (misal: "Kunci: A", tanda bintang pada pilihan, atau daftar kunci di akhir).
2. Jika kunci jawaban eksplisit ditemukan:
   - Set `answer_source: "explicit"`
   - Set `needs_review: false`
3. Jika kunci jawaban TIDAK TERTERA SECARA EKSPLISIT:
   - Jangan menebak secara diam-diam!
   - Tetapkan pilihan yang paling logis sebagai kunci jawaban, NAMUN WAJIB set:
     `answer_source: "inferred"`
     `needs_review: true`
4. Setiap butir soal harus memuat:
   - order: nomor urut integer
   - question_text: teks pertanyaan lengkap
   - topic: topik bahasan
   - difficulty: "easy", "medium", atau "hard"
   - explanation: pembahasan jawaban
   - points: bobot nilai (default 10)
   - answer_source: "explicit" atau "inferred"
   - needs_review: boolean
   - options: array berisi tepat 4 pilihan jawaban, masing-masing dengan `option_text`, `is_correct` (tepat 1 true), dan `order` (1, 2, 3, 4).
PROMPT;

        $optionProperties = [
            'order' => ['type' => 'integer'],
            'option_text' => ['type' => 'string'],
            'is_correct' => ['type' => 'boolean'],
        ];
        $questionProperties = [
            'order' => ['type' => 'integer'],
            'question_text' => ['type' => 'string'],
            'topic' => ['type' => 'string'],
            'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
            'explanation' => ['type' => 'string'],
            'points' => ['type' => 'integer'],
            'answer_source' => ['type' => 'string', 'enum' => ['explicit', 'inferred']],
            'needs_review' => ['type' => 'boolean'],
            'options' => [
                'type' => 'array',
                'minItems' => 4,
                'maxItems' => 4,
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => $optionProperties,
                    'required' => array_keys($optionProperties),
                ],
            ],
        ];
        $schemaDefinition = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'title' => ['type' => 'string'],
                'questions' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => $questionProperties,
                        'required' => array_keys($questionProperties),
                    ],
                ],
            ],
            'required' => ['title', 'questions'],
        ];

        $aiOutput = $this->aiContentService->process(
            processType: AIProcessingLog::PROCESS_QUESTION_EXTRACTION,
            sourceModel: $bank,
            systemPrompt: $systemPrompt,
            userContent: $documentText,
            schemaDefinition: $schemaDefinition
        );

        $parsedQuestions = $aiOutput['parsed_data']['questions'] ?? [];

        if (empty($parsedQuestions)) {
            throw new RuntimeException('AI tidak menemukan butir soal yang valid di dalam teks dokumen.');
        }

        $parsedQuestions = $this->validateExtractedQuestions($parsedQuestions);

        return DB::transaction(function () use ($bank, $parsedQuestions): Collection {
            $createdQuestions = collect();
            $maxOrder = (int) $bank->questions()->max('order');

            foreach ($parsedQuestions as $qData) {
                $maxOrder++;
                $needsReview = $qData['needs_review'];
                $status = $needsReview ? Question::STATUS_REVIEW : Question::STATUS_APPROVED;

                $question = Question::create([
                    'question_bank_id' => $bank->id,
                    'question_text' => trim($qData['question_text']),
                    'type' => Question::TYPE_MULTIPLE_CHOICE,
                    'topic' => trim($qData['topic']),
                    'difficulty' => $qData['difficulty'],
                    'explanation' => trim($qData['explanation']),
                    'points' => (int) $qData['points'],
                    'order' => $maxOrder,
                    'needs_review' => $needsReview,
                    'answer_source' => $qData['answer_source'],
                    'status' => $status,
                ]);

                foreach ($qData['options'] as $optIdx => $opt) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => trim($opt['option_text']),
                        'is_correct' => $opt['is_correct'],
                        'order' => $optIdx + 1,
                    ]);
                }

                $createdQuestions->push($question);
            }

            return $createdQuestions;
        });
    }

    /**
     * Apply a second validation boundary after provider/schema parsing so malformed or
     * semantically unsafe answer keys can never be persisted silently.
     *
     * @param  array<int, mixed>  $questions
     * @return list<array<string, mixed>>
     */
    private function validateExtractedQuestions(array $questions): array
    {
        Validator::make(['questions' => $questions], [
            'questions' => ['required', 'array', 'list', 'min:1'],
            'questions.*' => ['required', 'array'],
            'questions.*.order' => ['required', 'integer', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.topic' => ['required', 'string'],
            'questions.*.difficulty' => ['required', 'in:easy,medium,hard'],
            'questions.*.explanation' => ['required', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.answer_source' => ['required', 'in:explicit,inferred'],
            'questions.*.needs_review' => ['required', 'boolean'],
            'questions.*.options' => ['required', 'array', 'list', 'size:4'],
            'questions.*.options.*' => ['required', 'array'],
            'questions.*.options.*.order' => ['required', 'integer', 'min:1', 'max:4'],
            'questions.*.options.*.option_text' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ])->validate();

        $seenQuestions = [];

        foreach ($questions as $index => $question) {
            if (! is_bool($question['needs_review'])) {
                throw ValidationException::withMessages([
                    "questions.{$index}.needs_review" => 'Status review dari AI harus berupa boolean.',
                ]);
            }

            $normalizedQuestion = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $question['question_text']) ?? $question['question_text']));
            if ($normalizedQuestion === '' || isset($seenQuestions[$normalizedQuestion])) {
                throw ValidationException::withMessages([
                    "questions.{$index}.question_text" => 'Pertanyaan AI kosong atau duplikat.',
                ]);
            }
            $seenQuestions[$normalizedQuestion] = true;

            $normalizedOptions = collect($question['options'])->map(function (array $option, int $optionIndex) use ($index): string {
                if (! is_bool($option['is_correct'])) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.options.{$optionIndex}.is_correct" => 'Penanda jawaban benar dari AI harus berupa boolean.',
                    ]);
                }

                return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $option['option_text']) ?? $option['option_text']));
            });

            if ($normalizedOptions->contains('') || $normalizedOptions->unique()->count() !== 4) {
                throw ValidationException::withMessages([
                    "questions.{$index}.options" => 'Pilihan jawaban AI harus berisi empat pilihan unik dan tidak kosong.',
                ]);
            }

            $optionOrders = collect($question['options'])->pluck('order')->sort()->values()->all();
            if ($optionOrders !== [1, 2, 3, 4]) {
                throw ValidationException::withMessages([
                    "questions.{$index}.options" => 'Urutan pilihan jawaban AI harus 1 sampai 4 tanpa duplikasi.',
                ]);
            }

            $correctCount = collect($question['options'])->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                throw ValidationException::withMessages([
                    "questions.{$index}.options" => 'Setiap soal AI harus memiliki tepat satu jawaban benar.',
                ]);
            }

            $mustReview = $question['answer_source'] === Question::SOURCE_INFERRED;
            if ($question['needs_review'] !== $mustReview) {
                throw ValidationException::withMessages([
                    "questions.{$index}.needs_review" => $mustReview
                        ? 'Kunci jawaban hasil inferensi AI wajib melalui review guru.'
                        : 'Kunci jawaban eksplisit tidak boleh ditandai sebagai hasil inferensi.',
                ]);
            }
        }

        return array_values($questions);
    }
}
