@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Hasil Ujian</h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $quiz->title }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden mb-8">
            <div class="p-8">
                <!-- Status Kelulusan -->
                <div class="text-center mb-10">
                    @if($attempt->isPassed())
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-4">
                            <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white">SELAMAT, ANDA LULUS!</h3>
                        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Anda telah memenuhi standar kelulusan untuk kuis ini.</p>
                    @else
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100 mb-4">
                            <svg class="h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white">ANDA BELUM LULUS</h3>
                        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">{{ $attempt->status === 'expired' ? 'Waktu ujian telah habis. Attempt ini tidak dihitung sebagai kelulusan.' : 'Nilai Anda masih di bawah standar kelulusan ('.$quiz->passing_score.'%).' }}</p>
                    @endif
                </div>

                <!-- Ringkasan Nilai -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-900 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-8">
                    <div class="text-center p-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Persentase Nilai</p>
                        <p class="text-4xl font-black {{ $attempt->isPassed() ? 'text-green-600' : 'text-red-600' }}">
                            {{ round($attempt->percentage, 2) }}%
                        </p>
                    </div>
                    <div class="text-center p-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Skor Poin</p>
                        <p class="text-4xl font-black text-indigo-600 dark:text-indigo-400">
                            {{ $attempt->score }}
                        </p>
                    </div>
                </div>

                <!-- Statistik Detail -->
                <div class="grid grid-cols-3 gap-4 text-center border-t border-b border-gray-200 dark:border-gray-700 py-6 mb-8">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jawaban Benar</p>
                        <p class="text-2xl font-bold text-green-600">{{ $attempt->correct_count }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jawaban Salah/Kosong</p>
                        <p class="text-2xl font-bold text-red-600">{{ $attempt->wrong_count }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Waktu Pengerjaan</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            @if($attempt->duration_seconds)
                                {{ floor($attempt->duration_seconds / 60) }}m {{ $attempt->duration_seconds % 60 }}s
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Navigasi -->
                <div class="flex justify-center gap-4">
                    <a href="{{ route('student.dashboard') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                        Kembali ke Dashboard
                    </a>
                    
                    @if(! $attempt->isPassed() && $quiz->attempts()->where('student_id', auth()->id())->count() < $quiz->max_attempts)
                        <a href="{{ route('student.quizzes.show', $quiz) }}" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            Coba Lagi Kuis Ini
                        </a>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection