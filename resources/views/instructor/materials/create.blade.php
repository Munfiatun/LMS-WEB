@extends('layouts.app')

@php
    $title = 'Tambah Materi Baru';
    $breadcrumb = 'Tambah Materi';
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('instructor.courses.edit', $section->course) }}" class="hover:text-white">&larr; {{ $section->course->title }}</a>
                <span>&bull;</span>
                <span>{{ $section->title }}</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Tambah Materi Baru</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <form action="{{ route('instructor.materials.store', $section) }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Judul Materi <span class="text-rose-400">*</span></label>
                <input type="text" id="title" name="title" required value="{{ old('title') }}"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm"
                       placeholder="Contoh: Arsitektur Service Layer dan Form Request">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="duration_minutes" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Estimasi Durasi (Menit)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" value="{{ old('duration_minutes', 15) }}"
                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Status Publikasi</label>
                    <select id="status" name="status"
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft (Hanya Pengajar)</option>
                        <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published (Dapat diakses Siswa)</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Ringkasan / Sinopsis Materi</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm"
                          placeholder="Jelaskan intisari materi ini...">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="content" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Konten Teks / Catatan Tambahan (Opsional)</label>
                <textarea id="content" name="content" rows="6"
                          class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm font-mono"
                          placeholder="Tuliskan materi dalam format teks atau HTML...">{{ old('content') }}</textarea>
                <p class="text-[11px] text-slate-500 mt-1">Anda juga dapat mengunggah berkas PDF/DOCX setelah materi ini disimpan.</p>
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-800">
                <a href="{{ route('instructor.courses.edit', $section->course) }}" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/25 transition-all">
                    Simpan Materi &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
