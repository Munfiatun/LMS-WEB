@extends('layouts.app')

@php
    $title = 'Kelola Materi: ' . $material->title;
    $breadcrumb = 'Kelola Materi';
@endphp

@section('content')
@if($material->status === 'published')
    <form action="{{ route('instructor.materials.unpublish', $material) }}" method="POST">
        @csrf
        <button type="submit">Kembalikan ke Draft untuk Edit</button>
    </form>
@elseif($publicationErrors === [])
    <form action="{{ route('instructor.materials.publish', $material) }}" method="POST">
        @csrf
        <button type="submit">Publikasikan Materi</button>
    </form>
@else
    <p class="text-sm text-amber-300">{{ implode(' ', $publicationErrors) }}</p>
@endif
<div class="space-y-8" x-data="{ deleteConfirm: null }">
    {{-- Top Nav Bar --}}
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-violet-950/30 to-slate-900 border border-slate-800 shadow-xl">
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-3">
            <a href="{{ route('instructor.courses.index') }}" class="hover:text-white transition-colors">&larr; Kursus Saya</a>
            <span class="text-slate-600">&bull;</span>
            <a href="{{ route('instructor.courses.edit', $material->section->course) }}" class="hover:text-white transition-colors">{{ $material->section->course->title }}</a>
            <span class="text-slate-600">&bull;</span>
            <span>{{ $material->section->title }}</span>
        </div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">{{ $material->title }}</h1>
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="text-xs text-slate-400">Slug: <code class="text-violet-300 bg-slate-950 px-1.5 py-0.5 rounded">{{ $material->slug }}</code></span>
                    @if($material->status === 'published')
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Published</span>
                    @elseif($material->status === 'processing')
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 animate-pulse">Processing AI</span>
                    @elseif($material->status === 'review')
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">Needs Review</span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-slate-500/20 text-slate-400 border border-slate-500/30">Draft</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <span>⏱ {{ $material->duration_minutes }} menit</span>
                <span class="text-slate-600">&bull;</span>
                <span>📄 {{ $material->documents->count() }} Dokumen</span>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if($material->slidebook)
        {{-- AI Slidebook Studio Card --}}
        <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-950/60 via-purple-950/40 to-slate-900 border border-indigo-500/30 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-violet-400 animate-pulse"></span>
                    <h2 class="text-base font-bold text-white">AI Slidebook Studio</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-500/30">v{{ $material->slidebook->version }}</span>
                    @if($material->slidebook->status === 'published')
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Published</span>
                    @elseif($material->slidebook->status === 'review')
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">Menunggu Review Guru</span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-slate-500/20 text-slate-300 border border-slate-500/30">Draft</span>
                    @endif
                </div>
                <p class="text-xs text-slate-300">
                    Slidebook memiliki {{ $material->slidebook->slides()->count() }} lembar slide aktif. Anda dapat meninjau, mengedit per lembar, dan merilisnya ke siswa.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('instructor.materials.slidebook.review', $material) }}"
                   class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    Review & Edit Slidebook
                </a>
                <form action="{{ route('instructor.materials.ai.slidebook', $material) }}" method="POST" onsubmit="return confirm('Regenerate AI Slidebook akan memperbarui isi lembar slide. Lanjutkan?')">
                    @csrf
                    <input type="hidden" name="regenerate" value="1">
                    <button type="submit" class="px-3.5 py-2 text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 rounded-xl border border-slate-700 transition-colors">
                        Regenerate
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- Generate AI Banner --}}
        <div class="p-6 rounded-2xl bg-gradient-to-r from-violet-950/40 via-slate-900 to-indigo-950/30 border border-violet-500/20 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <h2 class="text-base font-bold text-white">Generate Slidebook Interaktif dengan AI</h2>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Ubah berkas materi ajar Word/PDF Anda menjadi rangkaian slide presentasi interaktif secara otomatis.
                </p>
            </div>
            <form action="{{ route('instructor.materials.ai.slidebook', $material) }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    Generate Slidebook AI
                </button>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        {{-- Left: Metadata Form --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-4">
                <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Edit Informasi Materi</h2>

                <form action="{{ route('instructor.materials.update', $material) }}" method="POST" class="space-y-4">
<fieldset @disabled(! in_array($material->status, ['draft', 'review'], true))>
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Judul Materi <span class="text-rose-400">*</span></label>
                        <input type="text" id="title" name="title" required value="{{ old('title', $material->title) }}"
                               class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="duration_minutes" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Durasi (menit)</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" min="1" value="{{ old('duration_minutes', $material->duration_minutes) }}"
                                   class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Publikasi dilakukan melalui action Publish setelah materi siap.</p>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Ringkasan</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">{{ old('description', $material->description) }}</textarea>
                    </div>

                    <div>
                        <label for="content" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Konten Teks</label>
                        <textarea id="content" name="content" rows="8"
                                  class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500 font-mono">{{ old('content', $material->content) }}</textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow-md shadow-indigo-600/20">
                            Perbarui Informasi Materi
                        </button>
                    </div>
                </fieldset></form>
            </div>

            {{-- Danger Zone --}}
            <div class="p-5 rounded-2xl bg-rose-950/20 border border-rose-500/20 shadow space-y-3">
                <h3 class="text-sm font-bold text-rose-300 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                    Zona Berbahaya
                </h3>
                <form action="{{ route('instructor.materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus materi ini beserta semua dokumen terlampir? Tindakan ini tidak dapat dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 text-xs font-bold text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 rounded-xl transition-colors">
                        Hapus Materi Ini Secara Permanen
                    </button>
                </form>
            </div>
        </div>

        {{-- Right: Document Upload & File List --}}
        <div class="lg:col-span-3 space-y-6">
            {{-- Upload Area --}}
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-5"
                 x-data="documentUploader()"
                 x-on:dragover.prevent="isDragging = true"
                 x-on:dragleave.prevent="isDragging = false"
                 x-on:drop.prevent="handleDrop($event)">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        Unggah Dokumen
                    </h2>
                    <span class="text-[11px] text-slate-400">PDF atau DOCX, maks 20MB</span>
                </div>

                <form action="{{ route('instructor.materials.documents.store', $material) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
<fieldset @disabled(! in_array($material->status, ['draft', 'review'], true))>
                    @csrf
                    <label for="document" class="block cursor-pointer">
                        <div :class="isDragging ? 'border-indigo-400 bg-indigo-950/20' : 'border-slate-700 bg-slate-950/60 hover:border-indigo-500/50 hover:bg-indigo-950/10'"
                             class="border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200">
                            <div class="flex flex-col items-center space-y-3">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-violet-500/20 flex items-center justify-center border border-indigo-500/30">
                                    <svg class="w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white" x-text="fileName || 'Drag & drop file atau klik untuk memilih'"></p>
                                    <p class="text-xs text-slate-400 mt-1">Format yang didukung: <code class="text-indigo-300 bg-slate-900 px-1.5 rounded">.pdf</code>, <code class="text-indigo-300 bg-slate-900 px-1.5 rounded">.docx</code></p>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="document" name="document" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               class="hidden" @change="fileSelected($event)">
                    </label>

                    <template x-if="fileName">
                        <div class="mt-4 flex items-center justify-between p-3 rounded-xl bg-indigo-950/30 border border-indigo-500/20">
                            <div class="flex items-center gap-3 text-sm text-indigo-300">
                                <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="truncate" x-text="fileName"></span>
                                <span class="text-xs text-slate-500" x-text="fileSize"></span>
                            </div>
                            <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow transition-colors flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                Upload Sekarang
                            </button>
                        </div>
                    </template>
                </fieldset></form>
            </div>

            {{-- Uploaded Documents List --}}
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                        Dokumen Terlampir
                    </h2>
                    <span class="text-[11px] text-slate-400 px-2 py-0.5 rounded-full bg-slate-800 border border-slate-700">{{ $material->documents->count() }} file</span>
                </div>

                @forelse($material->documents as $doc)
                    <div class="flex items-center justify-between p-4 rounded-xl bg-slate-950/50 border border-slate-800/60 hover:border-slate-700 transition-colors group">
                        <div class="flex items-center gap-4 min-w-0 flex-1">
                            {{-- File Type Icon --}}
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                                {{ $doc->extension === 'pdf' ? 'bg-rose-500/10 border border-rose-500/30' : 'bg-blue-500/10 border border-blue-500/30' }}">
                                @if($doc->extension === 'pdf')
                                    <svg class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                @else
                                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                @endif
                            </div>

                            {{-- File Info --}}
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-white truncate">{{ $doc->original_name }}</p>
                                <p class="text-xs text-slate-400">{{ ucfirst($doc->extraction?->status ?? 'pending') }}</p>
                                @if($doc->extraction?->status === 'failed')
                                    <p class="text-xs text-rose-300">Dokumen gagal diproses. Unggah PDF teks atau DOCX yang valid.</p>
                                @endif
                                <div class="flex items-center gap-3 mt-1 text-[11px] text-slate-400">
                                    <span class="uppercase font-bold text-{{ $doc->extension === 'pdf' ? 'rose' : 'blue' }}-400">{{ $doc->extension }}</span>
                                    <span>&bull;</span>
                                    <span>{{ number_format($doc->size / 1024, 1) }} KB</span>
                                    <span>&bull;</span>
                                    <span>{{ $doc->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                            <a href="{{ route('documents.download', $doc) }}" class="p-2 text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors" title="Download">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            </a>
                            @if(in_array($material->status, ['draft', 'review'], true))
<form action="{{ route('instructor.documents.destroy', $doc) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini dari private storage?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
@endif
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-800/50 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-400">Belum ada dokumen diunggah</p>
                        <p class="text-xs text-slate-500 mt-1">Unggah PDF atau DOCX untuk dianalisis AI dan dikonversi menjadi Slidebook interaktif.</p>
                    </div>
                @endforelse
            </div>

            {{-- AI Pipeline Hint --}}
            <div class="p-5 rounded-2xl bg-gradient-to-r from-indigo-950/40 to-violet-950/30 border border-indigo-500/20 shadow">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center flex-shrink-0 border border-indigo-500/30">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-indigo-300">Langkah Selanjutnya: AI Processing</h3>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Setelah mengunggah dokumen, Anda dapat men-trigger analisis AI untuk mengekstrak teks
                            dan menghasilkan <strong class="text-indigo-300">Slidebook interaktif</strong> secara otomatis.
                            Konten AI akan masuk tahap <em>Review</em> untuk approval Anda sebelum dipublikasikan ke siswa.
                        </p>
                        <div class="mt-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-slate-400 bg-slate-950/60 border border-slate-700 rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                Fitur AI tersedia di Phase 4
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function documentUploader() {
    return {
        isDragging: false,
        fileName: null,
        fileSize: null,
        fileSelected(event) {
            const file = event.target.files[0];
            if (file) {
                this.fileName = file.name;
                this.fileSize = this.formatSize(file.size);
            }
        },
        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                const input = document.getElementById('document');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
                this.fileName = file.name;
                this.fileSize = this.formatSize(file.size);
            }
        },
        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }
    };
}
</script>
@endsection
