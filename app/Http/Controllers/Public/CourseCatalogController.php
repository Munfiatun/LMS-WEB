<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $search = $request->query('search');

        $query = Course::published()
            ->with(['category', 'instructor'])
            ->withCount(['sections', 'materials']);

        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->latest('published_at')->paginate(9)->withQueryString();
        $categories = Category::withCount(['courses' => fn ($q) => $q->published()])->get();

        return view('public.courses.index', compact('courses', 'categories', 'categorySlug', 'search'));
    }

    public function show(string $slug): View
    {
        $course = Course::published()
            ->where('slug', $slug)
            ->with([
                'category',
                'instructor',
                'sections.materials' => fn ($q) => $q->published()->with('slidebook'),
            ])
            ->firstOrFail();

        $enrollment = null;
        $isEnrolled = false;
        if (auth()->check() && auth()->user()->isStudent()) {
            $enrollment = $course->enrollments()->where('student_id', auth()->id())->first();
            $isEnrolled = $enrollment !== null;
        }

        return view('public.courses.show', compact('course', 'isEnrolled', 'enrollment'));
    }
}
