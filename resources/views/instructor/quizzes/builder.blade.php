@extends('layouts.app')

@section('content')
@if($errors->any())
    <div role="alert" class="p-4 mb-4 rounded-lg bg-red-100 text-red-800">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
<div class="py-12" x-data="quizBuilder()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Builder Kuis: {{ $quiz->title }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pilih soal dari bank soal untuk ditambahkan ke kuis ini.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Target Soal</p>
                    <p class="text-xl font-bold" :class="selectedQuestions.length === {{ $quiz->total_questions }} ? 'text-green-600' : 'text-indigo-600'">
                        <span x-text="selectedQuestions.length"></span> / {{ $quiz->total_questions }}
                    </p>
                </div>
                <form action="{{ route('instructor.quizzes.sync-questions', $quiz) }}" method="POST" id="syncForm">
                    @csrf
                    <template x-for="(q, index) in selectedQuestions" :key="q.id">
                        <div>
                            <input type="hidden" :name="`questions[${index}][id]`" :value="q.id">
                            <input type="hidden" :name="`questions[${index}][points]`" :value="q.points">
                            <input type="hidden" :name="`questions[${index}][order]`" :value="index + 1">
                        </div>
                    </template>
                    <button type="submit" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="selectedQuestions.length === 0">
                        Simpan & Sinkronisasi
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Kolom Kiri: Bank Soal -->
            <div class="lg:col-span-2 space-y-6">
                @forelse($questionBanks as $bank)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" x-data="{ open: false }">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center cursor-pointer" @click="open = !open">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $bank->title }}</h3>
                                <p class="text-sm text-gray-500">{{ $bank->questions->count() }} Soal Tersedia</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div x-show="open" class="divide-y divide-gray-200 dark:divide-gray-700" style="display: none;">
                            @foreach($bank->questions as $question)
                                <div class="p-4 flex items-start gap-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                    <div class="flex-grow">
                                        <div class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2 mb-2">
                                            {{ strip_tags($question->question_text) }}
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $question->type }}
                                        </span>
                                    </div>
                                    <button type="button" 
                                        @click="toggleQuestion({{ $question->id }}, {{ json_encode(strip_tags($question->question_text)) }}, '{{ $question->type }}')"
                                        class="flex-shrink-0 px-3 py-1 text-sm font-medium rounded-md border"
                                        :class="isSelected({{ $question->id }}) ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' : 'bg-white text-indigo-600 border-indigo-200 hover:bg-indigo-50'">
                                        <span x-text="isSelected({{ $question->id }}) ? 'Hapus' : 'Pilih'"></span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow border border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-gray-500 dark:text-gray-400">Tidak ada Bank Soal yang tersedia. Silakan buat dan ekstrak soal terlebih dahulu.</p>
                    </div>
                @endforelse
            </div>

            <!-- Kolom Kanan: Soal Terpilih -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 h-fit sticky top-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Soal Terpilih (<span x-text="selectedQuestions.length"></span>)</h3>
                </div>
                <div class="p-4 overflow-y-auto max-h-[60vh]">
                    <template x-if="selectedQuestions.length === 0">
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                            Belum ada soal yang dipilih.
                        </div>
                    </template>
                    <ul class="space-y-3">
                        <template x-for="(q, index) in selectedQuestions" :key="q.id">
                            <li class="bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-lg p-3 relative group">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded" x-text="index + 1"></span>
                                    <button @click="removeQuestion(q.id)" type="button" class="text-gray-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2 mb-2" x-text="q.text"></p>
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-xs text-gray-500" x-text="q.type"></span>
                                    <div class="flex items-center gap-1">
                                        <label class="text-xs text-gray-500">Poin:</label>
                                        <input type="number" x-model="q.points" min="1" class="w-16 text-xs p-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('quizBuilder', () => ({
        selectedQuestions: {{ \Illuminate\Support\Js::from($selectedQuestions) }},

        isSelected(id) {
            return this.selectedQuestions.some(q => q.id === id);
        },
        
        toggleQuestion(id, text, type) {
            if (this.isSelected(id)) {
                this.removeQuestion(id);
            } else {
                this.selectedQuestions.push({
                    id: id,
                    text: text,
                    type: type,
                    points: 10
                });
            }
        },
        
        removeQuestion(id) {
            this.selectedQuestions = this.selectedQuestions.filter(q => q.id !== id);
        }
    }))
})
</script>
@endsection