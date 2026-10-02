<details class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100" @if($question->needs_review) open @endif>
    <summary class="cursor-pointer font-medium">{{ $question->question_text }} — {{ $question->needs_review ? 'AI Generated Draft' : 'Terverifikasi' }}</summary>
    <p class="text-sm text-gray-500 mt-2">{{ $question->topic }} · {{ $question->difficulty }}</p>
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
