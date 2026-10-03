@extends('layouts.app')

@php
    $title = 'Kelola Silabus Kursus';
    $breadcrumb = 'Kelola: ' . $course->title;
@endphp

@section('content')
<div class="space-y-8" x-data="{ addSectionModal: false }">
    <!-- Top Action Bar -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('instructor.courses.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors flex items-center gap-1">
                    &larr; Kembali ke Daftar
                </a>
                <span class="text-slate-600">&bull;</span>
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    {{ $course->category?->name ?? 'Uncategorized' }}
                </span>
                @if($course->status === 'published')
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Published</span>
                @else
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">Draft</span>
                @endif
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">{{ $course->title }}</h1>
            <p class="text-xs text-slate-400 mt-1">Slug: <code class="text-indigo-300 bg-slate-950 px-1.5 py-0.5 rounded">{{ $course->slug }}</code></p>
        </div>

        <div class="flex items-center gap-3">
            @if($course->status !== 'published')
                <form action="{{ route('instructor.courses.publish', $course) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Publikasikan Kursus
                    </button>
                </form>
            @else
                <span class="text-xs text-emerald-400 flex items-center gap-1 bg-emerald-950/60 border border-emerald-800/40 px-3 py-1.5 rounded-xl">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Kursus Aktif & Terbit
                </span>
            @endif
        </div>
    </div>

    <!-- Layout Grid: Left Details, Right Syllabus -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Course Metadata Form -->
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow h-fit space-y-4">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Informasi Umum Kursus</h2>

            <form action="{{ route('instructor.courses.update', $course) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Judul Kursus</label>
                    <input type="text" id="title" name="title" required value="{{ old('title', $course->title) }}"
                           class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Kategori</label>
                    <select id="category_id" name="category_id"
                            class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Tanpa Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $course->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">{{ old('description', $course->description) }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow">
                        Perbarui Informasi
                    </button>
                </div>
            </form>
        </div>

        <!-- Left: Enrollment Token Management -->
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow h-fit space-y-4">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Akses & Pendaftaran Siswa</h2>

            <div class="space-y-3">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Token Kelas</label>
                @if($course->enrollment_code)
                    <div class="flex items-center justify-between p-3 bg-slate-950 border border-slate-800 rounded-xl">
                        <code class="text-lg font-mono font-bold text-emerald-400 tracking-widest" id="tokenText">{{ $course->enrollment_code }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $course->enrollment_code }}').then(() => { alert('Token berhasil disalin.'); })" type="button" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-bold uppercase rounded-lg transition-colors border border-slate-700">
                            Salin
                        </button>
                    </div>
                @else
                    <div class="p-3 bg-slate-950 border border-amber-900/30 rounded-xl">
                        <p class="text-xs text-amber-400 font-medium">Belum ada token. Regenerate untuk membuat token baru.</p>
                    </div>
                @endif

                <form action="{{ route('instructor.courses.regenerate-code', $course) }}" method="POST" onsubmit="return confirm('Token lama tidak dapat digunakan lagi untuk pendaftaran baru. Lanjutkan?')">
                    @csrf
                    <button type="submit" class="w-full py-2 px-4 rounded-xl text-xs font-bold text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-colors shadow flex items-center justify-center gap-2 mt-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Regenerate Token
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Syllabus / Sections & Materials Builder -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">Silabus & Struktur Materi</h2>
                    <p class="text-xs text-slate-400">Atur chapter/bab dan materi ajar beserta dokumen PDF/Word pendukung.</p>
                </div>
                <button @click="addSectionModal = true" type="button" class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-all flex items-center gap-1.5 shadow">
                    + Tambah Bab
                </button>
            </div>

            <!-- Sections List -->
            <div class="space-y-5">
                @forelse($course->sections as $section)
                    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow overflow-hidden">
                        <!-- Section Header -->
                        <div class="p-4 bg-slate-950/80 border-b border-slate-800/80 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-md bg-indigo-500/20 text-indigo-300 text-xs font-bold flex items-center justify-center border border-indigo-500/30">
                                    {{ $section->order }}
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-white">{{ $section->title }}</h3>
                                    @if($section->description)
                                        <p class="text-xs text-slate-400 mt-0.5">{{ $section->description }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('instructor.materials.create', $section) }}" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 border border-indigo-500/20 transition-colors flex items-center gap-1">
                                    + Materi
                                </a>
                                <form action="{{ route('instructor.sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Hapus bab ini dan seluruh materinya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-slate-500 hover:text-rose-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Materials Inside Section -->
                        <div class="p-3 space-y-2">
                            @forelse($section->materials as $mat)
                                <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800/60 hover:border-slate-700/60 flex items-center justify-between transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="w-5 h-5 rounded bg-slate-800 text-slate-400 text-[11px] font-semibold flex items-center justify-center">
                                            {{ $mat->order }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('instructor.materials.edit', $mat) }}" class="text-xs font-bold text-white hover:text-indigo-400 transition-colors">
                                                    {{ $mat->title }}
                                                </a>
                                                @if($mat->status === 'published')
                                                    <span class="px-1.5 py-0.2 text-[9px] font-bold uppercase rounded bg-emerald-500/20 text-emerald-300">Published</span>
                                                @else
                                                    <span class="px-1.5 py-0.2 text-[9px] font-bold uppercase rounded bg-slate-500/20 text-slate-400">Draft</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-1">
                                                <span>⏱ {{ $mat->duration_minutes }} menit</span>
                                                <span>&bull;</span>
                                                <span class="text-indigo-400 font-medium">📄 {{ $mat->documents->count() }} Dokumen Lampiran</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('instructor.materials.edit', $mat) }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium px-2 py-1 rounded bg-indigo-500/10">
                                            Kelola & Upload
                                        </a>
                                        <form action="{{ route('instructor.materials.destroy', $mat) }}" method="POST" onsubmit="return confirm('Hapus materi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-500 hover:text-rose-400">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="py-4 text-center text-xs text-slate-500">
                                    Belum ada materi di bab ini. Klik <strong>+ Materi</strong> untuk menambahkan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center rounded-2xl bg-slate-900/60 border border-slate-800">
                        <p class="text-sm text-slate-300 font-medium">Belum ada bab/chapter pembelajaran.</p>
                        <p class="text-xs text-slate-500 mt-1">Klik tombol <strong>+ Tambah Bab</strong> di atas untuk membuat bab pertama.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal: Add Section -->
    <div x-show="addSectionModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="addSectionModal = false">
            <h3 class="text-base font-bold text-white">Tambah Bab / Chapter Baru</h3>
            <form action="{{ route('instructor.sections.store', $course) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="section_title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Judul Bab <span class="text-rose-400">*</span></label>
                    <input type="text" id="section_title" name="title" required
                           class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="Contoh: Bab 1: Dasar Pengenalan">
                </div>
                <div>
                    <label for="section_desc" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
                    <textarea id="section_desc" name="description" rows="2"
                              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500"
                              placeholder="Penjelasan pokok bahasan..."></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="addSectionModal = false" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl">Simpan Bab</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
