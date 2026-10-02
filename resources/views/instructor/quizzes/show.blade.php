@extends('layouts.app')

@section('content')
@if($errors->any())
    <div role="alert" class="p-4 mb-4 rounded-lg bg-red-100 text-red-800">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Header & Detail -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $quiz->title }}</h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $quiz->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($quiz->status) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kelas: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $quiz->course->title }}</span></p>
                    </div>
                    <div class="mt-4 md:mt-0 flex gap-3">
                        <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Buka Builder
                        </a>
                        
                        @if($quiz->status === 'draft')
                            <form action="{{ route('instructor.quizzes.publish', $quiz) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Terbitkan Kuis
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Soal Kuis (Target)</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $quiz->total_questions }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Soal Tersinkronisasi</dt>
                        <dd class="mt-1 text-2xl font-semibold text-{{ $quiz->quizQuestions->count() < $quiz->total_questions ? 'red' : 'green' }}-600">{{ $quiz->quizQuestions->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Durasi</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' M' : '∞' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nilai Kelulusan</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $quiz->passing_score }}</dd>
                    </div>
                </div>
            </div>
        </div>

        @if($quiz->status === 'draft')
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl space-y-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Review Quiz</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Periksa dan simpan setiap soal untuk memverifikasi jawabannya. Gunakan Builder untuk menghapus atau memilih soal, lalu Terbitkan Kuis setelah selesai.</p>
                @foreach($quiz->quizQuestions->pluck('question.questionBank')->unique('id') as $bank)
                    <a href="{{ route('instructor.question-banks.show', $bank) }}" class="block text-indigo-500">Tambah soal manual ke {{ $bank->title }}, lalu pilih melalui Builder</a>
                @endforeach
                @foreach($quiz->quizQuestions as $quizQuestion)
                    @include('instructor.quizzes.question-review', ['question' => $quizQuestion->question])
                @endforeach
            </div>
        @endif

        <!-- Daftar Soal Tersinkronisasi -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Daftar Soal Saat Ini</h3>
                @if($quiz->quizQuestions->count() < $quiz->total_questions && $quiz->status === 'draft')
                    <span class="text-sm text-red-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Butuh {{ $quiz->total_questions - $quiz->quizQuestions->count() }} soal lagi
                    </span>
                @endif
            </div>
            
            @if($quiz->quizQuestions->count() > 0)
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($quiz->quizQuestions as $quizQuestion)
                        <li class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-750">
                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center font-bold">
                                {{ $quizQuestion->order }}
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1 line-clamp-2">{{ strip_tags($quizQuestion->question->question_text) }}</p>
                                <div class="flex items-center gap-3 text-xs text-gray-500">
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $quizQuestion->question->type }}</span>
                                    <span>Poin: <strong class="text-gray-900 dark:text-gray-300">{{ $quizQuestion->points }}</strong></span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum Ada Soal</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Silakan buka Builder Kuis untuk memilih dan mensinkronisasi soal dari Bank Soal Anda.</p>
                    <div class="mt-6">
                        <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Buka Builder Kuis
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection