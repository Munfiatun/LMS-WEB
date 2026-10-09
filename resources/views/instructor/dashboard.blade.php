@extends('layouts.app')

@php
    $title = 'Dashboard Guru';
    $breadcrumb = 'Instructor Dashboard';
@endphp

@section('content')
<div class="space-y-6">
    <!-- Instructor Banner -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-purple-950/40 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    Portal Pengajar
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Halo, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-slate-400 mt-1">Kelola silabus, unggah dokumen PDF/DOCX materi, dan biarkan AI merancang slidebook interaktif.</p>
            </div>
            <div>
                <span class="px-3.5 py-2 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-xs font-semibold text-indigo-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    AI Pipeline Ready
                </span>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kursus Saya</span>
            <div class="mt-3 flex items-baseline justify-between gap-2">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_courses'] }}</span>
                <span class="text-xs text-indigo-400">Kursus</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Materi Ajar</span>
            <div class="mt-3 flex items-baseline justify-between gap-2">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_materials'] }}</span>
                <span class="text-xs text-blue-400">Materi</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Slidebook AI</span>
            <div class="mt-3 flex items-baseline justify-between gap-2">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_slidebooks'] }}</span>
                <span class="text-xs text-purple-400">Versi</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank Soal</span>
            <div class="mt-3 flex items-baseline justify-between gap-2">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_question_banks'] }}</span>
                <span class="text-xs text-cyan-400">Koleksi</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Kuis</span>
            <div class="mt-3 flex items-baseline justify-between gap-2">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_quizzes'] }}</span>
                <span class="text-xs text-emerald-400">Kuis</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Siswa Terdaftar</span>
            <div class="mt-3 flex items-baseline justify-between gap-2">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_students'] }}</span>
                <span class="text-xs text-amber-400">Siswa</span>
            </div>
        </div>
    </div>

    <!-- AI Feature Showcase & Workflow Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                Workflow Pembelajaran Bertenaga AI (Human-in-the-Loop)
            </h3>
            <p class="text-sm text-slate-400">Sistem AI LCMS dirancang dengan prinsip kendali penuh di tangan Guru:</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                    <span class="text-xs font-bold text-indigo-400">1. Ekstraksi Dokumen</span>
                    <p class="text-xs text-slate-400 mt-1">Upload modul Word/PDF materi atau naskah soal. Teks diekstrak di background queue.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                    <span class="text-xs font-bold text-purple-400">2. Generasi & Validasi</span>
                    <p class="text-xs text-slate-400 mt-1">AI menyusun poin intisari slidebook dan butir soal pilihan ganda secara terstruktur.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                    <span class="text-xs font-bold text-emerald-400">3. Review & Approval</span>
                    <p class="text-xs text-slate-400 mt-1">Guru meninjau, mengedit, dan menyetujui draft sebelum resmi dapat dipelajari oleh siswa.</p>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-white mb-2">Profil Pengajar</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ auth()->user()->bio ?? 'Belum ada bio profil. Anda dapat melengkapi profil pengajar Anda untuk ditampilkan di katalog kursus publik.' }}</p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <span>Email: {{ auth()->user()->email }}</span>
                <span class="text-emerald-400 font-medium">Terverifikasi</span>
            </div>
        </div>
    </div>
</div>
@endsection
