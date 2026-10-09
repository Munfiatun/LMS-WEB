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
use Illuminate\Database\Seeder;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command->warn('LmsDemoSeeder only runs in local/testing environments.');

            return;
        }

        if (! config('auth.demo_user_password')) {
            $this->command->warn('DEMO_USER_PASSWORD missing. Skipping LmsDemoSeeder.');

            return;
        }

        $instructor = User::where('email', 'instructor@example.com')->first();
        $student = User::where('email', 'student@example.com')->first();

        if (! $instructor || ! $student) {
            $this->command->warn('Demo users missing. Skipping LmsDemoSeeder.');

            return;
        }

        $category = Category::firstOrCreate(['name' => 'Web Development', 'slug' => 'web-development']);

        // 1. Showcase Course
        $course = Course::updateOrCreate(
            ['slug' => 'lms-feature-showcase'],
            [
                'instructor_id' => $instructor->id,
                'category_id' => $category->id,
                'title' => 'LMS Feature Showcase',
                'description' => 'A comprehensive course demonstrating all LMS features including Slidebook, layouts, and quizzes.',
                'status' => Course::STATUS_PUBLISHED,
                'published_at' => now()->subDays(5),
            ]
        );

        if (! $course->enrollment_code) {
            $course->update(['enrollment_code' => $course->generateEnrollmentCode()]);
        }

        // 2. Active Enrollment
        CourseEnrollment::firstOrCreate(
            ['course_id' => $course->id, 'student_id' => $student->id],
            ['enrolled_at' => now()->subDays(4), 'status' => 'active']
        );

        // 3. Section
        $section = CourseSection::updateOrCreate(
            ['course_id' => $course->id, 'title' => 'Module 1: Slidebook & Theme Engine'],
            ['description' => 'Showcasing the 18 Slidebook layouts', 'order' => 1, 'status' => 'active']
        );

        // 4. Draft Material
        LearningMaterial::updateOrCreate(
            ['section_id' => $section->id, 'title' => 'Draft Material (Not Visible)'],
            [
                'slug' => 'draft-material',
                'description' => 'Students should not see this.',
                'content' => '<p>Draft</p>',
                'duration_minutes' => 5,
                'order' => 1,
                'status' => LearningMaterial::STATUS_DRAFT,
            ]
        );

        // 5. Published Material for Slidebook
        $slidebookMaterial = LearningMaterial::updateOrCreate(
            ['section_id' => $section->id, 'title' => 'Slidebook Layout Gallery'],
            [
                'slug' => 'slidebook-layout-gallery',
                'description' => 'A complete gallery of all 18 supported layouts.',
                'content' => '<p>Explore the layouts below.</p>',
                'duration_minutes' => 30,
                'order' => 2,
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'published_at' => now()->subDays(3),
            ]
        );

        MaterialProgress::firstOrCreate(
            ['student_id' => $student->id, 'learning_material_id' => $slidebookMaterial->id],
            ['status' => 'completed', 'completed_at' => now()->subDays(2), 'last_accessed_at' => now()->subDays(2)]
        );

        // 6. Slidebooks (V1 Published, V2 Draft)
        $this->createShowcaseSlidebook($slidebookMaterial, $instructor);

        // 7. Quiz Section
        $quizSection = CourseSection::updateOrCreate(
            ['course_id' => $course->id, 'title' => 'Module 2: Assessment'],
            ['description' => 'Demonstrating Quiz engine', 'order' => 2, 'status' => 'active']
        );

        // 8. Quiz
        $quiz = Quiz::updateOrCreate(
            ['section_id' => $quizSection->id, 'title' => 'Web Fundamentals Quiz'],
            [
                'description' => 'Test your knowledge on web development.',
                'duration_minutes' => 15,
                'passing_score' => 70,
                'max_attempts' => 3,
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ]
        );

        $this->seedQuizQuestions($quiz);

        // 9. Quiz Attempt (Progress Demo)
        $attempt = QuizAttempt::firstOrCreate(
            ['quiz_id' => $quiz->id, 'student_id' => $student->id, 'status' => 'completed'],
            [
                'started_at' => now()->subDays(1),
                'completed_at' => now()->subDays(1)->addMinutes(10),
                'score' => 100,
                'passed' => true,
                'attempt_number' => 1,
            ]
        );

        $this->command->info('LmsDemoSeeder completed successfully.');
    }

    private function createShowcaseSlidebook(LearningMaterial $material, User $instructor): void
    {
        // Delete existing to recreate cleanly
        $material->slidebooks()->delete();

        // V1: Published
        $v1 = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Slidebook Layout Gallery',
            'version' => 1,
            'status' => Slidebook::STATUS_PUBLISHED,
            'created_by' => $instructor->id,
            'published_at' => now()->subDays(3),
            'design_settings' => ['preset' => 'indigo-dark'],
        ]);
        $this->seed18Layouts($v1);

        // V2: Draft
        $v2 = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Slidebook Layout Gallery (Draft)',
            'version' => 2,
            'status' => Slidebook::STATUS_DRAFT,
            'created_by' => $instructor->id,
            'design_settings' => ['preset' => 'modern-tech'],
        ]);
        $this->seed18Layouts($v2);
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
            'reading' => '18. Reading (Fallback)',
        ];

        $order = 1;
        foreach ($layouts as $layout => $title) {
            Slide::create([
                'slidebook_id' => $slidebook->id,
                'title' => $title,
                'content' => $this->getDummyContentForLayout($layout),
                'order' => $order++,
                'layout' => $layout,
            ]);
        }
    }

    private function getDummyContentForLayout(string $layout): string
    {
        return match ($layout) {
            'code' => "<pre><code>function helloWorld() {\n    console.log('Hello, world!');\n}</code></pre><p>This is a code block example.</p>",
            'comparison' => '<ul><li><strong>Pros:</strong> Fast, Reliable</li><li><strong>Cons:</strong> Steep learning curve</li></ul>',
            'process' => '<ul><li>Step 1: Planning</li><li>Step 2: Development</li><li>Step 3: Testing</li></ul>',
            default => "<p>This slide demonstrates the <strong>{$layout}</strong> layout visually.</p>"
        };
    }

    private function seedQuizQuestions(Quiz $quiz): void
    {
        $bank = QuestionBank::firstOrCreate(
            ['name' => 'Web Basics'],
            ['description' => 'Basic web questions', 'instructor_id' => $quiz->section->course->instructor_id]
        );

        $q = Question::firstOrCreate([
            'question_bank_id' => $bank->id,
            'question_text' => 'What does HTML stand for?',
            'question_type' => 'multiple_choice',
            'points' => 10,
        ]);

        if ($q->options()->count() === 0) {
            $q->options()->create(['option_text' => 'Hyper Text Markup Language', 'is_correct' => true]);
            $q->options()->create(['option_text' => 'High Tech Modern Language', 'is_correct' => false]);
            $q->options()->create(['option_text' => 'Hyperlink and Text Markup Language', 'is_correct' => false]);
        }

        $quiz->questions()->syncWithoutDetaching([$q->id => ['order' => 1]]);
    }
}
