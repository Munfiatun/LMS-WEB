@extends('layouts.app')

@php
    $title = 'Review Soal AI';
    $breadcrumb = 'Review Soal AI';
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">&larr; Kembali ke Bank Soal</a>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Review Soal Hasil Ekstraksi AI</h1>
            <p class="text-sm text-slate-400 mt-1">
                Terdapat <strong class="text-amber-400">{{ $questions->count() }} soal</strong> yang perlu diverifikasi secara manual. AI tidak menemukan kunci jawaban eksplisit sehingga kunci berikut merupakan hasil inferensi (tebakan).
            </p>
        </div>
    </div>

    @if(session('info'))
    <div class="bg-indigo-500/10 border border-indigo-500/30 rounded-xl p-4 flex gap-3 text-sm text-indigo-300">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <p>{{ session('info') }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6">
        @forelse($questions as $question)
            <div class="bg-slate-900/60 rounded-xl border border-amber-500/30 p-5 relative overflow-hidden" x-data="{ editing: false }">
                <!-- Status Badge -->
                <div class="absolute top-0 right-0 bg-amber-500/10 px-3 py-1 text-[10px] font-bold text-amber-400 rounded-bl-lg border-b border-l border-amber-500/20">
                    Menunggu Verifikasi Guru
                </div>

                <div class="flex gap-4">
                    <div class="shrink-0 w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-sm font-bold text-amber-500">
                        !
                    </div>
                    
                    <!-- View Mode -->
                    <div class="flex-1 space-y-4 pt-1" x-show="!editing">
                        <div class="text-sm text-slate-200">
                            {!! nl2br(e($question->question_text)) !!}
                        </div>
                        
                        <div class="space-y-2 mt-4">
                            @foreach($question->options as $option)
                                <div class="flex items-start gap-3 p-3 rounded-lg border {{ $option->is_correct ? 'bg-amber-500/10 border-amber-500/30' : 'bg-slate-950/50 border-slate-800' }}">
                                    <div class="mt-0.5 {{ $option->is_correct ? 'text-amber-400' : 'text-slate-500' }}">
                                        @if($option->is_correct)
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <div class="w-4 h-4 rounded-full border border-slate-600"></div>
                                        @endif
                                    </div>
                                    <div class="text-sm {{ $option->is_correct ? 'text-amber-100 font-medium' : 'text-slate-400' }}">
                                        {{ $option->option_text }}
                                        @if($option->is_correct)
                                            <span class="ml-2 text-[10px] bg-amber-500/20 text-amber-400 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Tebakan AI</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 mt-4 border-t border-slate-800/80">
                            <button @click="editing = true" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
                                Koreksi Jawaban
                            </button>
                            <form action="{{ route('instructor.questions.approve', $question) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 rounded-lg transition-colors shadow-lg shadow-amber-500/20 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Verifikasi & Setujui
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div class="flex-1 pt-1" x-show="editing" style="display: none;">
                        <form action="{{ route('instructor.questions.update', $question) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            
                            <input type="hidden" name="needs_review" value="0">
                            <input type="hidden" name="status" value="approved">
                            
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Pertanyaan</label>
                                <textarea name="question_text" rows="3" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ $question->question_text }}</textarea>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-slate-400">Pilihan Jawaban</label>
                                @foreach($question->options as $idx => $option)
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="correct_option" value="{{ $idx }}" {{ $option->is_correct ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-slate-950 border-slate-700 focus:ring-emerald-500">
                                    <div class="flex-1 flex gap-2">
                                        <span class="inline-flex items-center justify-center w-8 bg-slate-800 text-slate-300 rounded text-xs font-bold">{{ chr(65 + $idx) }}</span>
                                        <input type="text" name="options[{{ $idx }}]" value="{{ $option->option_text }}" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-sm text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">Tingkat Kesulitan</label>
                                    <select name="difficulty" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-sm text-white focus:border-amber-500">
                                        <option value="easy" @selected($question->difficulty === 'easy')>Easy</option>
                                        <option value="medium" @selected($question->difficulty === 'medium')>Medium</option>
                                        <option value="hard" @selected($question->difficulty === 'hard')>Hard</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">Bobot Nilai</label>
                                    <input type="number" name="points" value="{{ $question->points }}" min="1" max="100" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-sm text-white focus:border-amber-500">
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800/80">
                                <button type="button" @click="editing = false" class="px-4 py-2 text-sm font-medium text-slate-400 hover:text-white transition-colors">Batal</button>
                                <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors">
                                    Simpan & Setujui
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center rounded-2xl bg-slate-900/60 border border-slate-800">
                <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-base font-bold text-white">Semua Soal Telah Diverifikasi</h3>
                <p class="text-xs text-slate-400 mt-1 mb-4">Tidak ada lagi soal yang membutuhkan review manual.</p>
                <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Kembali ke Bank Soal
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
