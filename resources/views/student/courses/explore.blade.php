@extends('layouts.app')

@php
    $title = 'Jelajah Kursus';
    $breadcrumb = 'Jelajah Kursus';
@endphp

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    Jelajah Kursus
                </h3>
                <p class="text-sm text-slate-400 mt-1">Temukan dan bergabung ke kelas pembelajaran baru.</p>
            </div>
            
            <form action="{{ route('student.courses.explore') }}" method="GET" class="relative w-full md:w-auto min-w-[300px]">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kursus atau kategori..." class="w-full pl-10 pr-4 py-2 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:ring-2 focus:ring-indigo-500 placeholder-slate-500">
                <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </form>
        </div>

        @if($courses->isEmpty())
            <div class="p-10 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-slate-800/60 text-slate-400 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                <p class="text-base font-medium text-slate-300">Belum ada kursus yang tersedia.</p>
                <p class="text-sm text-slate-500 mt-1">Coba gunakan kata kunci pencarian yang lain.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($courses as $course)
                    @php
                        $isEnrolled = in_array($course->id, $enrolledCourseIds);
                    @endphp
                    <div class="flex flex-col rounded-2xl bg-slate-800/40 border border-slate-700 hover:border-slate-600 overflow-hidden shadow-lg transition-all group">
                        <div class="aspect-video w-full bg-slate-900 relative">
                            @if($course->thumbnail)
                                <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-900/40 to-slate-900">
                                    <svg class="w-12 h-12 text-indigo-500/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                </div>
                            @endif
                            <div class="absolute top-3 left-3">
                                <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-full bg-slate-900/80 backdrop-blur text-slate-300 border border-slate-700/50">
                                    {{ $course->category?->name ?? 'Uncategorized' }}
                                </span>
                            </div>
                        </div>
                        <div class="p-5 flex flex-col flex-grow">
                            <h4 class="text-lg font-bold text-white mb-2 line-clamp-2"><a href="{{ route('courses.show', $course->slug) }}" class="hover:text-indigo-400 transition-colors">{{ $course->title }}</a></h4>
                            <p class="text-xs text-slate-400 mb-4 flex-grow line-clamp-2">{{ $course->description }}</p>
                            
                            <div class="flex items-center gap-2 text-[11px] text-slate-500 mb-5 pb-4 border-b border-slate-700/50">
                                <span>👨‍🏫 {{ $course->instructor->name }}</span>
                                <span>&bull;</span>
                                <span>📚 {{ $course->materials_count }} Materi</span>
                            </div>
                            
                            <div class="mt-auto">
                                @if($isEnrolled)
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-xs font-semibold text-emerald-400 flex items-center gap-1.5 bg-emerald-500/10 px-3 py-2 rounded-lg border border-emerald-500/20">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Sudah Bergabung
                                        </span>
                                        <a href="{{ route('student.courses.continue', $course) }}" class="flex-1 text-center px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-emerald-600/20">
                                            Buka Kelas
                                        </a>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('courses.show', $course->slug) }}" class="flex-1 text-center px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl transition-all border border-slate-700">
                                            Lihat Kelas
                                        </a>
                                        <button type="button" @click="$dispatch('open-enrollment', { courseSlug: '{{ $course->slug }}', courseTitle: '{{ addslashes($course->title) }}', courseId: {{ $course->id }} })" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20">
                                            Gabung Kelas
                                        </button>
                                    </div>
                                @endif
                            </div>
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

<!-- Alpine Modal Component for Enrollment -->
<div x-data="{ open: false, courseTitle: '', courseSlug: '', courseId: '', token: '' }" 
     @open-enrollment.window="open = true; courseTitle = $event.detail.courseTitle; courseSlug = $event.detail.courseSlug; courseId = $event.detail.courseId; token = ''; setTimeout(() => $refs.tokenInput.focus(), 50)"
     class="relative z-50">
    <div x-show="open" style="display: none;" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="open = false">
            <h3 class="text-lg font-bold text-white">Gabung ke Kelas</h3>
            
            <div class="p-3 bg-slate-800/50 rounded-xl border border-slate-700/50 mb-4">
                <p class="text-xs text-slate-400">Nama Kelas:</p>
                <p class="text-sm font-bold text-white" x-text="courseTitle"></p>
            </div>
            
            <form :action="`/student/courses/${courseId}/enroll`" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="enrollment_code" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Masukkan Token Kelas</label>
                    <input type="text" id="enrollment_code" name="enrollment_code" x-model="token" required x-ref="tokenInput"
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-center tracking-[0.2em] text-lg focus:ring-2 focus:ring-indigo-500 uppercase"
                           placeholder="TOKEN">
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="px-5 py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-colors">Batalkan</button>
                    <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center gap-2">
                        Gabung
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
