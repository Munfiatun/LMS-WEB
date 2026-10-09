@extends('layouts.app')

@php
    $title = 'Kuis & Nilai';
    $breadcrumb = 'Kuis & Nilai';
@endphp

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    Kuis & Nilai
                </h3>
                <p class="text-sm text-slate-400 mt-1">Daftar evaluasi dan kuis dari kelas yang Anda ikuti.</p>
            </div>
        </div>

        @if($quizzes->isEmpty())
            <div class="p-10 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-slate-800/60 text-slate-400 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                </div>
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
                            <th class="px-6 py-4 text-center">Nilai</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($quizzes as $quiz)
                            @php
                                $quizAttempts = $attempts->get($quiz->id);
                                $latestAttempt = $quizAttempts ? $quizAttempts->last() : null;
                                
                                $status = 'Belum Dikerjakan';
                                $statusClass = 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                
                                if ($latestAttempt) {
                                    if ($latestAttempt->status === 'submitted') {
                                        $status = 'Selesai';
                                        $statusClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                                    } elseif ($latestAttempt->status === 'expired') {
                                        $status = 'Waktu Habis';
                                        $statusClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                                    } else {
                                        $status = 'Sedang Dikerjakan';
                                        $statusClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                                    }
                                }
                            @endphp
                            <tr class="bg-slate-900/40 hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white mb-1">{{ $quiz->title }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $quiz->total_questions }} Soal &bull; {{ $quiz->duration_minutes }} Menit</div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('courses.show', $quiz->course->slug) }}" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium">{{ $quiz->course->title }}</a>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($latestAttempt && $latestAttempt->status === 'submitted')
                                        <span class="text-lg font-bold text-white">{{ number_format($latestAttempt->score, 0) }}</span>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if(!$latestAttempt || $latestAttempt->status === 'in_progress')
                                        <a href="{{ route('student.quizzes.show', $quiz) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors shadow shadow-indigo-600/20">
                                            Kerjakan
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                        </a>
                                    @else
                                        <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $latestAttempt]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-slate-600 text-white text-xs font-bold rounded-lg transition-colors">
                                            Lihat Hasil
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6">
                {{ $quizzes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
