@extends('layouts.app')

@php
    $title = 'Manajemen Kategori';
    $breadcrumb = 'Kategori';
@endphp

@section('content')
<div class="space-y-8" x-data="{ addModal: false, editModal: false, editData: {} }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Manajemen Kategori</h1>
            <p class="text-sm text-slate-400 mt-1">Kelola kategori untuk mengelompokkan kursus di platform LCMS.</p>
        </div>
        <button @click="addModal = true" type="button"
                class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5 self-start">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Kategori
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <p class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Total Kategori</p>
            <p class="text-3xl font-extrabold text-white mt-1">{{ $categories->count() }}</p>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <p class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Kategori Aktif</p>
            <p class="text-3xl font-extrabold text-indigo-400 mt-1">{{ $categories->filter(fn($c) => $c->courses_count > 0)->count() }}</p>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <p class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Total Kursus</p>
            <p class="text-3xl font-extrabold text-emerald-400 mt-1">{{ $categories->sum('courses_count') }}</p>
        </div>
    </div>

    {{-- Category Table --}}
    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-950/80 border-b border-slate-800">
                        <th class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 px-6 py-4">#</th>
                        <th class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 px-6 py-4">Kategori</th>
                        <th class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 px-6 py-4">Slug</th>
                        <th class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 px-6 py-4">Deskripsi</th>
                        <th class="text-center text-[11px] font-bold uppercase tracking-wider text-slate-400 px-6 py-4">Kursus</th>
                        <th class="text-right text-[11px] font-bold uppercase tracking-wider text-slate-400 px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($categories as $index => $category)
                        <tr class="hover:bg-slate-950/40 transition-colors">
                            <td class="px-6 py-4 text-xs text-slate-500 font-mono">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center">
                                        @if($category->icon)
                                            <span class="text-base">{{ $category->icon }}</span>
                                        @else
                                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                        @endif
                                    </div>
                                    <span class="text-sm font-semibold text-white">{{ $category->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-xs text-indigo-300 bg-slate-950 px-2 py-0.5 rounded">{{ $category->slug }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-slate-400 truncate max-w-xs">{{ $category->description ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold
                                    {{ $category->courses_count > 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500 border border-slate-700' }}">
                                    {{ $category->courses_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($category->courses_count === 0)
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-500 italic">Memiliki kursus</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-14 h-14 rounded-2xl bg-slate-800/50 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                </div>
                                <p class="text-sm font-medium text-slate-400">Belum ada kategori</p>
                                <p class="text-xs text-slate-500 mt-1">Klik "Tambah Kategori" untuk membuat kategori pertama.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Add Category --}}
    <div x-show="addModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="addModal = false">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah Kategori Baru
            </h3>
            <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="cat_name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Nama Kategori <span class="text-rose-400">*</span></label>
                    <input type="text" id="cat_name" name="name" required
                           class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="Contoh: Artificial Intelligence">
                </div>
                <div>
                    <label for="cat_icon" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Icon (Emoji/Symbol)</label>
                    <input type="text" id="cat_icon" name="icon"
                           class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="🤖">
                </div>
                <div>
                    <label for="cat_desc" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Deskripsi</label>
                    <textarea id="cat_desc" name="description" rows="2"
                              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500"
                              placeholder="Penjelasan singkat..."></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
                    <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
