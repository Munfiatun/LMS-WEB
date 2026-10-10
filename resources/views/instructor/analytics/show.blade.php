@extends('layouts.app')

@php
    $title = 'Analitik Kursus: ' . $course->title;
    $breadcrumb = 'Analitik & Pelaporan';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
        <a href="{{ route('instructor.courses.index') }}" class="hover:text-white transition-colors">&larr; Kembali ke Daftar Kursus</a>
        <span class="text-slate-600">&bull;</span>
        <span class="text-slate-300">{{ $course->title }}</span>
    </div>

    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-indigo-300">Instructor Analytics</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Laporan Analitik Kursus</h1>
                <p class="text-sm text-slate-400 mt-2">Pantau progres siswa, partisipasi quiz, dan performa assessment untuk {{ $course->title }}.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $course->status === 'published' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' }}">
                Status: {{ ucfirst($course->status) }}
            </span>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pendaftar</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $totalStudents }}</span>
                <span class="text-xs text-indigo-400">Siswa</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Siswa Tuntas</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $completedStudents }}</span>
                <span class="text-xs text-emerald-400">100% progres</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Progres</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ number_format($averageProgress, 1) }}%</span>
                <span class="text-xs text-amber-400">Kursus</span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-3">
                <div class="bg-gradient-to-r from-amber-500 to-amber-300 h-1.5 rounded-full" style="width: {{ max(0, min(100, $averageProgress)) }}%"></div>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border {{ $atRiskStudents > 0 ? 'border-rose-500/30' : 'border-slate-800' }} shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Perlu Perhatian</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold {{ $atRiskStudents > 0 ? 'text-rose-300' : 'text-white' }} tracking-tight">{{ $atRiskStudents }}</span>
                <span class="text-xs {{ $atRiskStudents > 0 ? 'text-rose-400' : 'text-slate-500' }}">Prioritas tinggi</span>
            </div>
        </div>
    </div>

    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-300">Intervention Overview</p>
                <h3 class="mt-2 text-lg font-bold text-white">Ringkasan Intervensi</h3>
                <p class="mt-1 text-xs text-slate-500">Prioritas ditentukan dari progres, ketuntasan materi, dan hasil assessment. Ini alat bantu pemantauan, bukan diagnosis otomatis.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 min-w-full xl:min-w-[520px] xl:max-w-2xl">
                <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-300">Prioritas Tinggi</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $interventionCounts['high'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Perlu Dipantau</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $interventionCounts['medium'] }}</p>
                </div>
                <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-300">Pemantauan Rutin</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $interventionCounts['low'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-300">Tuntas / Stabil</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $interventionCounts['stable'] }}</p>
                </div>
            </div>
        </div>

        <div class="mt-5 border-t border-slate-800 pt-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-sm font-bold text-white">Prioritas Tindak Lanjut</h4>
                    <p class="mt-1 text-[11px] text-slate-500">Maksimal lima siswa ditampilkan berdasarkan tingkat intervensi dan progres terendah.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                @forelse($priorityStudents as $studentStat)
                    @php
                        $level = $studentStat['intervention_level'];
                        $badgeClass = match ($level) {
                            'high' => 'border-rose-500/30 bg-rose-500/10 text-rose-300',
                            'medium' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                            'stable' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                            default => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300',
                        };
                        $levelLabel = match ($level) {
                            'high' => 'Prioritas Tinggi',
                            'medium' => 'Perlu Dipantau',
                            'stable' => 'Tuntas / Stabil',
                            default => 'Pemantauan Rutin',
                        };
                    @endphp
                    <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-white">{{ $studentStat['enrollment']->student->name }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Progres {{ number_format($studentStat['enrollment']->progress_percentage, 0) }}% &bull;
                                    Rata-rata quiz {{ number_format($studentStat['average_quiz_score'], 1) }}%
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $badgeClass }}">{{ $levelLabel }}</span>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-slate-400">{{ $studentStat['intervention_message'] }}</p>
                        <div class="mt-3 text-right">
                            <a href="{{ route('instructor.courses.analytics.student', [$course, $studentStat['enrollment']->student]) }}" class="text-xs font-bold text-indigo-300 hover:text-indigo-200">Buka Detail Siswa &rarr;</a>
                        </div>
                    </div>
                @empty
                    <div class="lg:col-span-2 rounded-xl border border-dashed border-slate-800 px-4 py-6 text-center text-sm text-slate-500">
                        Belum ada data siswa untuk dirangkum.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Nilai Quiz</span>
            <p class="mt-3 text-3xl font-extrabold text-white">{{ number_format($averageQuizScore, 1) }}%</p>
            <p class="mt-1 text-xs text-slate-500">Dari {{ $submittedAttempts }} attempt submitted</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pass Rate Quiz</span>
            <p class="mt-3 text-3xl font-extrabold text-emerald-300">{{ number_format($quizPassRate, 1) }}%</p>
            <p class="mt-1 text-xs text-slate-500">Berdasarkan passing score masing-masing quiz</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Partisipasi Quiz</span>
            <p class="mt-3 text-3xl font-extrabold text-indigo-300">{{ $quizParticipants }}</p>
            <p class="mt-1 text-xs text-slate-500">Siswa unik pada {{ $totalQuizzes }} quiz</p>
        </div>
    </div>

    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h3 class="text-lg font-bold text-white">Performa Quiz</h3>
                <p class="mt-1 text-xs text-slate-500">Ringkasan performa assessment pada course ini.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800/80">
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Quiz</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Peserta</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Attempt</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Rata-Rata</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Pass Rate</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Passing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($quizPerformance as $quizStat)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-4">
                                <p class="text-sm font-semibold text-white">{{ $quizStat['title'] }}</p>
                                <p class="mt-1 text-[11px] uppercase tracking-wide text-slate-500">{{ $quizStat['status'] }}</p>
                            </td>
                            <td class="py-4 text-center text-sm text-slate-300">{{ $quizStat['participants'] }}</td>
                            <td class="py-4 text-center text-sm text-slate-300">{{ $quizStat['attempts'] }}</td>
                            <td class="py-4 text-center text-sm font-semibold text-white">{{ number_format($quizStat['average_score'], 1) }}%</td>
                            <td class="py-4 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $quizStat['pass_rate'] >= 70 ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20' }}">
                                    {{ number_format($quizStat['pass_rate'], 1) }}%
                                </span>
                            </td>
                            <td class="py-4 text-center text-sm text-slate-400">{{ number_format($quizStat['passing_score'], 0) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-sm text-slate-500">Belum ada quiz pada kursus ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <div class="mb-4">
            <h3 class="text-lg font-bold text-white">Daftar Pendaftar (Siswa)</h3>
            <p class="mt-1 text-xs text-slate-500">Buka detail siswa untuk melihat materi, assessment, dan rekomendasi tindak lanjut.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800/80">
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Siswa</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal Daftar</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Progres</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($enrollments as $enrollment)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-4 text-sm text-white font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-300 flex items-center justify-center font-bold text-xs border border-indigo-500/30">
                                        {{ substr($enrollment->student->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p>{{ $enrollment->student->name }}</p>
                                        <p class="text-[11px] text-slate-500 font-normal">{{ $enrollment->student->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 text-sm text-slate-400">{{ $enrollment->created_at->format('d M Y') }}</td>
                            <td class="py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-24 bg-slate-800 rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ max(0, min(100, (float) $enrollment->progress_percentage)) }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-300 font-medium w-10">{{ number_format($enrollment->progress_percentage, 0) }}%</span>
                                </div>
                            </td>
                            <td class="py-4">
                                @if($enrollment->status === 'completed')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-md border border-emerald-500/20">Lulus</span>
                                @elseif((float) $enrollment->progress_percentage < 50)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-300 bg-rose-500/10 px-2 py-1 rounded-md border border-rose-500/20">Perlu Perhatian</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-400 bg-amber-500/10 px-2 py-1 rounded-md border border-amber-500/20">Sedang Belajar</span>
                                @endif
                            </td>
                            <td class="py-4 text-right">
                                <a href="{{ route('instructor.courses.analytics.student', [$course, $enrollment->student]) }}" class="inline-flex items-center rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-1.5 text-xs font-bold text-indigo-200 transition-colors hover:bg-indigo-500/20">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-sm">Belum ada siswa yang mendaftar ke kursus ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $enrollments->links() }}</div>
    </div>
</div>
@endsection
