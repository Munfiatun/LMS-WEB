<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Services\Course\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::where('instructor_id', $request->user()->id)
            ->with('category')
            ->withCount(['sections', 'materials'])
            ->latest()
            ->paginate(10);

        return view('instructor.courses.index', compact('courses'));
    }

    public function create(): View
    {
        Gate::authorize('create', Course::class);

        $categories = Category::orderBy('name')->get();

        return view('instructor.courses.create', compact('categories'));
    }

    public function store(StoreCourseRequest $request, CourseService $service): RedirectResponse
    {
        $course = $service->createCourse($request->user(), $request->validated());

        return redirect()->route('instructor.courses.edit', $course)->with('success', 'Kursus berhasil dibuat. Silakan tambahkan chapter dan materi pembelajaran.');
    }

    public function edit(Course $course): View
    {
        Gate::authorize('update', $course);

        $course->load([
            'category',
            'sections.materials.documents',
        ]);

        $categories = Category::orderBy('name')->get();

        return view('instructor.courses.edit', compact('course', 'categories'));
    }

    public function update(UpdateCourseRequest $request, Course $course, CourseService $service): RedirectResponse
    {
        $service->updateCourse($course, $request->validated());

        return back()->with('success', 'Informasi kursus berhasil diperbarui.');
    }

    public function destroy(Course $course, CourseService $service): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $service->deleteCourse($course);

        return redirect()->route('instructor.courses.index')->with('success', 'Kursus berhasil dihapus.');
    }

    public function publish(Course $course, CourseService $service): RedirectResponse
    {
        Gate::authorize('update', $course);

        if ($course->materials()->count() === 0) {
            return back()->with('error', 'Kursus harus memiliki minimal satu materi pembelajaran sebelum dipublikasikan.');
        }

        $service->publishCourse($course);

        return back()->with('success', 'Kursus berhasil dipublikasikan dan kini dapat diakses oleh siswa.');
    }

    public function regenerateEnrollmentCode(Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->generateEnrollmentCode();

        return back()->with('success', 'Token kelas berhasil diperbarui. Token lama tidak dapat digunakan lagi untuk pendaftaran baru.');
    }
}
