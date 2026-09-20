@extends('layouts.guest')

@php
    $title = $slidebook->title . ' — Presentasi Slidebook';
@endphp

@section('content')
<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-indigo-500/30"
     x-data="studentSlidePresentation(@js($slides))"
     x-on:keydown.window.left="prevSlide()"
     x-on:keydown.window.right="nextSlide()"
     x-on:keydown.window.space.prevent="nextSlide()"
     x-on:keydown.window.f="toggleFullscreen()">

    {{-- Top Presentation Navigation Bar --}}
    <header class="p-4 md:px-8 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md flex items-center justify-between z-20">
        <div class="flex items-center gap-3">
            <a href="{{ route('courses.show', $course->slug) }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors flex items-center gap-1 text-xs font-semibold">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                <span class="hidden sm:inline">Kembali ke Kursus</span>
            </a>
            <div class="border-l border-slate-800 pl-3">
                <h1 class="text-xs sm:text-sm font-bold text-white truncate max-w-xs md:max-w-md">{{ $slidebook->title }}</h1>
                <p class="text-[11px] text-slate-400 truncate">{{ $course->title }} &bull; {{ $material->title }}</p>
            </div>
        </div>

        {{-- Slide Index & Controls --}}
        <div class="flex items-center gap-3">
            <span class="text-xs font-mono font-bold text-indigo-400 bg-indigo-950/60 px-2.5 py-1 rounded-lg border border-indigo-500/30">
                <span x-text="currentIndex + 1"></span> / <span x-text="slides.length"></span>
            </span>

            {{-- Fullscreen Button --}}
            <button type="button" @click="toggleFullscreen()" class="p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 transition-colors" title="Toggle Fullscreen (F)">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
            </button>
        </div>
    </header>

    {{-- Step Progress Bar --}}
    <div class="w-full bg-slate-900 h-1">
        <div class="bg-gradient-to-r from-indigo-500 to-violet-500 h-1 transition-all duration-300"
             :style="'width: ' + ((currentIndex + 1) / slides.length * 100) + '%'"></div>
    </div>

    {{-- Main Slide Stage (Center Viewport) --}}
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8 md:p-12 relative overflow-hidden" id="presentationContainer">
        {{-- Ambient Aura Gradients --}}
        <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-violet-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <template x-if="currentSlide">
            <div class="w-full max-w-4xl bg-gradient-to-b from-slate-900/90 to-slate-950/90 border border-slate-800/90 rounded-3xl p-6 sm:p-10 md:p-12 shadow-2xl backdrop-blur-xl relative transition-all duration-300">
                {{-- Slide Order Pill --}}
                <div class="flex items-center justify-between gap-4 mb-6">
                    <span class="text-[11px] font-mono uppercase tracking-widest font-bold text-indigo-400 bg-indigo-950/50 px-3 py-1 rounded-full border border-indigo-500/30">
                        Slide <span x-text="currentSlide.order"></span>
                    </span>

                    <span class="text-xs text-slate-500 hidden sm:inline" x-text="'Materi #' + currentSlide.order + ' dari ' + slides.length"></span>
                </div>

                {{-- Slide Title & Subtitle --}}
                <div class="mb-6 space-y-2">
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight leading-tight" x-text="currentSlide.title"></h2>
                    <template x-if="currentSlide.subtitle">
                        <p class="text-sm sm:text-base text-indigo-300/80 font-medium" x-text="currentSlide.subtitle"></p>
                    </template>
                </div>

                {{-- Slide Content (Formatted text) --}}
                <div class="text-slate-200 text-sm sm:text-base leading-relaxed whitespace-pre-wrap bg-slate-950/40 p-6 rounded-2xl border border-slate-800/60 font-sans"
                     x-text="currentSlide.content">
                </div>

                {{-- Key Takeaway / Summary Box --}}
                <template x-if="currentSlide.summary">
                    <div class="mt-6 p-4 rounded-2xl bg-indigo-950/30 border border-indigo-500/25 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center flex-shrink-0 border border-indigo-500/30">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-300 block mb-0.5">Intisari Penting</span>
                            <p class="text-xs sm:text-sm text-slate-300 leading-snug" x-text="currentSlide.summary"></p>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </main>

    {{-- Bottom Controller Bar --}}
    <footer class="p-4 md:px-8 border-t border-slate-800/80 bg-slate-900/60 backdrop-blur-md flex items-center justify-between z-20">
        {{-- Previous Button --}}
        <button type="button" @click="prevSlide()" :disabled="currentIndex === 0"
                :class="currentIndex === 0 ? 'opacity-30 cursor-not-allowed bg-slate-900 text-slate-600' : 'bg-slate-800 hover:bg-slate-700 text-white'"
                class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            <span>Sebelumnya</span>
        </button>

        {{-- Dots / Navigation Indicators --}}
        <div class="hidden sm:flex items-center gap-1.5 overflow-x-auto max-w-xs md:max-w-md px-2">
            <template x-for="(slide, index) in slides" :key="slide.id">
                <button type="button" @click="goToSlide(index)"
                        :class="index === currentIndex ? 'w-6 bg-indigo-500' : 'w-2 bg-slate-800 hover:bg-slate-700'"
                        class="h-2 rounded-full transition-all duration-200"
                        :title="'Lompat ke Slide ' + (index + 1)"></button>
            </template>
        </div>

        {{-- Next or Finish Button --}}
        <button type="button" @click="nextSlide()"
                class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow-lg shadow-indigo-600/25 flex items-center gap-2">
            <span x-text="currentIndex === slides.length - 1 ? 'Selesai Belajar' : 'Selanjutnya'"></span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        </button>
    </footer>
</div>

<script>
function studentSlidePresentation(slidesData) {
    return {
        slides: slidesData || [],
        currentIndex: 0,

        get currentSlide() {
            return this.slides[this.currentIndex] || null;
        },

        nextSlide() {
            if (this.currentIndex < this.slides.length - 1) {
                this.currentIndex++;
            }
        },

        prevSlide() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
            }
        },

        goToSlide(index) {
            if (index >= 0 && index < this.slides.length) {
                this.currentIndex = index;
            }
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Error attempting fullscreen:', err);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
    };
}
</script>
@endsection
