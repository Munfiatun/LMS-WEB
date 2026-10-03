@extends('layouts.app')

@php
    $title = 'Kursus Saya';
    $breadcrumb = 'Kursus Saya';
@endphp

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    Kursus Saya
                </h3>
                <p class="text-sm text-slate-400 mt-1">Daftar kelas yang sedang Anda ikuti.</p>
            </div>
            <div>
                <a href="{{ route('student.courses.explore') }}" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    Jelajah Kursus
                </a>
            </div>
        </div>

        @if($courses->isEmpty())
            <div class="p-10 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-slate-800/60 text-slate-400 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                <p class="text-base font-medium text-slate-300">Belum ada kursus yang diikuti.</p>
                <p class="text-sm text-slate-500 mt-1 mb-5">Anda belum terdaftar di kelas manapun.</p>
                <a href="{{ route('student.courses.explore') }}" class="px-5 py-2.5 text-sm text-white bg-indigo-600 rounded-xl hover:bg-indigo-500 shadow-lg shadow-indigo-600/20 font-semibold transition-all">Jelajah Kursus Sekarang</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($courses as $course)
                    @php
                        $enrollment = $course->enrollments->first();
                    @endphp
                    <div class="p-5 bg-slate-800/50 rounded-2xl border border-slate-700/80 hover:border-slate-600 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex-grow">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ $course->category?->name ?? 'Uncategorized' }}
                                </span>
                            </div>
                            <h4 class="text-xl font-extrabold text-white mb-1"><a href="{{ route('courses.show', $course->slug) }}" class="hover:text-indigo-300 transition-colors">{{ $course->title }}</a></h4>
                            <p class="text-xs text-slate-400 mb-4 line-clamp-1">Pengajar: {{ $course->instructor->name }}</p>
                            
                            <!-- Progress Bar -->
                            @if($enrollment)
                                <div class="w-full max-w-md">
                                    <div class="flex justify-between items-end text-[11px] font-semibold text-slate-400 mb-1.5">
                                        <span>Progres Belajar</span>
                                        <span class="{{ $enrollment->status === 'completed' ? 'text-emerald-400' : 'text-indigo-400' }}">
                                            {{ number_format($enrollment->progress_percentage, 0) }}% Selesai
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-900 rounded-full h-2 border border-slate-800 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $enrollment->status === 'completed' ? 'bg-emerald-500' : 'bg-gradient-to-r from-indigo-500 to-purple-500' }}" style="width: {{ $enrollment->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex-shrink-0 flex gap-3">
                            <a href="{{ route('student.courses.continue', $course) }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2">
                                Buka Kelas
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-6">
                {{ $courses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
