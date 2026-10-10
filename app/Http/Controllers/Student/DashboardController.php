<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentLearningDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private StudentLearningDashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $summary = $this->dashboard->summarize($user);

        return view('student.dashboard', [
            'user' => $user,
            ...$summary,
        ]);
    }
}
