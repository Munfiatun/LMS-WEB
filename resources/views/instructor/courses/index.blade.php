@extends('layouts.app')

@php
    $title = 'Kursus Saya';
    $breadcrumb = 'Kursus Saya';
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Koleksi Kursus Saya</h1>
            <p class="text-sm text-slate-400 mt-1">Buat silabus pembelajaran, upload modul materi, dan pantau publikasi materi.</p>
        </div>
        <a href="{{ route('instructor.courses.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-lg shadow-indigo-600/25 transition-all">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Buat Kursus Baru
        </a>
    </div>

    <!-- Course Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($courses as $course)
            <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow hover:border-slate-700 transition-all flex flex-col justify-between overflow-hidden">
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            {{ $course->category?->name ?? 'Uncategorized' }}
                        </span>
                        @if($course->status === 'published')
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Published</span>
                        @elseif($course->status === 'archived')
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-slate-500/20 text-slate-400 border border-slate-500/30">Archived</span>
                        @else
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">Draft</span>
                        @endif
                    </div>

                    <h3 class="text-base font-bold text-white line-clamp-1 hover:text-indigo-400 transition-colors">
                        <a href="{{ route('instructor.courses.edit', $course) }}">{{ $course->title }}</a>
                    </h3>

                    <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                        {{ $course->description ?? 'Belum ada deskripsi kursus.' }}
                    </p>

                    <div class="flex items-center gap-4 text-xs text-slate-500 pt-2 border-t border-slate-800/80">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            {{ $course->sections_count }} Bab
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            {{ $course->materials_count }} Materi
                        </span>
                    </div>
                </div>

                <div class="px-5 py-3.5 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('instructor.courses.edit', $course) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1">
                            <span>Kelola Silabus</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                        <span class="text-slate-700">|</span>
                        <a href="{{ route('instructor.courses.analytics', $course) }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            <span>Analitik</span>
                        </a>
                    </div>

                    <form action="{{ route('instructor.courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kursus ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center rounded-2xl bg-slate-900/60 border border-slate-800">
                <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                <h3 class="text-base font-bold text-white">Belum Ada Kursus</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Mulai susun kurikulum Anda sekarang untuk menyediakan pembelajaran bermutu bagi para siswa.</p>
                <a href="{{ route('instructor.courses.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors">
                    + Buat Kursus Pertama
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($courses->hasPages())
        <div class="pt-4">
            {{ $courses->links() }}
        </div>
    @endif
</div>
@endsection
