@extends('layouts.guru')

@section('title', 'Ubah Kata Sandi')
@section('subtitle', 'Area Guru — Keamanan Akun')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('guru.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('guru.profile.show') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Profil</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 dark:text-white font-medium">Ubah Kata Sandi</span>
    </nav>

    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Ubah Kata Sandi</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ganti kata sandi akun Anda secara mandiri.</p>
    </div>

    <form method="POST" action="{{ route('guru.profile.update-password') }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs p-6 sm:p-8 space-y-6">
        @csrf
        @method('PUT')

        {{-- Current Password --}}
        <div>
            <label for="current_password" class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Kata Sandi Saat Ini <span class="text-rose-500">*</span>
            </label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                   class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('current_password') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror">
            @error('current_password')
                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- New Password --}}
        <div>
            <label for="password" class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Kata Sandi Baru <span class="text-rose-500">*</span>
            </label>
            <input type="password" name="password" id="password" required autocomplete="new-password"
                   class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror">
            @error('password')
                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Minimal 8 karakter, mengandung huruf besar & kecil, serta angka.</p>
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
            </label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                   class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        {{-- Security Info --}}
        <div class="p-4 rounded-xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/50">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div class="text-xs text-amber-900 dark:text-amber-300 leading-relaxed">
                    <p class="font-semibold mb-1">Keamanan Akun Guru:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-amber-800 dark:text-amber-300">
                        <li>Kata sandi lama wajib diverifikasi sebelum penggantian.</li>
                        <li>Gunakan kombinasi huruf, angka, dan simbol yang aman.</li>
                        <li>Jangan berikan akses akun Anda kepada pihak lain.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Ubah Kata Sandi
            </button>
            <a href="{{ route('guru.profile.show') }}"
               class="px-5 py-2.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm font-medium transition-colors">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
