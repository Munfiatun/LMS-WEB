@extends('layouts.app')

@section('content')
@if($errors->any())
    <div role="alert" class="p-4 mb-4 rounded-lg bg-red-100 text-red-800">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
<div class="py-12" x-data="{ showModal: false }">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Kelola Kuis</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Buat dan atur kuis untuk kelas Anda.</p>
            </div>
            <button @click="showModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Kuis
            </button>
        </div>

        <form action="{{ route('instructor.quizzes.generate-ai') }}" method="POST" class="mb-6 p-6 space-y-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700" x-data="{ generating: false, fillPrompt(text) { \$refs.teacherPrompt.value = text; } }" @submit="generating = true">
            @csrf
            <input type="hidden" name="request_id" value="{{ old('request_id', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Generate Quiz dengan AI</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">AI akan membuat soal otomatis dari materi Slidebook berdasarkan instruksi Anda. Hasil menjadi draft dan wajib direview sebelum diterbitkan.</p>

            {{-- Materi Slidebook --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Materi Slidebook</label>
                <select name="slidebook_id" required class="w-full rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 p-2.5 text-sm">
                    <option value="">— Pilih Slidebook —</option>
                    @foreach($slidebooks as $slidebook)
                        <option value="{{ $slidebook->id }}" @selected(old('slidebook_id', request()->integer('slidebook_id')) == $slidebook->id)>{{ $slidebook->title }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Instruksi Guru (PRIMARY INPUT) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instruksi untuk AI</label>
                <textarea name="custom_instructions" x-ref="teacherPrompt" required rows="4" maxlength="2000" class="w-full rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 p-2.5 text-sm" placeholder="Contoh: Buatkan 10 soal pilihan ganda dari materi ini dengan tingkat kesulitan sedang. Fokuskan soal pada konsep utama dan pemahaman siswa.">{{ old('custom_instructions') }}</textarea>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span class="text-xs text-gray-400">Contoh:</span>
                    <button type="button" @click="fillPrompt('Buatkan 10 soal pilihan ganda dengan tingkat kesulitan sedang.')" class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer">10 soal PG sedang</button>
                    <button type="button" @click="fillPrompt('Buatkan soal yang berfokus pada pemahaman konsep, bukan hafalan.')" class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer">Fokus pemahaman</button>
                    <button type="button" @click="fillPrompt('Buatkan 5 soal mudah dan 5 soal sulit. Jangan membuat pertanyaan yang terlalu panjang.')" class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer">5 mudah + 5 sulit</button>
                    <button type="button" @click="fillPrompt('Buatkan 15 soal dari seluruh materi dengan 4 pilihan jawaban untuk mengevaluasi pemahaman siswa.')" class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer">15 soal evaluasi</button>
                </div>
            </div>

            {{-- Konfigurasi tambahan --}}
            <details class="text-sm">
                <summary class="cursor-pointer text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 select-none">⚙️ Konfigurasi tambahan (opsional)</summary>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Jumlah soal
                        <input type="number" name="total_questions" min="1" max="30" value="{{ old('total_questions', 10) }}" required class="w-full rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 p-2 mt-1">
                    </label>
                    <label class="text-sm text-gray-700 dark:text-gray-300">Difficulty
                        <select name="difficulty" class="w-full rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 p-2 mt-1">
                            @foreach(['easy', 'medium', 'hard', 'mixed'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty', 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm text-gray-700 dark:text-gray-300">Jenis soal
                        <select name="type" class="w-full rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 p-2 mt-1">
                            <option value="multiple_choice" @selected(old('type') === 'multiple_choice')>Pilihan Ganda</option>
                            <option value="true_false" @selected(old('type') === 'true_false')>Benar / Salah</option>
                        </select>
                    </label>
                </div>
            </details>

            {{-- Submit --}}
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="generating" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium disabled:opacity-50 hover:bg-indigo-700 transition-colors flex items-center gap-2">
                    <svg x-show="!generating" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <svg x-show="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="generating ? 'Gemini sedang membuat Quiz...' : 'Generate Quiz'">Generate Quiz</span>
                </button>
                <p x-show="generating" style="display: none" role="status" class="text-sm text-gray-500">Menyiapkan materi dan mengirim ke AI. Mohon tunggu...</p>
            </div>
        </form>

        <!-- Grid Kuis -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($quizzes as $quiz)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                    <div class="p-5 flex-grow">
                        <div class="flex justify-between items-start mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $quiz->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                {{ ucfirst($quiz->status) }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $quiz->total_questions }} Soal</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $quiz->title }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">{{ $quiz->description ?? 'Tidak ada deskripsi.' }}</p>
                        
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                <span>Durasi: {{ $quiz->duration_minutes ? $quiz->duration_minutes . ' Menit' : 'Tanpa batas' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Nilai Lulus: {{ $quiz->passing_score }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50 flex gap-2">
                        <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Kelola
                        </a>
                        <a href="{{ route('instructor.quizzes.results', $quiz) }}" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 rounded-lg text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                            Hasil
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum ada Kuis</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat kuis pertama Anda sekarang.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $quizzes->links() }}
        </div>
    </div>

    <!-- Modal Create Quiz -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showModal" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('instructor.quizzes.store') }}" method="POST">
                    @csrf
                    <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Buat Kuis Baru</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                                <select name="course_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Kuis</label>
                                <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Batas Waktu (Menit)</label>
                                    <input type="number" name="duration_minutes" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Kosongkan jika tak terbatas">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nilai Kelulusan</label>
                                    <input type="number" name="passing_score" required value="70" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Soal Kuis</label>
                                    <input type="number" name="total_questions" required value="10" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Batas Percobaan</label>
                                    <input type="number" name="max_attempts" required value="1" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="flex items-center space-x-4 pt-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="randomize_questions" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Acak Soal</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="randomize_options" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Acak Opsi (A,B,C,D)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Simpan & Buat
                        </button>
                        <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection