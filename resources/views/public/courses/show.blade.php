@extends('layouts.guest')

@php
    $title = $course->title;
@endphp

@section('content')
<div class="min-h-screen bg-slate-950">
    {{-- Course Hero --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-950 via-slate-950 to-violet-950 border-b border-slate-800">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-violet-500/20 rounded-full blur-3xl"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 relative z-10">
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-4">
                <a href="{{ route('courses.index') }}" class="hover:text-white transition-colors">&larr; Katalog Kursus</a>
                @if($course->category)
                    <span class="text-slate-600">&bull;</span>
                    <a href="{{ route('courses.index', ['category' => $course->category->slug]) }}" class="text-indigo-400 hover:text-indigo-300 transition-colors">
                        {{ $course->category->icon ?? '' }} {{ $course->category->name }}
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2">
                    <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight leading-tight">{{ $course->title }}</h1>
                    <p class="text-base text-slate-400 mt-4 leading-relaxed">{{ $course->description }}</p>

                    {{-- Instructor --}}
                    <div class="flex items-center gap-3 mt-6">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-sm font-bold text-white shadow-lg shadow-indigo-500/20">
                            {{ substr($course->instructor->name ?? 'I', 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $course->instructor->name ?? 'Instructor' }}</p>
                            <p class="text-xs text-slate-400">Instructor</p>
                        </div>
                    </div>
                </div>

                {{-- Course Info Card --}}
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm shadow-xl space-y-5 h-fit">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                            <p class="text-2xl font-extrabold text-indigo-400">{{ $course->sections->count() }}</p>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Bab / Chapter</p>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                            @php
                                $totalMaterials = $course->sections->sum(fn($s) => $s->materials->count());
                            @endphp
                            <p class="text-2xl font-extrabold text-emerald-400">{{ $totalMaterials }}</p>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Materi Ajar</p>
                        </div>
                    </div>

                    @php
                        $totalDuration = $course->sections->flatMap->materials->sum('duration_minutes');
                    @endphp
                    <div class="text-center p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                        <p class="text-lg font-extrabold text-white">{{ $totalDuration }} menit</p>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Estimasi Total Durasi</p>
                    </div>

                    @auth
                        @if(auth()->user()->isStudent())
                            @if($isEnrolled)
                                <p class="text-sm text-emerald-400">Progress: {{ number_format($enrollment->progress_percentage, 0) }}% Selesai</p>
                                <a href="{{ route('student.courses.continue', $course) }}" class="w-full py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                    Lanjutkan Belajar
                                </a>
                            @else
                                <form action="{{ route('student.courses.enroll', $course) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 shadow-lg shadow-indigo-600/25 transition-all flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                        Daftar Kursus Ini
                                    </button>
                                </form>
                            @endif
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="block w-full py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 shadow-lg shadow-indigo-600/25 transition-all text-center">
                            Login untuk Mendaftar
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Syllabus / Course Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-xl font-extrabold text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            Silabus Kursus
        </h2>

        <div class="space-y-4">
            @forelse($course->sections as $sIndex => $section)
                <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow overflow-hidden" x-data="{ open: {{ $sIndex === 0 ? 'true' : 'false' }} }">
                    <button @click="open = !open" type="button"
                            class="w-full p-5 flex items-center justify-between text-left hover:bg-slate-950/40 transition-colors">
                        <div class="flex items-center gap-4">
                            <span class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-300 text-sm font-bold flex items-center justify-center border border-indigo-500/30 flex-shrink-0">
                                {{ $section->order }}
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-white">{{ $section->title }}</h3>
                                @if($section->description)
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $section->description }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="text-xs text-slate-500">{{ $section->materials->count() }} materi</span>
                            <svg :class="open ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </button>

                    <div x-show="open" x-collapse class="border-t border-slate-800/60">
                        <div class="p-4 space-y-2">
                            @forelse($section->materials as $mat)
                                    @php
                                        $targetUrl = '#';
                                        if ($isEnrolled) {
                                            $targetUrl = route('student.materials.show', [$course, $mat]);
                                        }
                                    @endphp
                                    
                                    @if($isEnrolled)
                                        <a href="{{ $targetUrl }}" class="block group">
                                            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-950/40 border border-slate-800/40 group-hover:bg-slate-900/60 group-hover:border-slate-700 transition-all cursor-pointer">
                                    @else
                                        <div>
                                            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-950/40 border border-slate-800/40">
                                    @endif
                                            <span class="w-6 h-6 rounded bg-slate-800 text-slate-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0">
                                                {{ $mat->order }}
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-white truncate {{ $isEnrolled ? 'group-hover:text-indigo-400 transition-colors' : '' }}">{{ $mat->title }}</p>
                                                
                                                @if($mat->description)
                                                    <p class="text-xs text-slate-500 truncate mt-0.5">{{ $mat->description }}</p>
                                                @endif
                                            </div>
                                            @if($mat->slidebook && $mat->slidebook->isPublished())
                                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center gap-1 flex-shrink-0">
                                                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    Slidebook
                                                </span>
                                            @endif
                                            <span class="text-[11px] text-slate-500 flex-shrink-0 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ $mat->duration_minutes }} min
                                            </span>
                                        </div>
                                    @if($isEnrolled)
                                        </a>
                                    @else
                                        </div>
                                    @endif
                            @empty
                                <p class="text-center text-xs text-slate-500 py-4">Belum ada materi dalam bab ini.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center rounded-2xl bg-slate-900/60 border border-slate-800">
                    <p class="text-sm text-slate-400">Silabus kursus belum tersedia.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
