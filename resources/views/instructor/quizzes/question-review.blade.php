@php
    $sourceLabel = match ($question->answer_source) {
        \App\Models\Question::SOURCE_EXPLICIT => 'Kunci eksplisit dari sumber',
        \App\Models\Question::SOURCE_MANUAL => 'Kunci ditetapkan guru',
        default => $question->needs_review ? 'Kunci hasil inferensi AI' : 'Inferensi AI diverifikasi guru',
    };
    $sourceClass = match ($question->answer_source) {
        \App\Models\Question::SOURCE_EXPLICIT => 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
        \App\Models\Question::SOURCE_MANUAL => 'border-indigo-500/20 bg-indigo-500/10 text-indigo-300',
        default => $question->needs_review
            ? 'border-amber-500/20 bg-amber-500/10 text-amber-300'
            : 'border-cyan-500/20 bg-cyan-500/10 text-cyan-300',
    };
@endphp

<details class="group overflow-hidden rounded-2xl border {{ $question->needs_review ? 'border-amber-500/20 bg-slate-950/55' : 'border-emerald-500/15 bg-slate-950/45' }}" @if($question->needs_review) open @endif>
    <summary class="flex cursor-pointer list-none items-start gap-4 p-5 marker:hidden">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $question->needs_review ? 'bg-amber-500/10 text-amber-300' : 'bg-emerald-500/10 text-emerald-300' }} text-sm font-extrabold">
            {{ $number ?? $question->order }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <p class="pr-4 text-sm font-semibold leading-6 text-slate-100">{{ $question->question_text }}</p>
                <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $question->needs_review ? 'border-amber-500/20 bg-amber-500/10 text-amber-300' : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' }}">
                    {{ $question->needs_review ? 'Perlu Review' : 'Terverifikasi' }}
                </span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $sourceClass }}">{{ $sourceLabel }}</span>
                <span class="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[10px] font-semibold text-slate-400">{{ $question->topic }}</span>
                <span class="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[10px] font-semibold text-slate-400">{{ ucfirst($question->difficulty) }}</span>
            </div>
        </div>
        <svg class="mt-2 h-4 w-4 shrink-0 text-slate-500 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </summary>

    <div class="border-t border-slate-800 px-5 pb-5 pt-4 sm:pl-[4.5rem]">
        @if($question->answer_source === \App\Models\Question::SOURCE_INFERRED && $question->needs_review)
            <div class="mb-4 rounded-xl border border-amber-500/15 bg-amber-500/5 px-4 py-3 text-xs leading-5 text-amber-200/90">
                Jawaban benar dipilih AI berdasarkan materi dan belum dianggap valid sampai guru memverifikasinya.
            </div>
        @endif

        @if(filled($question->source_excerpt))
            <div class="mb-5 overflow-hidden rounded-xl border border-cyan-500/15 bg-cyan-500/[0.04]">
                <div class="flex items-center justify-between gap-3 border-b border-cyan-500/10 px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-cyan-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        <span class="text-[11px] font-bold uppercase tracking-[0.15em] text-cyan-200">Referensi Materi</span>
                    </div>
                    @if($question->source_slide_number)
                        <span class="rounded-full border border-cyan-500/15 bg-cyan-500/10 px-2.5 py-1 text-[10px] font-bold text-cyan-200">Slide {{ $question->source_slide_number }}</span>
                    @endif
                </div>
                <blockquote class="px-4 py-3 text-xs leading-6 text-slate-300">“{{ $question->source_excerpt }}”</blockquote>
                <p class="border-t border-cyan-500/10 px-4 py-2 text-[10px] leading-5 text-slate-500">Cuplikan ini disimpan dari slide yang dirujuk AI saat quiz dibuat, sehingga guru dapat membandingkan soal dengan materi sumber.</p>
            </div>
        @endif

        <form action="{{ route('instructor.questions.update', $question) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-400">Pertanyaan</label>
                <textarea name="question_text" required rows="3" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ $question->question_text }}</textarea>
            </div>

            <fieldset>
                <legend class="mb-3 text-xs font-semibold text-slate-400">Pilihan jawaban <span class="font-normal text-slate-500">— pilih satu jawaban benar</span></legend>
                <div class="grid gap-2">
                    @foreach($question->options as $index => $option)
                        <label class="flex items-center gap-3 rounded-xl border {{ $option->is_correct ? 'border-emerald-500/25 bg-emerald-500/5' : 'border-slate-800 bg-slate-900/50' }} p-3 transition hover:border-slate-700">
                            <input type="radio" name="correct_option" value="{{ $index }}" @checked($option->is_correct) required aria-label="Jawaban benar pilihan {{ $index + 1 }}" class="h-4 w-4 border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-slate-950 text-[11px] font-bold text-slate-400">{{ chr(65 + $index) }}</span>
                            <input type="text" name="options[{{ $index }}]" value="{{ $option->option_text }}" required class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-slate-200 focus:ring-0" aria-label="Pilihan {{ $index + 1 }}">
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-4 lg:grid-cols-[1fr_180px]">
                <div>
                    <label class="mb-2 block text-xs font-semibold text-slate-400">Pembahasan</label>
                    <textarea name="explanation" rows="3" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ $question->explanation }}</textarea>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold text-slate-400">Tingkat Kesulitan</label>
                    <select name="difficulty" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-3 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach(['easy', 'medium', 'hard'] as $difficulty)
                            <option value="{{ $difficulty }}" @selected($question->difficulty === $difficulty)>{{ ucfirst($difficulty) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <input type="hidden" name="points" value="{{ $question->points }}">

            <div class="flex flex-col gap-3 border-t border-slate-800 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[11px] leading-5 text-slate-500">Menyimpan soal akan menandainya sebagai hasil verifikasi guru.</p>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500">
                    Simpan & Verifikasi Soal
                </button>
            </div>
        </form>
    </div>
</details>
