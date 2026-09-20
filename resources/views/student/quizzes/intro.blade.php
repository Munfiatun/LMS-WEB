@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="bg-indigo-600 px-8 py-10 text-white text-center">
                <h2 class="text-3xl font-bold mb-4">{{ $quiz->title }}</h2>
                <p class="text-indigo-100">{{ $quiz->course->title }}</p>
            </div>
            
            <div class="p-8">
                <div class="prose dark:prose-invert max-w-none mb-8">
                    <h3 class="text-xl font-semibold mb-2">Deskripsi Kuis</h3>
                    <p>{{ $quiz->description ?? 'Tidak ada deskripsi.' }}</p>
                    
                    @if($quiz->instructions)
                        <h3 class="text-xl font-semibold mt-6 mb-2">Instruksi</h3>
                        <p>{{ $quiz->instructions }}</p>
                    @endif
                </div>

                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Durasi</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' Menit' : 'Tanpa batas' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Soal</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $quiz->total_questions }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Nilai Lulus</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $quiz->passing_score }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Batas Percobaan</p>
                            <p class="text-xl font-bold {{ $attemptsCount >= $quiz->max_attempts ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                {{ $attemptsCount }} / {{ $quiz->max_attempts }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    @if($activeAttempt)
                        <a href="{{ route('student.quizzes.take', ['quiz' => $quiz->id, 'attempt' => $activeAttempt->id]) }}" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg transition-transform transform hover:scale-105">
                            Lanjutkan Ujian (Sedang Berjalan)
                        </a>
                    @elseif($attemptsCount >= $quiz->max_attempts)
                        <div class="text-center">
                            <p class="text-red-600 font-medium mb-2">Anda telah mencapai batas maksimal percobaan ujian ini.</p>
                            <a href="{{ route('student.quizzes.result', ['quiz' => $quiz->id, 'attempt' => App\Models\QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', auth()->id())->latest()->first()->id]) }}" class="text-indigo-600 hover:underline font-medium">Lihat Hasil Terakhir</a>
                        </div>
                    @else
                        <form action="{{ route('student.quizzes.start', $quiz) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg transition-transform transform hover:scale-105" onclick="return confirm('Apakah Anda yakin ingin memulai ujian sekarang? Timer akan mulai berjalan setelah Anda menekan OK.')">
                                Mulai Ujian Sekarang
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection