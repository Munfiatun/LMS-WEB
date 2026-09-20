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

    <!-- Header Banner -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Laporan Analitik Kursus</h1>
                <p class="text-sm text-slate-400 mt-2">Pantau progres pendaftaran dan ketuntasan materi siswa untuk {{ $course->title }}.</p>
            </div>
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $course->status === 'published' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' }}">
                    Status: {{ ucfirst($course->status) }}
                </span>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pendaftar</span>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $totalStudents }}</span>
                <span class="text-xs text-indigo-400">Siswa Aktif</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Siswa Lulus</span>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $completedStudents }}</span>
                <span class="text-xs text-emerald-400">Ketuntasan 100%</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Progres</span>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ number_format($averageProgress, 1) }}%</span>
                <span class="text-xs text-amber-400">Keseluruhan</span>
            </div>
            <!-- Visual Progress Bar -->
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-3">
                <div class="bg-gradient-to-r from-amber-500 to-amber-300 h-1.5 rounded-full" style="width: {{ $averageProgress }}%"></div>
            </div>
        </div>
    </div>

    <!-- Student Enrollments Table -->
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <h3 class="text-lg font-bold text-white mb-4">Daftar Pendaftar (Siswa)</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800/80">
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Siswa</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal Daftar</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Progres</th>
                        <th class="pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Status</th>
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
                            <td class="py-4 text-sm text-slate-400">
                                {{ $enrollment->created_at->format('d M Y') }}
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-24 bg-slate-800 rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $enrollment->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-300 font-medium w-8">{{ number_format($enrollment->progress_percentage, 0) }}%</span>
                                </div>
                            </td>
                            <td class="py-4">
                                @if($enrollment->status === 'completed')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-md border border-emerald-500/20">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Lulus
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-400 bg-amber-500/10 px-2 py-1 rounded-md border border-amber-500/20">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Sedang Belajar
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500 text-sm">
                                Belum ada siswa yang mendaftar ke kursus ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $enrollments->links() }}
        </div>
    </div>
</div>
@endsection
