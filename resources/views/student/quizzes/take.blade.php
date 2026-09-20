@extends('layouts.app')

@section('content')
<div class="py-6" x-data="quizExam()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <form action="{{ route('student.quizzes.submit', ['quiz' => $quiz->id, 'attempt' => $attempt->id]) }}" method="POST" id="examForm">
            @csrf
            
            <div class="flex flex-col lg:flex-row gap-6">
                
                <!-- Main Content: Soal -->
                <div class="lg:w-3/4 space-y-6">
                    @foreach($attempt->attemptQuestions as $index => $attemptQuestion)
                        <div x-show="currentQuestion === {{ $index }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" style="display: none;">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Soal No. {{ $index + 1 }}</h3>
                                <span class="text-sm font-medium text-gray-500 bg-gray-200 dark:bg-gray-700 px-2.5 py-0.5 rounded">
                                    {{ $attemptQuestion->question->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar/Salah' }}
                                </span>
                            </div>
                            <div class="p-6">
                                <div class="prose dark:prose-invert max-w-none mb-8 text-lg text-gray-900 dark:text-gray-100">
                                    {!! $attemptQuestion->question->question_text !!}
                                </div>
                                
                                <div class="space-y-3">
                                    @foreach($attemptQuestion->attemptOptions as $attemptOption)
                                        <label class="flex items-center p-4 border rounded-xl cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors"
                                            :class="answers[{{ $attemptQuestion->question_id }}] == {{ $attemptOption->option_id }} ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/40 ring-1 ring-indigo-500' : 'border-gray-300 dark:border-gray-600'">
                                            <input type="radio" 
                                                name="answers[{{ $attemptQuestion->question_id }}]" 
                                                value="{{ $attemptOption->option_id }}"
                                                x-model="answers[{{ $attemptQuestion->question_id }}]"
                                                class="w-5 h-5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                            <span class="ml-4 text-gray-700 dark:text-gray-300 flex-grow prose dark:prose-invert max-w-none">
                                                {!! $attemptOption->option->option_text !!}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Navigasi Bawah -->
                    <div class="flex justify-between items-center mt-6">
                        <button type="button" 
                            @click="prevQuestion" 
                            :disabled="currentQuestion === 0"
                            class="px-6 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Sebelumnya
                        </button>
                        
                        <button type="button" 
                            @click="nextQuestion" 
                            x-show="currentQuestion < {{ $attempt->attemptQuestions->count() - 1 }}"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            Selanjutnya
                        </button>

                        <button type="submit" 
                            x-show="currentQuestion === {{ $attempt->attemptQuestions->count() - 1 }}"
                            class="px-8 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-colors shadow-lg"
                            onclick="return confirm('Apakah Anda yakin ingin menyelesaikan dan mengumpulkan ujian ini? Anda tidak bisa mengubah jawaban setelah ini.')">
                            Kumpulkan Ujian
                        </button>
                    </div>
                </div>

                <!-- Sidebar Kanan: Timer & Navigasi -->
                <div class="lg:w-1/4 space-y-6">
                    <!-- Timer -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center sticky top-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Sisa Waktu</h4>
                        <div class="text-4xl font-black font-mono tracking-wider" :class="timeRemaining <= 300 ? 'text-red-600 animate-pulse' : 'text-gray-900 dark:text-white'" x-text="formattedTime">
                            --:--:--
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="submit" class="w-full px-4 py-2 bg-gray-800 dark:bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-900 dark:hover:bg-gray-600 transition-colors" onclick="return confirm('Kumpulkan ujian sekarang?')">
                                Kumpulkan Sekarang
                            </button>
                        </div>
                    </div>

                    <!-- Peta Soal -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-48">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Navigasi Soal</h4>
                        <div class="grid grid-cols-5 gap-2">
                            @foreach($attempt->attemptQuestions as $index => $attemptQuestion)
                                <button type="button" 
                                    @click="currentQuestion = {{ $index }}"
                                    class="h-10 w-full flex items-center justify-center rounded-md font-medium text-sm transition-colors border"
                                    :class="{
                                        'bg-indigo-600 text-white border-indigo-600 shadow-md': currentQuestion === {{ $index }},
                                        'bg-green-100 text-green-800 border-green-200': answers[{{ $attemptQuestion->question_id }}] && currentQuestion !== {{ $index }},
                                        'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700': !answers[{{ $attemptQuestion->question_id }}] && currentQuestion !== {{ $index }}
                                    }">
                                    {{ $index + 1 }}
                                </button>
                            @endforeach
                        </div>
                        
                        <div class="mt-6 flex flex-col gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded bg-indigo-600"></div> Soal saat ini
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded bg-green-100 border border-green-200"></div> Sudah dijawab
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded bg-white border border-gray-300"></div> Belum dijawab
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('quizExam', () => ({
        currentQuestion: 0,
        totalQuestions: {{ $attempt->attemptQuestions->count() }},
        answers: {},
        timeRemaining: {{ $attempt->expires_at ? now()->diffInSeconds($attempt->expires_at, false) : 99999999 }},
        timerInterval: null,
        
        init() {
            // Restore previous answers from session storage if exists
            const savedAnswers = sessionStorage.getItem('quiz_attempt_{{ $attempt->id }}');
            if (savedAnswers) {
                this.answers = JSON.parse(savedAnswers);
            }

            // Watch for answer changes to save automatically
            this.$watch('answers', value => {
                sessionStorage.setItem('quiz_attempt_{{ $attempt->id }}', JSON.stringify(value));
            });

            // Start Timer
            if (this.timeRemaining > 0 && this.timeRemaining < 999999) {
                this.timerInterval = setInterval(() => {
                    this.timeRemaining--;
                    if (this.timeRemaining <= 0) {
                        clearInterval(this.timerInterval);
                        alert('Waktu ujian telah habis! Jawaban Anda akan otomatis dikumpulkan.');
                        document.getElementById('examForm').submit();
                    }
                }, 1000);
            }
        },

        nextQuestion() {
            if (this.currentQuestion < this.totalQuestions - 1) {
                this.currentQuestion++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevQuestion() {
            if (this.currentQuestion > 0) {
                this.currentQuestion--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        get formattedTime() {
            if (this.timeRemaining >= 999999) return 'Tanpa Batas';
            if (this.timeRemaining <= 0) return '00:00:00';
            
            const h = Math.floor(this.timeRemaining / 3600).toString().padStart(2, '0');
            const m = Math.floor((this.timeRemaining % 3600) / 60).toString().padStart(2, '0');
            const s = Math.floor(this.timeRemaining % 60).toString().padStart(2, '0');
            
            return `${h}:${m}:${s}`;
        }
    }))
})
</script>
@endsection