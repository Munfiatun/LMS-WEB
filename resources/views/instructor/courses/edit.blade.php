@extends('layouts.app')

@php
    $title = 'Kelola Silabus Kursus';
    $breadcrumb = 'Kelola: ' . $course->title;
    $canPublish = $course->status === 'draft' && $publicationErrors === [];
@endphp

@section('content')
<div class="space-y-8" x-data="{ addSectionModal: false }">
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-xl flex flex-col xl:flex-row xl:items-center justify-between gap-5">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <a href="{{ route('instructor.courses.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">&larr; Kembali ke Daftar</a>
                <span class="text-slate-600">&bull;</span>
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $course->category?->name ?? 'Uncategorized' }}</span>
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full {{ $course->status === 'published' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($course->status === 'archived' ? 'bg-slate-500/20 text-slate-300 border border-slate-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30') }}">{{ ucfirst($course->status) }}</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight truncate">{{ $course->title }}</h1>
            <p class="text-xs text-slate-400 mt-1">Slug: <code class="text-indigo-300 bg-slate-950 px-1.5 py-0.5 rounded">{{ $course->slug }}</code></p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($course->status === 'published')
                <form action="{{ route('instructor.courses.archive', $course) }}" method="POST" onsubmit="return confirm('Arsipkan kursus ini? Kursus tidak lagi tersedia untuk pendaftaran baru.')">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold text-amber-200 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 transition-colors">Arsipkan Kursus</button>
                </form>
                <span class="text-xs text-emerald-400 flex items-center gap-1.5 bg-emerald-950/60 border border-emerald-800/40 px-3 py-2 rounded-xl">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Kursus Aktif & Terbit
                </span>
            @elseif($course->status === 'draft')
                <form action="{{ route('instructor.courses.publish', $course) }}" method="POST">
                    @csrf
                    <button type="submit" @disabled(!$canPublish)
                        class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $canPublish ? 'text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/20' : 'text-slate-500 bg-slate-800 border border-slate-700 cursor-not-allowed' }}">
                        Publikasikan Kursus
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($course->status === 'draft')
        <div class="p-5 rounded-2xl border {{ $publicationErrors === [] ? 'bg-emerald-950/20 border-emerald-800/40' : 'bg-amber-950/20 border-amber-800/40' }}">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center {{ $publicationErrors === [] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }}">
                    {{ $publicationErrors === [] ? '✓' : '!' }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-white">Kesiapan Publikasi</h2>
                    @if($publicationErrors === [])
                        <p class="text-xs text-emerald-300 mt-1">Kursus siap dipublikasikan.</p>
                    @else
                        <p class="text-xs text-slate-400 mt-1 mb-2">Tombol publikasi dinonaktifkan sampai syarat berikut terpenuhi:</p>
                        <ul class="space-y-1 text-xs text-amber-200">
                            @foreach($publicationErrors as $error)
                                <li class="flex items-start gap-2"><span>•</span><span>{{ $error }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <div class="space-y-6 lg:col-span-1">
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-4">
                <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Informasi Umum Kursus</h2>
                <form action="{{ route('instructor.courses.update', $course) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Judul Kursus</label>
                        <input type="text" id="title" name="title" required value="{{ old('title', $course->title) }}" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="category_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Kategori</label>
                        <select id="category_id" name="category_id" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Tanpa Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $course->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Deskripsi</label>
                        <textarea id="description" name="description" rows="5" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">{{ old('description', $course->description) }}</textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow">Perbarui Informasi</button>
                </form>
            </div>

            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-4">
                <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Akses & Pendaftaran Siswa</h2>
                <div class="space-y-3">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Token Kelas</label>
                    @if($course->enrollment_code)
                        <div class="flex items-center justify-between gap-3 p-3 bg-slate-950 border border-slate-800 rounded-xl">
                            <code class="text-lg font-mono font-bold text-emerald-400 tracking-widest truncate" id="tokenText">{{ $course->enrollment_code }}</code>
                            <button onclick="navigator.clipboard.writeText(@js($course->enrollment_code)).then(() => alert('Token berhasil disalin.'))" type="button" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-bold uppercase rounded-lg border border-slate-700">Salin</button>
                        </div>
                    @else
                        <div class="p-3 bg-slate-950 border border-amber-900/30 rounded-xl"><p class="text-xs text-amber-400 font-medium">Belum ada token. Regenerate untuk membuat token baru.</p></div>
                    @endif
                    <form action="{{ route('instructor.courses.regenerate-code', $course) }}" method="POST" onsubmit="return confirm('Token lama tidak dapat digunakan lagi untuk pendaftaran baru. Lanjutkan?')">
                        @csrf
                        <button type="submit" class="w-full py-2 px-4 rounded-xl text-xs font-bold text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-colors">Regenerate Token</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Silabus & Struktur Materi</h2>
                    <p class="text-xs text-slate-400">Atur chapter/bab dan materi ajar beserta dokumen PDF/DOCX pendukung.</p>
                </div>
                <button @click="addSectionModal = true" type="button" class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-all shadow">+ Tambah Bab</button>
            </div>

            <div class="space-y-5">
                @forelse($course->sections as $section)
                    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow overflow-hidden">
                        <div class="p-4 bg-slate-950/80 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-7 h-7 rounded-md bg-indigo-500/20 text-indigo-300 text-xs font-bold flex items-center justify-center border border-indigo-500/30 flex-shrink-0">{{ $section->order }}</span>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-white truncate">{{ $section->title }}</h3>
                                    @if($section->description)<p class="text-xs text-slate-400 mt-0.5">{{ $section->description }}</p>@endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="{{ route('instructor.materials.create', $section) }}" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 border border-indigo-500/20">+ Materi</a>
                                <form action="{{ route('instructor.sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Hapus bab ini dan seluruh materinya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs text-rose-400 hover:text-rose-300">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <div class="p-3 space-y-2">
                            @forelse($section->materials as $mat)
                                <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800/60 hover:border-slate-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-6 h-6 rounded bg-slate-800 text-slate-400 text-[11px] font-semibold flex items-center justify-center flex-shrink-0">{{ $mat->order }}</span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('instructor.materials.edit', $mat) }}" class="text-xs font-bold text-white hover:text-indigo-400 transition-colors">{{ $mat->title }}</a>
                                                <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded {{ $mat->status === 'published' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-400' }}">{{ ucfirst($mat->status) }}</span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-400 mt-1">
                                                <span>⏱ {{ $mat->duration_minutes }} menit</span><span>&bull;</span><span class="text-indigo-400">📄 {{ $mat->documents->count() }} dokumen</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <a href="{{ route('instructor.materials.edit', $mat) }}" class="text-xs text-indigo-300 font-medium px-2.5 py-1.5 rounded-lg bg-indigo-500/10">Kelola</a>
                                        <form action="{{ route('instructor.materials.destroy', $mat) }}" method="POST" onsubmit="return confirm('Hapus materi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-400 px-2 py-1">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="py-5 text-center text-xs text-slate-500">Belum ada materi di bab ini. Klik <strong>+ Materi</strong> untuk menambahkan.</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center rounded-2xl bg-slate-900/60 border border-slate-800">
                        <p class="text-sm text-slate-300 font-medium">Belum ada bab/chapter pembelajaran.</p>
                        <p class="text-xs text-slate-500 mt-1">Klik tombol <strong>+ Tambah Bab</strong> untuk membuat bab pertama.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div x-show="addSectionModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="addSectionModal = false">
            <h3 class="text-base font-bold text-white">Tambah Bab / Chapter Baru</h3>
            <form action="{{ route('instructor.sections.store', $course) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="section_title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Judul Bab <span class="text-rose-400">*</span></label>
                    <input type="text" id="section_title" name="title" required class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Bab 1: Dasar Pengenalan">
                </div>
                <div>
                    <label for="section_desc" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
                    <textarea id="section_desc" name="description" rows="2" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Penjelasan pokok bahasan..."></textarea>
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
