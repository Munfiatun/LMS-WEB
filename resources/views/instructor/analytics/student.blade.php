@extends('layouts.app')

@php
    $title = 'Performa Siswa: ' . $student->name;
    $breadcrumb = 'Analitik & Pelaporan';
    $levelClasses = [
        'high' => 'border-rose-500/30 bg-rose-500/10 text-rose-200',
        'medium' => 'border-amber-500/30 bg-amber-500/10 text-amber-200',
        'low' => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-200',
        'stable' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200',
    ];
    $levelLabels = [
        'high' => 'Prioritas Tinggi',
        'medium' => 'Perlu Dipantau',
        'low' => 'Pemantauan Rutin',
        'stable' => 'Tuntas / Stabil',
    ];
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('instructor.courses.analytics', $course) }}" class="hover:text-white transition-colors">&larr; Kembali ke Analitik Kursus</a>
        <span class="text-slate-600">&bull;</span>
        <span class="text-slate-300">{{ $course->title }}</span>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 p-6 shadow-xl">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-indigo-500/30 bg-indigo-500/15 text-xl font-extrabold text-indigo-200">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-300">Student Performance Detail</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white">{{ $student->name }}</h1>
                    <p class="mt-1 text-sm text-slate-400">{{ $student->email }}</p>
                </div>
            </div>
            <div class="rounded-xl border px-4 py-3 {{ $levelClasses[$interventionLevel] ?? $levelClasses['low'] }}">
                <p class="text-[10px] font-bold uppercase tracking-wider opacity-75">Status Intervensi</p>
                <p class="mt-1 text-sm font-extrabold">{{ $levelLabels[$interventionLevel] ?? 'Pemantauan Rutin' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Progres Kursus</p>
            <p class="mt-3 text-3xl font-extrabold text-white">{{ number_format($enrollment->progress_percentage, 1) }}%</p>
            <div class="mt-3 h-1.5 rounded-full bg-slate-800">
                <div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ max(0, min(100, (float) $enrollment->progress_percentage)) }}%"></div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Materi Selesai</p>
            <p class="mt-3 text-3xl font-extrabold text-white">{{ $completedMaterials }}<span class="text-lg text-slate-500">/{{ $totalMaterials }}</span></p>
            <p class="mt-1 text-xs text-slate-500">Materi published</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Quiz Lulus</p>
            <p class="mt-3 text-3xl font-extrabold text-emerald-300">{{ $passedQuizzes }}<span class="text-lg text-slate-500">/{{ $totalQuizzes }}</span></p>
            <p class="mt-1 text-xs text-slate-500">{{ $submittedAttempts }} attempt submitted</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Quiz</p>
            <p class="mt-3 text-3xl font-extrabold text-amber-300">{{ number_format($averageQuizScore, 1) }}%</p>
            <p class="mt-1 text-xs text-slate-500">Semua attempt submitted</p>
        </div>
    </div>

    <div class="rounded-2xl border p-5 {{ $levelClasses[$interventionLevel] ?? $levelClasses['low'] }}">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 text-lg">◎</div>
            <div>
                <h2 class="text-sm font-extrabold">Rekomendasi Tindak Lanjut</h2>
                <p class="mt-1 text-sm leading-relaxed opacity-90">{{ $interventionMessage }}</p>
                <p class="mt-2 text-[11px] opacity-65">Rekomendasi dibuat secara deterministik dari progres dan hasil assessment, bukan diagnosis otomatis.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-white">Progres Materi</h2>
                <p class="mt-1 text-xs text-slate-500">Status material published pada course ini.</p>
            </div>
            <div class="space-y-2">
                @forelse($materialRows as $material)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ $material['title'] }}</p>
                            <p class="mt-1 truncate text-[11px] text-slate-500">{{ $material['section'] }}</p>
                        </div>
                        @if($material['completed'])
                            <div class="text-right">
                                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase text-emerald-300">Selesai</span>
                                @if($material['completed_at'])
                                    <p class="mt-1 text-[10px] text-slate-500">{{ $material['completed_at']->format('d M Y') }}</p>
                                @endif
                            </div>
                        @else
                            <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase text-amber-300">Belum</span>
                        @endif
                    </div>
                @empty
                    <p class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-6 text-center text-sm text-slate-500">Belum ada materi published.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-white">Performa Quiz</h2>
                <p class="mt-1 text-xs text-slate-500">Nilai terbaik, nilai terbaru, dan jumlah attempt siswa.</p>
            </div>
            <div class="space-y-2">
                @forelse($quizRows as $quiz)
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-white">{{ $quiz['title'] }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">Passing score {{ number_format($quiz['passing_score'], 0) }}% &bull; {{ $quiz['attempts'] }} attempt</p>
                            </div>
                            @if($quiz['passed'])
                                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase text-emerald-300">Lulus</span>
                            @elseif($quiz['attempts'] > 0)
                                <span class="rounded-full border border-rose-500/20 bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold uppercase text-rose-300">Belum Lulus</span>
                            @else
                                <span class="rounded-full border border-slate-700 bg-slate-800 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-400">Belum Mengerjakan</span>
                            @endif
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div class="rounded-lg bg-slate-900 px-3 py-2">
                                <p class="text-slate-500">Nilai Terbaik</p>
                                <p class="mt-1 font-bold text-white">{{ $quiz['best_score'] === null ? '-' : number_format($quiz['best_score'], 1).'%' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-900 px-3 py-2">
                                <p class="text-slate-500">Nilai Terbaru</p>
                                <p class="mt-1 font-bold text-white">{{ $quiz['latest_score'] === null ? '-' : number_format($quiz['latest_score'], 1).'%' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-6 text-center text-sm text-slate-500">Belum ada quiz pada course ini.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
