@extends('layouts.app')

@php
    $title = 'Progres Kursus: ' . $course->title;
    $breadcrumb = 'Progres Belajar';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('student.courses.index') }}" class="hover:text-white transition-colors">&larr; Kembali ke Kursus Saya</a>
        <span class="text-slate-600">&bull;</span>
        <span class="text-slate-300">{{ $course->title }}</span>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-emerald-950/30 to-slate-900 p-6 shadow-xl">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-300">Learning Progress</span>
                    <span class="text-xs text-slate-500">{{ $course->category?->name ?? 'Uncategorized' }}</span>
                </div>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $course->title }}</h1>
                <p class="mt-2 text-sm text-slate-400">Pengajar: {{ $course->instructor->name }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('student.courses.continue', $course) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-emerald-500">
                    Lanjutkan Belajar
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
        <div class="pointer-events-none absolute -bottom-16 -right-16 h-72 w-72 rounded-full bg-emerald-500/10 blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Progres Kursus</p>
            <p class="mt-3 text-3xl font-extrabold text-white">{{ number_format($enrollment->progress_percentage, 1) }}%</p>
            <div class="mt-3 h-2 w-full rounded-full bg-slate-800">
                <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-400" style="width: {{ max(0, min(100, (float) $enrollment->progress_percentage)) }}%"></div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Materi Selesai</p>
            <p class="mt-3 text-3xl font-extrabold text-white">{{ $completedMaterials }}<span class="text-lg text-slate-500">/{{ $totalMaterials }}</span></p>
            <p class="mt-1 text-xs text-slate-500">Materi published pada kursus ini</p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Quiz Lulus</p>
            <p class="mt-3 text-3xl font-extrabold text-emerald-300">{{ $passedQuizzes }}<span class="text-lg text-slate-500">/{{ $totalQuizzes }}</span></p>
            <p class="mt-1 text-xs text-slate-500">{{ $submittedAttempts }} attempt submitted</p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Quiz</p>
            <p class="mt-3 text-3xl font-extrabold text-amber-300">{{ number_format($averageQuizScore, 1) }}%</p>
            <p class="mt-1 text-xs text-slate-500">Hanya dari attempt yang sudah submitted</p>
        </div>
    </div>

    <div class="rounded-2xl border {{ $recommendationType === 'complete' ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-indigo-500/30 bg-indigo-500/5' }} p-5 shadow">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] {{ $recommendationType === 'complete' ? 'text-emerald-300' : 'text-indigo-300' }}">Rekomendasi Belajar</p>
                <h2 class="mt-2 text-lg font-bold text-white">{{ $recommendation }}</h2>
                <p class="mt-1 text-xs text-slate-500">Rekomendasi dibuat dari progres materi dan hasil quiz pada kursus ini.</p>
            </div>

            @if($nextMaterial)
                <a href="{{ route('student.materials.show', [$course, $nextMaterial]) }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">
                    Buka Materi Berikutnya
                </a>
            @elseif($nextQuiz)
                <a href="{{ route('student.quizzes.show', $nextQuiz) }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 hover:bg-amber-400">
                    Buka Quiz
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-white">Progres Materi</h2>
                <p class="mt-1 text-xs text-slate-500">Lihat materi yang sudah selesai dan yang masih perlu dipelajari.</p>
            </div>

            <div class="space-y-3">
                @forelse($materialRows as $row)
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-white">{{ $row['material']->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $row['material']->section?->title ?? 'Materi Kursus' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase {{ $row['completed'] ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/20 bg-amber-500/10 text-amber-300' }}">
                                {{ $row['completed'] ? 'Selesai' : 'Belum' }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="text-[11px] text-slate-600">
                                {{ $row['completed_at'] ? 'Selesai ' . $row['completed_at']->format('d M Y') : 'Belum ditandai selesai' }}
                            </span>
                            <a href="{{ route('student.materials.show', [$course, $row['material']]) }}" class="text-xs font-bold text-emerald-300 hover:text-emerald-200">Buka Materi &rarr;</a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-800 p-6 text-center text-sm text-slate-500">Belum ada materi published pada kursus ini.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-white">Performa Quiz</h2>
                <p class="mt-1 text-xs text-slate-500">Nilai terbaik, nilai terbaru, dan status kelulusan tiap quiz.</p>
            </div>

            <div class="space-y-3">
                @forelse($quizRows as $row)
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-white">{{ $row['quiz']->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">Passing score {{ number_format($row['quiz']->passing_score, 0) }}% &bull; {{ $row['attempts'] }} attempt</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase {{ $row['passed'] ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/20 bg-amber-500/10 text-amber-300' }}">
                                {{ $row['passed'] ? 'Lulus' : 'Belum Lulus' }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                                <p class="text-[10px] font-bold uppercase text-slate-500">Nilai Terbaik</p>
                                <p class="mt-1 text-lg font-bold text-white">{{ $row['best_score'] === null ? '-' : number_format($row['best_score'], 1) . '%' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                                <p class="text-[10px] font-bold uppercase text-slate-500">Nilai Terbaru</p>
                                <p class="mt-1 text-lg font-bold text-white">{{ $row['latest_score'] === null ? '-' : number_format($row['latest_score'], 1) . '%' }}</p>
                            </div>
                        </div>

                        <div class="mt-3 flex justify-end">
                            <a href="{{ route('student.quizzes.show', $row['quiz']) }}" class="text-xs font-bold text-indigo-300 hover:text-indigo-200">Buka Quiz &rarr;</a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-800 p-6 text-center text-sm text-slate-500">Belum ada quiz tersedia pada kursus ini.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
