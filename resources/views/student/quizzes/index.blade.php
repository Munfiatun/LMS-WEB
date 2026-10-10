@extends('layouts.app')

@php
    $title = 'Kuis & Nilai';
    $breadcrumb = 'Kuis & Nilai';
@endphp

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-violet-950/30 to-slate-900 border border-slate-800 shadow-xl">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-300">Assessment Overview</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Kuis & Nilai</h1>
                <p class="mt-2 text-sm text-slate-400">Pantau kuis yang tersedia, riwayat attempt, dan hasil assessment dari kursus yang Anda ikuti.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kuis Tersedia</span>
            <p class="mt-3 text-3xl font-extrabold text-white">{{ $stats['available_quizzes'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Dari kursus aktif Anda</p>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sudah Dikerjakan</span>
            <p class="mt-3 text-3xl font-extrabold text-indigo-300">{{ $stats['attempted_quizzes'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['submitted_attempts'] }} attempt submitted</p>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Quiz Lulus</span>
            <p class="mt-3 text-3xl font-extrabold text-emerald-300">{{ $stats['passed_quizzes'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Berdasarkan passing score quiz</p>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Nilai</span>
            <p class="mt-3 text-3xl font-extrabold text-amber-300">{{ number_format($stats['average_score'], 1) }}%</p>
            <p class="mt-1 text-xs text-slate-500">Attempt yang sudah submitted</p>
        </div>
    </div>

    @if($recentAttempts->isNotEmpty())
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
            <div class="mb-4">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-violet-300">Recent Activity</p>
                <h3 class="mt-1 text-lg font-bold text-white">Riwayat Nilai Terbaru</h3>
                <p class="mt-1 text-xs text-slate-500">Maksimal lima attempt submitted terbaru.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                @foreach($recentAttempts as $attempt)
                    @php
                        $passed = $attempt->percentage !== null && $attempt->percentage >= (float) $attempt->quiz->passing_score;
                    @endphp
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-white">{{ $attempt->quiz->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $attempt->quiz->course->title }} &bull; {{ optional($attempt->submitted_at)->format('d M Y H:i') }}</p>
                            </div>
                            <span class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase {{ $passed ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/20 bg-amber-500/10 text-amber-300' }}">
                                {{ $passed ? 'Lulus' : 'Belum Lulus' }}
                            </span>
                        </div>
                        <div class="mt-4 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Nilai</p>
                                <p class="mt-1 text-2xl font-extrabold text-white">{{ number_format((float) $attempt->percentage, 1) }}%</p>
                            </div>
                            <a href="{{ route('student.quizzes.result', ['quiz' => $attempt->quiz, 'attempt' => $attempt]) }}" class="text-xs font-bold text-violet-300 hover:text-violet-200">Lihat Hasil &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <div class="mb-6">
            <h3 class="text-xl font-bold text-white">Daftar Kuis</h3>
            <p class="text-sm text-slate-400 mt-1">Kuis published dari kelas yang sedang Anda ikuti.</p>
        </div>

        @if($quizzes->isEmpty())
            <div class="p-10 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center flex flex-col items-center justify-center">
                <p class="text-base font-medium text-slate-300">Belum ada kuis yang tersedia.</p>
                <p class="text-sm text-slate-500 mt-1">Anda akan melihat kuis di sini setelah bergabung dengan kelas yang memiliki kuis.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-xs font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-6 py-4">Nama Kuis</th>
                            <th class="px-6 py-4">Kursus</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Attempt</th>
                            <th class="px-6 py-4 text-center">Nilai Terbaik</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($quizzes as $quiz)
                            @php
                                $quizAttempts = $attempts->get($quiz->id, collect());
                                $latestAttempt = $quizAttempts->last();
                                $submitted = $quizAttempts->where('status', 'submitted');
                                $bestPercentage = $submitted->whereNotNull('percentage')->max('percentage');
                                $hasPassed = $submitted->contains(fn ($attempt) => $attempt->percentage !== null && $attempt->percentage >= (float) $quiz->passing_score);

                                $status = 'Belum Dikerjakan';
                                $statusClass = 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                if ($latestAttempt?->status === 'in_progress') {
                                    $status = 'Sedang Dikerjakan';
                                    $statusClass = 'bg-amber-500/10 text-amber-300 border-amber-500/20';
                                } elseif ($hasPassed) {
                                    $status = 'Lulus';
                                    $statusClass = 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20';
                                } elseif ($submitted->isNotEmpty()) {
                                    $status = 'Belum Lulus';
                                    $statusClass = 'bg-amber-500/10 text-amber-300 border-amber-500/20';
                                } elseif ($latestAttempt?->status === 'expired') {
                                    $status = 'Waktu Habis';
                                    $statusClass = 'bg-rose-500/10 text-rose-300 border-rose-500/20';
                                }
                            @endphp
                            <tr class="bg-slate-900/40 hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white mb-1">{{ $quiz->title }}</div>
                                    <div class="text-[11px] text-slate-400">Passing {{ number_format($quiz->passing_score, 0) }}% &bull; {{ $quiz->total_questions }} soal &bull; {{ $quiz->duration_minutes }} menit</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-300">{{ $quiz->course->title }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border {{ $statusClass }}">{{ $status }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-slate-300">{{ $quizAttempts->count() }}{{ $quiz->max_attempts ? ' / '.$quiz->max_attempts : '' }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($bestPercentage !== null)
                                        <span class="text-lg font-bold {{ $hasPassed ? 'text-emerald-300' : 'text-white' }}">{{ number_format((float) $bestPercentage, 1) }}%</span>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($latestAttempt?->status === 'in_progress')
                                        <a href="{{ route('student.quizzes.take', ['quiz' => $quiz, 'attempt' => $latestAttempt]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-lg transition-colors">Lanjutkan</a>
                                    @elseif($latestAttempt?->status === 'expired')
                                        <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $latestAttempt]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold rounded-lg transition-colors">Lihat Hasil</a>
                                    @elseif($submitted->isNotEmpty())
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $submitted->last()]) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold rounded-lg transition-colors">Hasil Terbaru</a>
                                            <a href="{{ route('student.quizzes.show', $quiz) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Buka Kuis</a>
                                        </div>
                                    @else
                                        <a href="{{ route('student.quizzes.show', $quiz) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Kerjakan</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $quizzes->links() }}</div>
        @endif
    </div>
</div>
@endsection
