<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionRequest;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SectionController extends Controller
{
    public function store(StoreSectionRequest $request, Course $course): RedirectResponse
    {
        $maxOrder = $course->sections()->max('order') ?? 0;

        $course->sections()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'order' => $request->validated('order') ?? ($maxOrder + 1),
            'status' => 'active',
        ]);

        return back()->with('success', 'Bab / Chapter baru berhasil ditambahkan.');
    }

    public function destroy(CourseSection $section): RedirectResponse
    {
        Gate::authorize('delete', $section);

        $section->delete();

        return back()->with('success', 'Bab berhasil dihapus.');
    }
}
