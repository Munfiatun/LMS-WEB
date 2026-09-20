<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $stats = [
            'total_students' => User::whereHas('role', fn ($q) => $q->where('name', 'student'))->count(),
            'total_instructors' => User::whereHas('role', fn ($q) => $q->where('name', 'instructor'))->count(),
            'total_courses' => 0,
        ];

        return view('home', compact('stats'));
    }
}
