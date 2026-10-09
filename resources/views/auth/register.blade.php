@extends('layouts.guest')

@section('content')
<div class="min-h-[calc(100vh-10rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-slate-900/80 p-8 rounded-2xl border border-slate-800 shadow-2xl backdrop-blur-xl">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-400 text-white shadow-lg shadow-indigo-500/25 mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">Pendaftaran Akun Siswa</h2>
            <p class="mt-2 text-sm text-slate-400">
                Bergabunglah untuk mulai belajar kursus cerdas bertenaga AI.
            </p>
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $error }}</span>
                    </p>
                @endforeach
            </div>
        @endif

        <!-- Register Form -->
        <form class="mt-6 space-y-4" action="{{ route('register') }}" method="POST">
            @csrf
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="Contoh: Budi Santoso">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="nama@email.com">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="Minimal 8 karakter">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="Ulangi password">
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-lg shadow-indigo-600/25 transition-all hover:scale-[1.01] active:scale-[0.99]">
                    Daftar Sekarang
                </button>
            </div>
        </form>

        <div class="text-center text-xs text-slate-400">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-400 hover:text-indigo-300">Masuk di sini</a>
        </div>
    </div>
</div>
@endsection
