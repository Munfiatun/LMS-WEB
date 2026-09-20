@extends('layouts.app')

@php
    $title = 'Portal Siswa';
    $breadcrumb = 'Student Dashboard';
@endphp

@section('content')
<div class="space-y-6">
    <!-- Student Header Banner -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-emerald-950/40 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Akademi Pembelajaran
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-slate-400 mt-1">Lanjutkan belajar modul interaktif, baca slidebook ringkas, dan uji pemahaman lewat kuis terstandar.</p>
            </div>
            <div>
                <a href="{{ route('home') }}" class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    Jelajahi Kursus
                </a>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kursus Diikuti</span>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['enrolled_courses'] }}</span>
                <span class="text-xs text-emerald-400">Aktif</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kursus Selesai</span>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['completed_courses'] }}</span>
                <span class="text-xs text-indigo-400">Tuntas 100%</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Materi Selesai</span>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['completed_materials'] }}</span>
                <span class="text-xs text-purple-400">Slide / Bacaan</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kuis Dikerjakan</span>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['quiz_attempts'] }}</span>
                <span class="text-xs text-cyan-400">Evaluasi</span>
            </div>
        </div>
    </div>

    <!-- Continue Learning Section / Enrolled Courses -->
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    Kelas Saya
                </h3>
                <p class="text-xs text-slate-400">Daftar kelas yang sedang Anda ikuti.</p>
            </div>
        </div>

        @if($enrollments->isEmpty())
            <div class="p-8 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center flex flex-col items-center justify-center">
                <div class="w-12 h-12 rounded-full bg-slate-800/60 text-slate-400 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-300">Belum ada kelas yang diikuti.</p>
                <a href="{{ route('courses.index') }}" class="mt-4 px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Jelajahi Kelas</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($enrollments as $enrollment)
                    <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-grow">
                            <h4 class="text-lg font-bold text-white mb-1">{{ $enrollment->course->title }}</h4>
                            <p class="text-xs text-slate-400 mb-3">{{ $enrollment->course->category ? $enrollment->course->category->name : 'Uncategorized' }}</p>
                            
                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-700 rounded-full h-2.5 mb-1">
                                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: {{ $enrollment->progress_percentage }}%"></div>
                            </div>
                            <div class="flex justify-between items-center text-xs text-slate-400">
                                <span>{{ number_format($enrollment->progress_percentage, 0) }}% Selesai</span>
                                <span>{{ $enrollment->status === 'completed' ? 'Tuntas' : 'Sedang Belajar' }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex gap-2">
                            <a href="{{ route('student.courses.continue', $enrollment->course) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                Lanjutkan
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
