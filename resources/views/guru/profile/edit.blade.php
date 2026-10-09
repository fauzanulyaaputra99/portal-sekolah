@extends('layouts.guru')

@section('title', 'Edit Kontak')
@section('subtitle', 'Area Guru — Edit Profil')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('guru.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('guru.profile.show') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Profil</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 dark:text-white font-medium">Edit Kontak</span>
    </nav>

    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Edit Nomor Kontak</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Perbarui nomor telepon atau WhatsApp Anda.</p>
    </div>

    <form method="POST" action="{{ route('guru.profile.update-contact') }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs p-6 sm:p-8 space-y-6">
        @csrf
        @method('PUT')

        {{-- Info readonly --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/60">
            <div>
                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Nama Lengkap</p>
                <p class="text-sm text-slate-900 dark:text-white font-semibold mt-0.5">{{ $teacher->full_name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">NIP</p>
                <p class="text-sm text-slate-900 dark:text-white font-semibold mt-0.5 font-mono">{{ $teacher->nip ?? '-' }}</p>
            </div>
        </div>

        {{-- Phone Number --}}
        <div>
            <label for="phone_number" class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Nomor Telepon / WhatsApp
            </label>
            <input type="text" name="phone_number" id="phone_number"
                   value="{{ old('phone_number', $teacher->phone_number) }}"
                   placeholder="Contoh: 081234567890"
                   maxlength="20"
                   class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('phone_number') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror">
            @error('phone_number')
                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Format: angka, spasi, tanda hubung, atau tanda kurung. Maksimal 20 karakter.</p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Perubahan
            </button>
            <a href="{{ route('guru.profile.show') }}"
               class="px-5 py-2.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm font-medium transition-colors">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
