<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN)->first();
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->first();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->first();

        $stats = [
            'total_users' => User::count(),
            'total_students' => $studentRole ? User::where('role_id', $studentRole->id)->count() : 0,
            'total_instructors' => $instructorRole ? User::where('role_id', $instructorRole->id)->count() : 0,
            'total_admins' => $adminRole ? User::where('role_id', $adminRole->id)->count() : 0,
            'total_courses' => 0, // Placeholder until Phase 3
            'total_materials' => 0,
            'total_quizzes' => 0,
            'ai_logs_count' => 0,
        ];

        $recentUsers = User::with('role')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }
}
