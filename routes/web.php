<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InstructorActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Instructor\AIProcessController;
use App\Http\Controllers\Instructor\AIQuestionExtractController;
use App\Http\Controllers\Instructor\AIQuizController;
use App\Http\Controllers\Instructor\AnalyticsController;
use App\Http\Controllers\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Instructor\MaterialController as InstructorMaterialController;
use App\Http\Controllers\Instructor\MaterialDocumentController as InstructorMaterialDocumentController;
use App\Http\Controllers\Instructor\QuestionBankController;
use App\Http\Controllers\Instructor\QuestionController;
use App\Http\Controllers\Instructor\QuizController;
use App\Http\Controllers\Instructor\QuizResultController;
use App\Http\Controllers\Instructor\SectionController as InstructorSectionController;
use App\Http\Controllers\Instructor\SlidebookReviewController;
use App\Http\Controllers\Instructor\SlideController;
use App\Http\Controllers\Public\CourseCatalogController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\MaterialController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\QuizAttemptController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\SlidebookViewerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Guest Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/courses', [CourseCatalogController::class, 'index'])->name('courses.index');
Route::get('/courses/{slug}', [CourseCatalogController::class, 'show'])->name('courses.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Admin Area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::post('/users/{user}/activate-instructor', [InstructorActivationController::class, 'store'])->name('users.activate-instructor');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    });

/*
|--------------------------------------------------------------------------
| Instructor Area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:instructor'])
    ->prefix('instructor')
    ->name('instructor.')
    ->group(function (): void {
        Route::get('/dashboard', [InstructorDashboardController::class, 'index'])->name('dashboard');

        // Course CRUD
        Route::resource('courses', InstructorCourseController::class)->except(['show']);
        Route::post('/courses/{course}/archive', [InstructorCourseController::class, 'archive'])->name('courses.archive');
        Route::post('/courses/{course}/publish', [InstructorCourseController::class, 'publish'])->name('courses.publish');
        Route::get('/courses/{course}/analytics', [AnalyticsController::class, 'show'])->name('courses.analytics');
        Route::post('/courses/{course}/enrollment-code/regenerate', [InstructorCourseController::class, 'regenerateEnrollmentCode'])->name('courses.regenerate-code');

        // Sections
        Route::post('/courses/{course}/sections', [InstructorSectionController::class, 'store'])->name('sections.store');
        Route::delete('/sections/{section}', [InstructorSectionController::class, 'destroy'])->name('sections.destroy');

        // Materials
        Route::get('/sections/{section}/materials/create', [InstructorMaterialController::class, 'create'])->name('materials.create');
        Route::post('/sections/{section}/materials', [InstructorMaterialController::class, 'store'])->name('materials.store');
        Route::get('/materials/{material}/edit', [InstructorMaterialController::class, 'edit'])->name('materials.edit');
        Route::post('/materials/{material}/publish', [InstructorMaterialController::class, 'publish'])->name('materials.publish');
        Route::post('/materials/{material}/unpublish', [InstructorMaterialController::class, 'unpublish'])->name('materials.unpublish');
        Route::put('/materials/{material}', [InstructorMaterialController::class, 'update'])->name('materials.update');
        Route::delete('/materials/{material}', [InstructorMaterialController::class, 'destroy'])->name('materials.destroy');

        // Documents
        Route::post('/materials/{material}/documents', [InstructorMaterialDocumentController::class, 'store'])->name('materials.documents.store');
        Route::delete('/documents/{document}', [InstructorMaterialDocumentController::class, 'destroy'])->name('documents.destroy');

        // AI Slidebook Generation & Review
        Route::post('/materials/{material}/ai/generate-slidebook', [AIProcessController::class, 'generateSlidebook'])->name('materials.ai.slidebook');
        Route::get('/materials/{material}/slidebook/review', [SlidebookReviewController::class, 'show'])->name('materials.slidebook.review');
        Route::post('/slidebooks/{slidebook}/revision', [SlidebookReviewController::class, 'revision'])->name('slidebooks.revision');
        Route::post('/slidebooks/{slidebook}/approve', [SlidebookReviewController::class, 'approve'])->name('slidebooks.approve');
        Route::post('/slidebooks/{slidebook}/publish', [SlidebookReviewController::class, 'publish'])->name('slidebooks.publish');
        Route::get('/slidebooks/{slidebook}/preview', [SlidebookReviewController::class, 'preview'])->name('slidebooks.preview');

        // Slide CRUD & Reordering
        Route::post('/slidebooks/{slidebook}/slides', [SlideController::class, 'store'])->name('slidebooks.slides.store');
        Route::put('/slides/{slide}', [SlideController::class, 'update'])->name('slides.update');
        Route::delete('/slides/{slide}', [SlideController::class, 'destroy'])->name('slides.destroy');
        Route::post('/slidebooks/{slidebook}/slides/reorder', [SlideController::class, 'reorder'])->name('slidebooks.slides.reorder');

        // Question Banks
        Route::resource('question-banks', QuestionBankController::class);

        // Questions
        Route::post('/question-banks/{question_bank}/questions', [QuestionController::class, 'store'])->name('question-banks.questions.store');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('/questions/{question}/approve', [QuestionController::class, 'approve'])->name('questions.approve');

        // AI Question Extraction
        Route::post('/question-banks/{question_bank}/upload-document', [AIQuestionExtractController::class, 'uploadDocument'])->name('question-banks.upload-document');
        Route::post('/question-banks/{question_bank}/extract', [AIQuestionExtractController::class, 'extract'])->name('question-banks.extract');
        Route::get('/question-banks/{question_bank}/review', [AIQuestionExtractController::class, 'review'])->name('question-banks.review');

        // Quizzes
        Route::post('/quizzes/generate-ai', [AIQuizController::class, 'store'])->name('quizzes.generate-ai')->middleware('throttle:5,1');
        Route::resource('quizzes', QuizController::class)->except(['edit', 'update', 'destroy']);
        Route::get('/quizzes/{quiz}/builder', [QuizController::class, 'builder'])->name('quizzes.builder');
        Route::post('/quizzes/{quiz}/sync-questions', [QuizController::class, 'syncQuestions'])->name('quizzes.sync-questions');
        Route::post('/quizzes/{quiz}/unpublish', [QuizController::class, 'unpublish'])->name('quizzes.unpublish');
        Route::post('/quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('quizzes.publish');
        Route::get('/quizzes/{quiz}/results', [QuizResultController::class, 'index'])->name('quizzes.results');
    });

/*
|--------------------------------------------------------------------------
| Shared Authenticated Document Download (Protected by Policy)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')
    ->get('/documents/{document}/download', [InstructorMaterialDocumentController::class, 'download'])
    ->name('documents.download');

/*
|--------------------------------------------------------------------------
| Student Area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function (): void {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/slidebooks/{slidebook}', [SlidebookViewerController::class, 'show'])->name('slidebooks.show');

        // Course Discovery & Lists
        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/explore', [StudentCourseController::class, 'explore'])->name('courses.explore');

        // Enrollment & Progress
        Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');
        Route::get('/courses/{course}/continue', [EnrollmentController::class, 'continue'])->name('courses.continue');
        Route::post('/materials/{material}/complete', [ProgressController::class, 'complete'])->name('materials.complete');
        Route::get('/courses/{course}/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');

        // Quizzes
        Route::get('/quizzes', [StudentQuizController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/{quiz}', [QuizAttemptController::class, 'show'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}/start', [QuizAttemptController::class, 'start'])->name('quizzes.start');
        Route::get('/quizzes/{quiz}/attempt/{attempt}', [QuizAttemptController::class, 'take'])->name('quizzes.take');
        Route::post('/quizzes/{quiz}/attempt/{attempt}', [QuizAttemptController::class, 'submit'])->name('quizzes.submit')->middleware('throttle:10,1');
        Route::get('/quizzes/{quiz}/attempt/{attempt}/result', [QuizAttemptController::class, 'result'])->name('quizzes.result');
    });
