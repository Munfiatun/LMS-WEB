@extends('layouts.app')

@php
    $title = 'Buat Kursus Baru';
    $breadcrumb = 'Buat Kursus';
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Buat Kursus Baru</h1>
            <p class="text-sm text-slate-400 mt-1">Lengkapi informasi umum kursus sebelum menambahkan bab dan modul pembelajaran.</p>
        </div>
        <a href="{{ route('instructor.courses.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            &larr; Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <form action="{{ route('instructor.courses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Judul Kursus <span class="text-rose-400">*</span></label>
                <input type="text" id="title" name="title" required value="{{ old('title') }}"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm"
                       placeholder="Contoh: Pemrograman Web dengan Laravel 13">
            </div>

            <div>
                <label for="category_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Kategori Kursus</label>
                <select id="category_id" name="category_id"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Deskripsi Singkat</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm"
                          placeholder="Jelaskan tujuan dan materi yang akan dipelajari siswa...">{{ old('description') }}</textarea>
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-800">
                <a href="{{ route('instructor.courses.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/25 transition-all">
                    Simpan & Lanjutkan ke Silabus &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
