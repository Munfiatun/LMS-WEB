<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Slide;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\Quiz\QuizService;
use App\Services\SlidebookService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('LmsDemoSeeder only runs in local/testing environments.');
            return;
        }

        if (! config('auth.demo_user_password')) {
            $this->command?->warn('DEMO_USER_PASSWORD missing. Skipping LmsDemoSeeder.');
            return;
        }

        $instructor = User::where('email', 'instructor@example.com')->first();
        $student = User::where('email', 'student@example.com')->first();

        if (! $instructor || ! $student) {
            $this->command?->warn('Demo users missing. Skipping LmsDemoSeeder.');
            return;
        }

        DB::transaction(function () use ($instructor, $student): void {
            $category = Category::firstOrCreate(
                ['slug' => 'web-development'],
                ['name' => 'Web Development']
            );

            $course = Course::updateOrCreate(
                ['slug' => 'lms-feature-showcase'],
                [
                    'instructor_id' => $instructor->id,
                    'category_id' => $category->id,
                    'title' => 'LMS Feature Showcase',
                    'description' => 'Course demo untuk menguji alur materi, Slidebook, quiz, enrollment, dan progress secara end-to-end.',
                    'status' => Course::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(5),
                ]
            );

            if (! $course->enrollment_code) {
                $course->generateEnrollmentCode();
                $course->refresh();
            }

            $enrollment = CourseEnrollment::updateOrCreate(
                ['course_id' => $course->id, 'student_id' => $student->id],
                ['status' => 'active', 'progress_percentage' => 0, 'completed_at' => null]
            );

            $section = CourseSection::updateOrCreate(
                ['course_id' => $course->id, 'title' => 'Module 1: Slidebook & Theme Engine'],
                ['description' => 'Showcase sistem Slidebook dan seluruh layout', 'order' => 1, 'status' => 'active']
            );

            LearningMaterial::updateOrCreate(
                ['section_id' => $section->id, 'slug' => 'draft-material'],
                [
                    'title' => 'Draft Material (Not Visible)',
                    'description' => 'Materi ini sengaja draft untuk memastikan siswa tidak dapat melihat konten belum terbit.',
                    'content' => '<p>Draft content for authorization testing.</p>',
                    'duration_minutes' => 5,
                    'order' => 1,
                    'status' => LearningMaterial::STATUS_DRAFT,
                    'published_at' => null,
                ]
            );

            $slidebookMaterial = LearningMaterial::updateOrCreate(
                ['section_id' => $section->id, 'slug' => 'slidebook-layout-gallery'],
                [
                    'title' => 'Slidebook Layout Gallery',
                    'description' => 'Galeri seluruh layout Slidebook untuk pengujian tema dan komposisi visual.',
                    'content' => '<p>Materi showcase untuk menguji renderer Slidebook dari sisi guru dan siswa.</p>',
                    'duration_minutes' => 30,
                    'order' => 2,
                    'status' => LearningMaterial::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(3),
                ]
            );

            $readingMaterial = LearningMaterial::updateOrCreate(
                ['section_id' => $section->id, 'slug' => 'web-request-lifecycle'],
                [
                    'title' => 'Web Request Lifecycle',
                    'description' => 'Materi published yang sengaja belum diselesaikan siswa agar progress demo tidak selalu 100%.',
                    'content' => '<p>Browser melakukan request, router memilih endpoint, controller memproses input, dan server mengembalikan response.</p>',
                    'duration_minutes' => 15,
                    'order' => 3,
                    'status' => LearningMaterial::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(2),
                ]
            );

            MaterialProgress::updateOrCreate(
                ['student_id' => $student->id, 'learning_material_id' => $slidebookMaterial->id],
                ['status' => 'completed', 'completed_at' => now()->subDays(2)]
            );
            MaterialProgress::where('student_id', $student->id)
                ->where('learning_material_id', $readingMaterial->id)
                ->delete();

            $this->createShowcaseSlidebooks($slidebookMaterial, $instructor);

            $quizSection = CourseSection::updateOrCreate(
                ['course_id' => $course->id, 'title' => 'Module 2: Assessment'],
                ['description' => 'Quiz demo yang valid menurut QuizService', 'order' => 2, 'status' => 'active']
            );

            $quiz = $this->createValidDemoQuiz($course, $quizSection, $instructor);

            QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $student->id)->delete();
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'started_at' => now()->subDay()->subMinutes(8),
                'expires_at' => now()->subDay()->addMinutes(7),
                'submitted_at' => now()->subDay(),
                'status' => 'submitted',
                'score' => 30,
                'percentage' => 100,
                'correct_count' => 3,
                'wrong_count' => 0,
                'duration_seconds' => 480,
            ]);

            app(EnrollmentService::class)->updateProgress($course, $student);
            $enrollment->refresh();
        });

        $this->command?->info('LmsDemoSeeder completed successfully.');
    }

    private function createShowcaseSlidebooks(LearningMaterial $material, User $instructor): void
    {
        $material->slidebooks()->delete();

        $v1 = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Slidebook Layout Gallery',
            'version' => 1,
            'status' => Slidebook::STATUS_DRAFT,
            'created_by' => $instructor->id,
            'design_settings' => ['preset' => 'indigo-dark'],
        ]);

        $this->seed18Layouts($v1);

        $service = app(SlidebookService::class);
        $service->approve($v1, $instructor);
        $service->publish($v1);
        $v1->refresh();

        $v2 = $service->createRevision($v1, $instructor);
        $v2->update([
            'title' => 'Slidebook Layout Gallery (Working Revision)',
            'design_settings' => ['preset' => 'modern-tech'],
        ]);

        $firstSlide = $v2->slides()->orderBy('order')->first();
        if ($firstSlide) {
            $service->updateSlide($firstSlide, [
                'title' => '1. Cover Layout — V2 Review',
                'content' => $firstSlide->content,
                'layout' => 'cover',
            ]);
        }
    }

    private function seed18Layouts(Slidebook $slidebook): void
    {
        $layouts = [
            'cover' => '1. Cover Layout',
            'section-divider' => '2. Section Divider',
            'concept' => '3. Concept Layout',
            'definition' => '4. Definition',
            'key-points' => '5. Key Points',
            'process' => '6. Process Steps',
            'timeline' => '7. Timeline',
            'comparison' => '8. Comparison',
            'example' => '9. Example',
            'case-study' => '10. Case Study',
            'image-focus' => '11. Image Focus',
            'quote' => '12. Quote',
            'code' => '13. Code Block',
            'diagram' => '14. Diagram',
            'checkpoint' => '15. Checkpoint',
            'summary' => '16. Summary',
            'closing' => '17. Closing',
            'reading' => '18. Reading',
        ];

        $order = 1;
        foreach ($layouts as $layout => $title) {
            Slide::create([
                'slidebook_id' => $slidebook->id,
                'title' => $title,
                'content' => $this->contentForLayout($layout),
                'order' => $order++,
                'layout' => $layout,
                'source_reference' => ['demo' => true],
                'needs_review' => false,
                'status' => 'active',
            ]);
        }
    }

    private function contentForLayout(string $layout): string
    {
        return match ($layout) {
            'cover' => '<h2>Dasar Pengembangan Web Modern</h2><p>Memahami arsitektur client-server, HTTP, Laravel, dan alur request.</p>',
            'section-divider' => '<h2>Bagian 1 — Arsitektur Web</h2><p>Dari browser menuju aplikasi dan database.</p>',
            'concept' => '<h3>Client–Server Architecture</h3><p>Client mengirim request dan server memprosesnya sebelum mengirim response.</p>',
            'definition' => '<p><strong>HTTP Request</strong> adalah pesan yang dikirim client kepada server untuk meminta resource atau menjalankan suatu aksi.</p>',
            'key-points' => '<ul><li>HTTP bersifat request-response.</li><li>Method menjelaskan tujuan request.</li><li>Status code menjelaskan hasil response.</li><li>Headers membawa metadata.</li></ul>',
            'process' => '<ol><li>Browser meminta URL.</li><li>DNS menemukan alamat server.</li><li>Router memilih endpoint.</li><li>Controller memproses request.</li><li>Server mengirim response.</li></ol>',
            'timeline' => '<ul><li>1991 — HTML awal</li><li>1996 — CSS</li><li>1995–2009 — JavaScript dan AJAX berkembang</li><li>2014 — HTML5 menjadi standar</li><li>Sekarang — Web API dan aplikasi progresif</li></ul>',
            'comparison' => '<ul><li><strong>Client-side rendering:</strong> interaktif dan kaya UI.</li><li><strong>Server-side rendering:</strong> HTML disiapkan server dan baik untuk initial render.</li></ul>',
            'example' => '<p>Contoh route Laravel:</p><pre><code>Route::get("/courses", [CourseController::class, "index"]);</code></pre>',
            'case-study' => '<p><strong>Kasus:</strong> halaman LMS lambat saat membuka materi. Analisis dimulai dari query database, ukuran asset, cache, dan latency jaringan.</p>',
            'image-focus' => '<p><strong>Visual focus fallback:</strong> diagram konseptual Browser → Web Server → Application → Database. Tidak bergantung pada gambar eksternal.</p>',
            'quote' => '<blockquote>Desain sistem yang baik membuat alur kompleks terasa sederhana bagi pengguna.</blockquote>',
            'code' => '<pre><code>public function index(): View\n{\n    return view("courses.index");\n}</code></pre><p>Controller mengembalikan view kepada browser.</p>',
            'diagram' => '<p>Browser → Router → Middleware → Controller → Service → Database → Response</p>',
            'checkpoint' => '<p><strong>Checkpoint:</strong> Di bagian mana authorization sebaiknya dilakukan sebelum data sensitif ditampilkan?</p>',
            'summary' => '<ul><li>Browser bertindak sebagai client.</li><li>HTTP mengatur komunikasi.</li><li>Laravel memetakan request ke controller.</li><li>Service menjaga business logic.</li></ul>',
            'closing' => '<h2>Web adalah rangkaian alur yang terstruktur</h2><p>Pahami alur request terlebih dahulu sebelum mengoptimalkan implementasi.</p>',
            'reading' => '<p>Pada aplikasi web modern, satu interaksi pengguna dapat melewati routing, middleware, validasi, service, database, dan rendering. Memisahkan tanggung jawab setiap lapisan membuat sistem lebih mudah diuji, dipelihara, dan dikembangkan.</p>',
            default => '<p>Demo content.</p>',
        };
    }

    private function createValidDemoQuiz(Course $course, CourseSection $section, User $instructor): Quiz
    {
        $existing = Quiz::where('course_id', $course->id)->where('title', 'Web Fundamentals Quiz')->first();
        if ($existing) {
            $existing->attempts()->delete();
            $existing->quizQuestions()->delete();
            $existing->delete();
        }

        $bank = QuestionBank::updateOrCreate(
            ['instructor_id' => $instructor->id, 'course_id' => $course->id, 'title' => 'Web Basics'],
            ['description' => 'Bank soal demo Web Fundamentals', 'status' => QuestionBank::STATUS_ACTIVE]
        );
        $bank->questions()->delete();

        $questionData = [
            ['What does HTML stand for?', ['Hyper Text Markup Language', 'High Tech Modern Language', 'Hyperlink Text Machine Language'], 0],
            ['Which HTTP method is commonly used to retrieve a resource?', ['GET', 'POST', 'DELETE'], 0],
            ['In Laravel, which component commonly receives a routed request and coordinates the response?', ['Controller', 'Migration', 'Seeder'], 0],
        ];

        $questions = [];
        foreach ($questionData as $index => [$text, $options, $correctIndex]) {
            $question = Question::create([
                'question_bank_id' => $bank->id,
                'question_text' => $text,
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'topic' => 'Web Fundamentals',
                'difficulty' => Question::DIFFICULTY_EASY,
                'explanation' => 'Demo question for LMS showcase.',
                'points' => 10,
                'order' => $index + 1,
                'needs_review' => false,
                'answer_source' => Question::SOURCE_MANUAL,
                'status' => Question::STATUS_APPROVED,
            ]);

            foreach ($options as $optionIndex => $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => $optionIndex === $correctIndex,
                    'order' => $optionIndex + 1,
                ]);
            }
            $questions[] = $question;
        }

        $quizService = app(QuizService::class);
        $quiz = $quizService->createQuiz($course, [
            'section_id' => $section->id,
            'title' => 'Web Fundamentals Quiz',
            'description' => 'Quiz valid untuk menguji engine assessment.',
            'instructions' => 'Pilih satu jawaban yang paling tepat.',
            'passing_score' => 70,
            'duration_minutes' => 15,
            'total_questions' => 3,
            'randomize_questions' => false,
            'randomize_options' => false,
            'max_attempts' => 3,
        ]);

        $quizService->syncQuestions($quiz, collect($questions)->values()->map(fn (Question $question, int $index) => [
            'id' => $question->id,
            'points' => 10,
            'order' => $index + 1,
        ])->all());

        $quiz->refresh();
        $quizService->publishQuiz($quiz);
        return $quiz->fresh();
    }
}
