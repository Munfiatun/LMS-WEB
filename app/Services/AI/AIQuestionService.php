<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
   - options: array berisi 4 pilihan jawaban, masing-masing dengan `option_text`, `is_correct` (tepat 1 true), dan `order` (1, 2, 3, 4).
PROMPT;

        $schemaDefinition = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'order' => ['type' => 'integer'],
                            'question_text' => ['type' => 'string'],
                            'topic' => ['type' => 'string'],
                            'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
                            'explanation' => ['type' => 'string'],
                            'points' => ['type' => 'integer'],
                            'answer_source' => ['type' => 'string', 'enum' => ['explicit', 'inferred', 'manual']],
                            'needs_review' => ['type' => 'boolean'],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'order' => ['type' => 'integer'],
                                        'option_text' => ['type' => 'string'],
                                        'is_correct' => ['type' => 'boolean'],
                                    ],
                                    'required' => ['order', 'option_text', 'is_correct'],
                                ],
                            ],
                        ],
                        'required' => ['order', 'question_text', 'options'],
                    ],
                ],
            ],
            'required' => ['questions'],
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

        return DB::transaction(function () use ($bank, $parsedQuestions): Collection {
            $createdQuestions = collect();
            $maxOrder = (int) $bank->questions()->max('order');

            foreach ($parsedQuestions as $qData) {
                $maxOrder++;
                $needsReview = (bool) ($qData['needs_review'] ?? false);
                $status = $needsReview ? Question::STATUS_REVIEW : Question::STATUS_APPROVED;

                $question = Question::create([
                    'question_bank_id' => $bank->id,
                    'question_text' => $qData['question_text'],
                    'type' => Question::TYPE_MULTIPLE_CHOICE,
                    'topic' => $qData['topic'] ?? 'Umum',
                    'difficulty' => $qData['difficulty'] ?? Question::DIFFICULTY_MEDIUM,
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => (int) ($qData['points'] ?? 10),
                    'order' => $maxOrder,
                    'needs_review' => $needsReview,
                    'answer_source' => $qData['answer_source'] ?? ($needsReview ? Question::SOURCE_INFERRED : Question::SOURCE_EXPLICIT),
                    'status' => $status,
                ]);

                $optionsData = $qData['options'] ?? [];
                foreach ($optionsData as $optIdx => $opt) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $opt['option_text'],
                        'is_correct' => (bool) ($opt['is_correct'] ?? false),
                        'order' => (int) ($opt['order'] ?? ($optIdx + 1)),
                    ]);
                }

                $createdQuestions->push($question);
            }

            return $createdQuestions;
        });
    }
}
