@php
    $sourceLabel = match ($question->answer_source) {
        \App\Models\Question::SOURCE_EXPLICIT => 'Kunci eksplisit dari sumber',
        \App\Models\Question::SOURCE_MANUAL => 'Kunci ditetapkan guru',
        default => $question->needs_review ? 'Kunci hasil inferensi AI' : 'Inferensi AI diverifikasi guru',
    };
    $sourceClass = match ($question->answer_source) {
        \App\Models\Question::SOURCE_EXPLICIT => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        \App\Models\Question::SOURCE_MANUAL => 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
        default => $question->needs_review
            ? 'bg-amber-500/10 text-amber-300 border-amber-500/20'
            : 'bg-cyan-500/10 text-cyan-300 border-cyan-500/20',
    };
@endphp

<details class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100" @if($question->needs_review) open @endif>
    <summary class="cursor-pointer font-medium">{{ $question->question_text }} — {{ $question->needs_review ? 'AI Generated Draft' : 'Terverifikasi' }}</summary>
    <div class="mt-2 flex flex-wrap items-center gap-2">
        <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $sourceClass }}">{{ $sourceLabel }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $question->topic }} · {{ $question->difficulty }}</span>
    </div>
    @if($question->answer_source === \App\Models\Question::SOURCE_INFERRED && $question->needs_review)
        <p class="mt-2 text-xs text-amber-500 dark:text-amber-300">Jawaban benar dipilih AI berdasarkan materi dan belum dianggap valid sampai guru memverifikasinya.</p>
    @endif
    <form action="{{ route('instructor.questions.update', $question) }}" method="POST" class="space-y-3 mt-4">
        @csrf
        @method('PUT')
        <label class="block text-sm">Pertanyaan
            <textarea name="question_text" required rows="3" class="w-full p-2 rounded-lg bg-gray-50 dark:bg-gray-700">{{ $question->question_text }}</textarea>
        </label>
        <fieldset class="space-y-2">
            <legend class="text-sm">Pilihan jawaban — pilih satu jawaban benar</legend>
            @foreach($question->options as $index => $option)
                <label class="flex items-center gap-3">
                    <input type="radio" name="correct_option" value="{{ $index }}" @checked($option->is_correct) required aria-label="Jawaban benar pilihan {{ $index + 1 }}">
                    <input type="text" name="options[{{ $index }}]" value="{{ $option->option_text }}" required class="w-full p-2 rounded-lg bg-gray-50 dark:bg-gray-700" aria-label="Pilihan {{ $index + 1 }}">
                </label>
            @endforeach
        </fieldset>
        <label class="block text-sm">Pembahasan
            <textarea name="explanation" rows="2" class="w-full p-2 rounded-lg bg-gray-50 dark:bg-gray-700">{{ $question->explanation }}</textarea>
        </label>
        <label class="block text-sm">Difficulty
            <select name="difficulty" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                @foreach(['easy', 'medium', 'hard'] as $difficulty)
                    <option value="{{ $difficulty }}" @selected($question->difficulty === $difficulty)>{{ ucfirst($difficulty) }}</option>
                @endforeach
            </select>
        </label>
        <input type="hidden" name="points" value="{{ $question->points }}">
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Simpan & Verifikasi Soal</button>
    </form>
</details>
