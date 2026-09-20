@extends('layouts.guest')

@section('content')
<div class="relative overflow-hidden">
    <!-- Hero Glow Background -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-gradient-to-tr from-indigo-600/20 via-purple-600/20 to-cyan-500/10 blur-[130px] pointer-events-none"></div>

    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 mb-6 animate-pulse">
            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
            Laravel 13 &bull; AI-Augmented LCMS Platform
        </div>

        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold text-white tracking-tight leading-[1.1] max-w-4xl mx-auto">
            Transformasi Materi Ajar Menjadi <span class="bg-gradient-to-r from-indigo-400 via-purple-300 to-cyan-400 bg-clip-text text-transparent">Slidebook & Kuis Cerdas</span>
        </h1>

        <p class="mt-6 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
            Platform LCMS terintegrasi AI dengan prinsip <strong class="text-slate-200">Human-in-the-Loop</strong>. Unggah modul PDF/Word, biarkan AI mengekstrak intisari, validasi oleh Guru, dan nikmati pembelajaran adaptif untuk Siswa.
        </p>

        <!-- CTA Buttons -->
        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="px-6 py-3.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-xl shadow-indigo-600/30 transition-all hover:scale-105">
                        Buka Admin Dashboard &rarr;
                    </a>
                @elseif(auth()->user()->isInstructor())
                    <a href="{{ route('instructor.dashboard') }}" class="px-6 py-3.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-xl shadow-indigo-600/30 transition-all hover:scale-105">
                        Buka Dashboard Guru &rarr;
                    </a>
                @else
                    <a href="{{ route('student.dashboard') }}" class="px-6 py-3.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-xl shadow-indigo-600/30 transition-all hover:scale-105">
                        Buka Portal Siswa &rarr;
                    </a>
                @endif
            @else
                <a href="{{ route('register') }}" class="px-6 py-3.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-xl shadow-indigo-600/30 transition-all hover:scale-105">
                    Mulai Belajar Sekarang &rarr;
                </a>
                <a href="{{ route('login') }}" class="px-6 py-3.5 rounded-xl text-sm font-bold text-slate-300 bg-slate-900 border border-slate-800 hover:bg-slate-800 hover:text-white transition-all">
                    Masuk Akun Demo
                </a>
            @endauth
        </div>

        <!-- Metric Badges -->
        <div class="mt-14 grid grid-cols-2 sm:grid-cols-3 gap-4 max-w-2xl mx-auto pt-8 border-t border-slate-800/80 text-left">
            <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/50">
                <span class="text-2xl font-extrabold text-white">100%</span>
                <p class="text-xs text-slate-400 mt-0.5">Server Authority on Quiz & Timer</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/50">
                <span class="text-2xl font-extrabold text-white">Zero</span>
                <p class="text-xs text-slate-400 mt-0.5">Unapproved AI Publishing</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/50 col-span-2 sm:col-span-1">
                <span class="text-2xl font-extrabold text-white">Private</span>
                <p class="text-xs text-slate-400 mt-0.5">Secure Document Storage</p>
            </div>
        </div>
    </section>

    <!-- Key Features Grid -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-400">Pilar Utama Sistem</h2>
            <p class="text-2xl sm:text-3xl font-extrabold text-white mt-2">Dibuat Khusus untuk Pengalaman Belajar Berkualitas</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1 -->
            <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all hover:-translate-y-1 shadow-lg">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Ekstraksi Dokumen & AI Analysis</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Pengajar mengunggah berkas PDF atau DOCX. Sistem mengekstrak teks secara otomatis di antrean latar belakang untuk dianalisis oleh AI.
                </p>
            </div>

            <!-- Card 2 -->
            <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all hover:-translate-y-1 shadow-lg">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Slidebook Interaktif & Human Review</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Menghasilkan lembar presentasi ringkas terstruktur dengan trace referensi sumber asli. Guru berhak mengedit dan meng-approve sebelum tayang.
                </p>
            </div>

            <!-- Card 3 -->
            <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all hover:-translate-y-1 shadow-lg">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Bank Soal & Asesmen Anti-Curang</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Kuis diacak pada susunan soal dan opsi jawaban per attempt siswa. Timer server-authoritative mencegah manipulasi client-side.
                </p>
            </div>
        </div>
    </section>
</div>
@endsection
