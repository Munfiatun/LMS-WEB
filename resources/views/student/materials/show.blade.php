@extends('layouts.app')

@php
    $title = $material->title;
    $breadcrumb = 'Materi';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
        <a href="{{ route('student.courses.continue', $course) }}" class="hover:text-white transition-colors">&larr; Kembali ke Daftar Kelas</a>
        <span class="text-slate-600">&bull;</span>
        <span class="text-slate-300">{{ $course->title }}</span>
    </div>

    <!-- Header Materi -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/30 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $material->title }}</h1>
                @if($material->description)
                    <p class="text-sm text-slate-400 mt-2">{{ $material->description }}</p>
                @endif
                
                <div class="flex items-center gap-4 mt-4">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-300 bg-slate-800/80 px-2.5 py-1 rounded-full border border-slate-700">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Estimasi {{ $material->duration_minutes }} menit
                    </span>
                </div>
            </div>
            
            @if(!$isCompleted)
                <form action="{{ route('student.materials.complete', $material) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Tandai Selesai
                    </button>
                </form>
            @else
                <div class="px-5 py-2.5 rounded-xl text-sm font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Materi Telah Selesai
                </div>
            @endif
        </div>
    </div>

    <!-- Konten Materi -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-lg prose prose-invert max-w-none">
                {!! $material->content ?? '<p class="text-slate-400">Tidak ada deskripsi konten.</p>' !!}
            </div>

            @if($material->slidebook && $material->slidebook->isPublished())
                <div class="p-6 rounded-2xl bg-indigo-950/20 border border-indigo-900/40 shadow-lg">
                    <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Slidebook Tersedia
                    </h3>
                    <p class="text-sm text-slate-400 mb-4">Materi ini memiliki slidebook interaktif yang dirangkum oleh AI untuk memudahkan pemahaman Anda.</p>
                    <a href="{{ route('student.slidebooks.show', $material->slidebook) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-colors">
                        Buka Slidebook
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                </div>
            @endif
        </div>
        
        <div class="space-y-6">
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-lg">
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">Navigasi Kursus</h3>
                
                <div class="space-y-2">
                    @foreach($course->sections as $section)
                        <div class="mb-4">
                            <h4 class="text-xs font-bold text-slate-400 mb-2">{{ $section->title }}</h4>
                            <div class="space-y-1">
                                @foreach($section->materials as $mat)
                                    @php
                                        $isCurrent = $mat->id === $material->id;
                                        $completed = auth()->user()->materialProgress()->where('learning_material_id', $mat->id)->where('status', 'completed')->exists();
                                    @endphp
                                    <a href="{{ route('student.materials.show', ['course' => $course, 'material' => $mat]) }}" 
                                       class="block px-3 py-2 rounded-lg text-sm transition-colors border {{ $isCurrent ? 'bg-indigo-500/10 border-indigo-500/30 text-indigo-300' : 'border-transparent text-slate-400 hover:bg-slate-800/50 hover:text-slate-300' }}">
                                        <div class="flex items-center gap-2">
                                            @if($completed)
                                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @else
                                                <div class="w-4 h-4 rounded-full border-2 border-slate-600 flex-shrink-0"></div>
                                            @endif
                                            <span class="truncate">{{ $mat->title }}</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    
                    @if($course->quizzes->count() > 0)
                        <div class="mt-6 pt-4 border-t border-slate-800">
                            <h4 class="text-xs font-bold text-slate-400 mb-2">Kuis Evaluasi</h4>
                            <div class="space-y-1">
                                @foreach($course->quizzes as $quiz)
                                    <a href="{{ route('student.quizzes.show', $quiz) }}" class="block px-3 py-2 rounded-lg text-sm border border-transparent text-slate-400 hover:bg-slate-800/50 hover:text-slate-300 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                            <span class="truncate">{{ $quiz->title }}</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
