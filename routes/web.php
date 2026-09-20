<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Instructor\AIProcessController;
use App\Http\Controllers\Instructor\AIQuestionExtractController;
use App\Http\Controllers\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Instructor\MaterialController as InstructorMaterialController;
use App\Http\Controllers\Instructor\MaterialDocumentController as InstructorMaterialDocumentController;
use App\Http\Controllers\Instructor\QuestionBankController;
use App\Http\Controllers\Instructor\QuestionController;
use App\Http\Controllers\Instructor\SectionController as InstructorSectionController;
use App\Http\Controllers\Instructor\SlidebookReviewController;
use App\Http\Controllers\Instructor\SlideController;
use App\Http\Controllers\Public\CourseCatalogController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
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
        Route::post('/courses/{course}/publish', [InstructorCourseController::class, 'publish'])->name('courses.publish');
        Route::get('/courses/{course}/analytics', [\App\Http\Controllers\Instructor\AnalyticsController::class, 'show'])->name('courses.analytics');

        // Sections
        Route::post('/courses/{course}/sections', [InstructorSectionController::class, 'store'])->name('sections.store');
        Route::delete('/sections/{section}', [InstructorSectionController::class, 'destroy'])->name('sections.destroy');

        // Materials
        Route::get('/sections/{section}/materials/create', [InstructorMaterialController::class, 'create'])->name('materials.create');
        Route::post('/sections/{section}/materials', [InstructorMaterialController::class, 'store'])->name('materials.store');
        Route::get('/materials/{material}/edit', [InstructorMaterialController::class, 'edit'])->name('materials.edit');
        Route::put('/materials/{material}', [InstructorMaterialController::class, 'update'])->name('materials.update');
        Route::delete('/materials/{material}', [InstructorMaterialController::class, 'destroy'])->name('materials.destroy');

        // Documents
        Route::post('/materials/{material}/documents', [InstructorMaterialDocumentController::class, 'store'])->name('materials.documents.store');
        Route::delete('/documents/{document}', [InstructorMaterialDocumentController::class, 'destroy'])->name('documents.destroy');

        // AI Slidebook Generation & Review
        Route::post('/materials/{material}/ai/generate-slidebook', [AIProcessController::class, 'generateSlidebook'])->name('materials.ai.slidebook');
        Route::get('/materials/{material}/slidebook/review', [SlidebookReviewController::class, 'show'])->name('materials.slidebook.review');
        Route::post('/slidebooks/{slidebook}/approve', [SlidebookReviewController::class, 'approve'])->name('slidebooks.approve');
        Route::post('/slidebooks/{slidebook}/publish', [SlidebookReviewController::class, 'publish'])->name('slidebooks.publish');

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
        Route::resource('quizzes', \App\Http\Controllers\Instructor\QuizController::class)->except(['edit', 'update', 'destroy']);
        Route::get('/quizzes/{quiz}/builder', [\App\Http\Controllers\Instructor\QuizController::class, 'builder'])->name('quizzes.builder');
        Route::post('/quizzes/{quiz}/sync-questions', [\App\Http\Controllers\Instructor\QuizController::class, 'syncQuestions'])->name('quizzes.sync-questions');
        Route::post('/quizzes/{quiz}/publish', [\App\Http\Controllers\Instructor\QuizController::class, 'publish'])->name('quizzes.publish');
        Route::get('/quizzes/{quiz}/results', [\App\Http\Controllers\Instructor\QuizResultController::class, 'index'])->name('quizzes.results');
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
        
        // Enrollment & Progress
        Route::post('/courses/{course}/enroll', [\App\Http\Controllers\Student\EnrollmentController::class, 'store'])->name('courses.enroll');
        Route::get('/courses/{course}/continue', [\App\Http\Controllers\Student\EnrollmentController::class, 'continue'])->name('courses.continue');
        Route::post('/materials/{material}/complete', [\App\Http\Controllers\Student\ProgressController::class, 'complete'])->name('materials.complete');
        Route::get('/courses/{course}/materials/{material}', [\App\Http\Controllers\Student\MaterialController::class, 'show'])->name('materials.show');
        
        // Quizzes
        Route::get('/quizzes/{quiz}', [\App\Http\Controllers\Student\QuizAttemptController::class, 'show'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}/start', [\App\Http\Controllers\Student\QuizAttemptController::class, 'start'])->name('quizzes.start');
        Route::get('/quizzes/{quiz}/attempt/{attempt}', [\App\Http\Controllers\Student\QuizAttemptController::class, 'take'])->name('quizzes.take');
        Route::post('/quizzes/{quiz}/attempt/{attempt}', [\App\Http\Controllers\Student\QuizAttemptController::class, 'submit'])->name('quizzes.submit')->middleware('throttle:10,1');
        Route::get('/quizzes/{quiz}/attempt/{attempt}/result', [\App\Http\Controllers\Student\QuizAttemptController::class, 'result'])->name('quizzes.result');
    });
