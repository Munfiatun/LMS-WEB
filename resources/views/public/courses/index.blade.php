@extends('layouts.guest')

@php
    $title = 'Katalog Kursus';
@endphp

@section('content')
<div class="min-h-screen bg-slate-950">
    {{-- Hero Banner --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-950 via-slate-950 to-violet-950 border-b border-slate-800">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMyI+PHBhdGggZD0iTTM2IDE4YzMuMzE0IDAgNi0yLjY4NiA2LTZzLTIuNjg2LTYtNi02LTYgMi42ODYtNiA2IDIuNjg2IDYgNiA2em0wIDMwYzMuMzE0IDAgNi0yLjY4NiA2LTZzLTIuNjg2LTYtNi02LTYgMi42ODYtNiA2IDIuNjg2IDYgNiA2ek02IDE4YzMuMzE0IDAgNi0yLjY4NiA2LTZTOS4zMTQgNiA2IDYgMCA4LjY4NiAwIDEyczIuNjg2IDYgNiA2ek02IDQ4YzMuMzE0IDAgNi0yLjY4NiA2LTZzLTIuNjg2LTYtNi02LTYgMi42ODYtNiA2IDIuNjg2IDYgNiA2eiIvPjwvZz48L2c+PC9zdmc+')] opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight">
                    Jelajahi <span class="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">Kursus Terbaik</span>
                </h1>
                <p class="text-lg text-slate-400 mt-4 leading-relaxed">
                    Temukan materi pembelajaran berkualitas tinggi yang didukung teknologi AI untuk pengalaman belajar modern.
                </p>

                {{-- Search Bar --}}
                <form action="{{ route('courses.index') }}" method="GET" class="mt-8 max-w-xl mx-auto">
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kursus..."
                               class="w-full pl-12 pr-4 py-3.5 bg-slate-900/80 border border-slate-700 rounded-2xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm backdrop-blur">
                        @if($categorySlug ?? false)
                            <input type="hidden" name="category" value="{{ $categorySlug }}">
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Sidebar: Categories Filter --}}
            <aside class="lg:w-64 flex-shrink-0 space-y-4">
                <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Filter Kategori</h3>
                    <div class="space-y-1">
                        <a href="{{ route('courses.index', ['search' => $search ?? '']) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors {{ !($categorySlug ?? false) ? 'bg-indigo-600 text-white font-bold shadow' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                            <span>Semua Kategori</span>
                            <span class="text-xs {{ !($categorySlug ?? false) ? 'text-indigo-200' : 'text-slate-500' }}">{{ $courses->total() }}</span>
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ route('courses.index', ['category' => $cat->slug, 'search' => $search ?? '']) }}"
                               class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors {{ ($categorySlug ?? '') === $cat->slug ? 'bg-indigo-600 text-white font-bold shadow' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="flex items-center gap-2">
                                    @if($cat->icon)
                                        <span>{{ $cat->icon }}</span>
                                    @endif
                                    {{ $cat->name }}
                                </span>
                                <span class="text-xs {{ ($categorySlug ?? '') === $cat->slug ? 'text-indigo-200' : 'text-slate-500' }}">{{ $cat->courses_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            {{-- Main: Course Grid --}}
            <div class="flex-1">
                {{-- Active filters --}}
                @if(($search ?? false) || ($categorySlug ?? false))
                    <div class="flex items-center gap-2 mb-6 flex-wrap">
                        <span class="text-xs text-slate-400">Filter aktif:</span>
                        @if($categorySlug ?? false)
                            <a href="{{ route('courses.index', ['search' => $search ?? '']) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-300 bg-indigo-500/10 border border-indigo-500/30 rounded-lg hover:bg-indigo-500/20 transition-colors">
                                {{ $categories->firstWhere('slug', $categorySlug)?->name ?? $categorySlug }}
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endif
                        @if($search ?? false)
                            <a href="{{ route('courses.index', ['category' => $categorySlug ?? '']) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-violet-300 bg-violet-500/10 border border-violet-500/30 rounded-lg hover:bg-violet-500/20 transition-colors">
                                "{{ $search }}"
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Results count --}}
                <p class="text-sm text-slate-400 mb-6">
                    <span class="text-white font-bold">{{ $courses->total() }}</span> kursus ditemukan
                </p>

                {{-- Course Cards Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    @forelse($courses as $course)
                        <a href="{{ route('courses.show', $course->slug) }}"
                           class="group rounded-2xl bg-slate-900/80 border border-slate-800 shadow-lg hover:shadow-indigo-500/10 hover:border-indigo-500/30 overflow-hidden transition-all duration-300 hover:-translate-y-1 flex flex-col">
                            {{-- Thumbnail Placeholder --}}
                            <div class="h-40 bg-gradient-to-br from-indigo-900/60 to-violet-900/40 flex items-center justify-center relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                                <svg class="w-12 h-12 text-indigo-500/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                @if($course->category)
                                    <span class="absolute top-3 left-3 px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 backdrop-blur-sm z-10">
                                        {{ $course->category->icon ?? '' }} {{ $course->category->name }}
                                    </span>
                                @endif
                            </div>

                            {{-- Card Body --}}
                            <div class="p-5 flex-1 flex flex-col">
                                <h3 class="text-base font-bold text-white group-hover:text-indigo-400 transition-colors line-clamp-2">{{ $course->title }}</h3>
                                <p class="text-xs text-slate-400 mt-2 line-clamp-2 flex-1">{{ Str::limit($course->description, 100) }}</p>

                                {{-- Meta --}}
                                <div class="flex items-center gap-3 mt-4 pt-3 border-t border-slate-800/60 text-[11px] text-slate-400">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                        {{ $course->sections_count }} Bab
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        {{ $course->materials_count }} Materi
                                    </span>
                                </div>

                                {{-- Instructor --}}
                                <div class="flex items-center gap-2 mt-3">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-[10px] font-bold text-white">
                                        {{ substr($course->instructor->name ?? 'I', 0, 1) }}
                                    </div>
                                    <span class="text-xs text-slate-400">{{ $course->instructor->name ?? 'Instructor' }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="col-span-full py-16 text-center">
                            <div class="w-20 h-20 rounded-3xl bg-slate-800/50 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <p class="text-lg font-semibold text-slate-400">Belum ada kursus tersedia</p>
                            <p class="text-sm text-slate-500 mt-2">Kursus yang telah dipublikasikan oleh Instructor akan tampil di sini.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($courses->hasPages())
                    <div class="mt-8">
                        {{ $courses->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
