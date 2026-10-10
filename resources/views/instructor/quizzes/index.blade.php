@extends('layouts.app')

@php
    $title = 'Kuis';
    $breadcrumb = 'Kuis';
@endphp

@section('content')
<div class="space-y-6" x-data="{ showModal: false }">
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-300">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <a href="{{ route('instructor.question-banks.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-900/70 px-2.5 py-1 text-[11px] font-semibold text-slate-400 transition-colors hover:border-indigo-500/40 hover:text-indigo-300">
                    <span aria-hidden="true">&larr;</span> Bank Soal
                </a>
                <span class="rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-2.5 py-1 text-[11px] font-bold text-indigo-300">Manajemen Kuis</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Kelola Kuis</h1>
            <p class="mt-1 text-sm text-slate-400">Buat assessment manual atau hasilkan draft soal dari Slidebook menggunakan AI.</p>
        </div>

        <button @click="showModal = true" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-950/40 transition hover:from-indigo-500 hover:to-violet-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Buat Kuis Manual
        </button>
    </div>

    {{-- AI Generator --}}
    <form action="{{ route('instructor.quizzes.generate-ai') }}" method="POST"
          class="relative overflow-hidden rounded-2xl border border-indigo-500/20 bg-slate-900/80 shadow-xl"
          x-data="{ generating: false, fillPrompt(text) { $refs.teacherPrompt.value = text; $refs.teacherPrompt.focus(); } }"
          @submit="generating = true">
        @csrf
        <input type="hidden" name="request_id" value="{{ old('request_id', (string) \Illuminate\Support\Str::uuid()) }}">

        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
        <div class="relative p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-indigo-500/30 bg-indigo-500/10 text-indigo-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-extrabold text-white">Generate Quiz dengan AI</h2>
                            <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-300">Draft + Review Guru</span>
                        </div>
                        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-400">AI membuat soal hanya dari materi Slidebook. Hasil tidak langsung terbit dan tetap harus diverifikasi guru sebelum digunakan siswa.</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Materi Slidebook</label>
                    <select name="slidebook_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">— Pilih Slidebook —</option>
                        @foreach($slidebooks as $slidebook)
                            <option value="{{ $slidebook->id }}" @selected(old('slidebook_id', request()->integer('slidebook_id')) == $slidebook->id)>{{ $slidebook->title }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-500">Slidebook menjadi sumber utama pertanyaan dan jawaban AI.</p>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Instruksi untuk AI</label>
                    <textarea name="custom_instructions" x-ref="teacherPrompt" required rows="4" maxlength="2000"
                              class="w-full resize-y rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm leading-relaxed text-slate-100 placeholder:text-slate-600 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                              placeholder="Contoh: Buatkan 10 soal pilihan ganda tingkat sedang. Fokus pada pemahaman konsep utama, bukan hafalan.">{{ old('custom_instructions') }}</textarea>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-semibold text-slate-500">Prompt cepat:</span>
                        <button type="button" @click="fillPrompt('Buatkan 10 soal pilihan ganda dengan tingkat kesulitan sedang.')" class="rounded-full border border-slate-700 bg-slate-800/70 px-2.5 py-1 text-[11px] font-medium text-slate-300 transition hover:border-indigo-500/50 hover:text-indigo-300">10 soal PG sedang</button>
                        <button type="button" @click="fillPrompt('Buatkan soal yang berfokus pada pemahaman konsep, bukan hafalan.')" class="rounded-full border border-slate-700 bg-slate-800/70 px-2.5 py-1 text-[11px] font-medium text-slate-300 transition hover:border-indigo-500/50 hover:text-indigo-300">Fokus pemahaman</button>
                        <button type="button" @click="fillPrompt('Buatkan 5 soal mudah dan 5 soal sulit. Jangan membuat pertanyaan yang terlalu panjang.')" class="rounded-full border border-slate-700 bg-slate-800/70 px-2.5 py-1 text-[11px] font-medium text-slate-300 transition hover:border-indigo-500/50 hover:text-indigo-300">5 mudah + 5 sulit</button>
                        <button type="button" @click="fillPrompt('Buatkan 15 soal dari seluruh materi dengan 4 pilihan jawaban untuk mengevaluasi pemahaman siswa.')" class="rounded-full border border-slate-700 bg-slate-800/70 px-2.5 py-1 text-[11px] font-medium text-slate-300 transition hover:border-indigo-500/50 hover:text-indigo-300">15 soal evaluasi</button>
                    </div>
                </div>
            </div>

            <details class="mt-5 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/40 text-sm">
                <summary class="cursor-pointer select-none px-4 py-3 font-semibold text-slate-300 transition hover:bg-slate-800/50 hover:text-white">Konfigurasi tambahan</summary>
                <div class="grid grid-cols-1 gap-4 border-t border-slate-800 p-4 md:grid-cols-3">
                    <label class="text-xs font-semibold text-slate-400">Jumlah soal
                        <input type="number" name="total_questions" min="2" max="20" value="{{ old('total_questions', 10) }}" required class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                        <span class="mt-1 block text-[11px] font-normal text-slate-500">2–20 soal per generasi.</span>
                    </label>
                    <label class="text-xs font-semibold text-slate-400">Tingkat Kesulitan
                        <select name="difficulty" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                            @foreach(['easy', 'medium', 'hard', 'mixed'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty', 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-400">Jenis Soal
                        <select name="type" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                            <option value="multiple_choice" @selected(old('type') === 'multiple_choice')>Pilihan Ganda</option>
                            <option value="true_false" @selected(old('type') === 'true_false')>Benar / Salah</option>
                        </select>
                    </label>
                </div>
            </details>

            <div class="mt-5 flex flex-col gap-3 border-t border-slate-800 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                    Jawaban AI tetap memerlukan verifikasi guru sebelum publikasi.
                </div>

                <button type="submit" :disabled="generating" class="inline-flex min-w-44 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-950/30 transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="!generating" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7" /></svg>
                    <svg x-show="generating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="generating ? 'Sedang membuat...' : 'Generate Quiz'">Generate Quiz</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Quiz list --}}
    <section class="space-y-4">
        <div class="flex items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-300">Assessment Library</p>
                <h2 class="mt-1 text-lg font-bold text-white">Daftar Kuis</h2>
                <p class="mt-1 text-xs text-slate-500">Kelola draft, publikasi, dan hasil pengerjaan siswa.</p>
            </div>
            <span class="rounded-full border border-slate-800 bg-slate-900 px-3 py-1 text-xs font-semibold text-slate-400">{{ $quizzes->total() }} kuis</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($quizzes as $quiz)
                <article class="group flex min-h-[230px] flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/75 shadow-lg transition hover:-translate-y-0.5 hover:border-indigo-500/30 hover:shadow-indigo-950/20">
                    <div class="flex-1 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $quiz->status === 'published' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-300' }}">
                                {{ $quiz->status === 'published' ? 'Published' : 'Draft' }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500">{{ $quiz->total_questions }} soal</span>
                        </div>

                        <h3 class="mt-4 text-lg font-bold leading-snug text-white transition group-hover:text-indigo-300">{{ $quiz->title }}</h3>
                        <p class="mt-1 text-xs font-medium text-indigo-300/80">{{ $quiz->course->title }}</p>
                        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-slate-400">{{ $quiz->description ?? 'Tidak ada deskripsi.' }}</p>

                        <div class="mt-5 grid grid-cols-2 gap-2">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Durasi</p>
                                <p class="mt-1 text-sm font-semibold text-slate-200">{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' menit' : 'Tanpa batas' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Nilai Lulus</p>
                                <p class="mt-1 text-sm font-semibold text-slate-200">{{ $quiz->passing_score }}%</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 border-t border-slate-800 bg-slate-950/50 p-4">
                        <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="flex-1 rounded-lg bg-indigo-600 px-3 py-2 text-center text-xs font-bold text-white transition hover:bg-indigo-500">Kelola</a>
                        <a href="{{ route('instructor.quizzes.results', $quiz) }}" class="flex-1 rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2 text-center text-xs font-bold text-slate-200 transition hover:border-indigo-500/40 hover:text-indigo-300">Hasil</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-800 text-slate-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <h3 class="mt-3 text-base font-bold text-white">Belum ada Kuis</h3>
                    <p class="mt-1 text-sm text-slate-400">Buat kuis manual atau gunakan generator AI untuk memulai assessment.</p>
                </div>
            @endforelse
        </div>

        @if($quizzes->hasPages())
            <div class="pt-2">{{ $quizzes->links() }}</div>
        @endif
    </section>

    {{-- Manual quiz modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex min-h-screen items-end justify-center px-4 pb-8 pt-4 sm:items-center">
            <div class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm" @click="showModal = false"></div>

            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl">
                <form action="{{ route('instructor.quizzes.store') }}" method="POST">
                    @csrf
                    <div class="border-b border-slate-800 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-white">Buat Kuis Manual</h3>
                                <p class="mt-1 text-xs text-slate-400">Atur struktur awal kuis, lalu tambahkan soal melalui Builder.</p>
                            </div>
                            <button type="button" @click="showModal = false" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-white" aria-label="Tutup">✕</button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <label class="block text-xs font-semibold text-slate-400">Kursus
                            <select name="course_id" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block text-xs font-semibold text-slate-400">Judul Kuis
                            <input type="text" name="title" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                        </label>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block text-xs font-semibold text-slate-400">Batas Waktu (Menit)
                                <input type="number" name="duration_minutes" min="1" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500" placeholder="Opsional">
                            </label>
                            <label class="block text-xs font-semibold text-slate-400">Nilai Kelulusan
                                <input type="number" name="passing_score" required value="70" min="0" max="100" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block text-xs font-semibold text-slate-400">Total Soal Kuis
                                <input type="number" name="total_questions" required value="10" min="1" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                            </label>
                            <label class="block text-xs font-semibold text-slate-400">Batas Percobaan
                                <input type="number" name="max_attempts" required value="1" min="1" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                            </label>
                        </div>

                        <div class="flex flex-wrap gap-4 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-300">
                                <input type="checkbox" name="randomize_questions" value="1" class="rounded border-slate-600 bg-slate-900 text-indigo-600 focus:ring-indigo-500">
                                Acak Soal
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-300">
                                <input type="checkbox" name="randomize_options" value="1" class="rounded border-slate-600 bg-slate-900 text-indigo-600 focus:ring-indigo-500">
                                Acak Opsi
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-800 bg-slate-950/40 px-6 py-4">
                        <button type="button" @click="showModal = false" class="rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:bg-slate-800">Batal</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">Simpan & Buat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
