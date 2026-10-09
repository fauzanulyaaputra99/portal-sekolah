@extends('layouts.guru')

@section('title', 'Scanner Terkunci')
@section('subtitle', 'Guru — Scanner Absensi Siswa (terkunci)')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
        <div class="px-6 py-10 text-center">
            <div class="mx-auto h-16 w-16 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80 flex items-center justify-center">
                <span class="text-3xl" aria-hidden="true">🔒</span>
            </div>

            <h2 class="mt-5 text-lg font-bold text-slate-900 dark:text-white">Scanner Absensi Dikunci</h2>

            <p class="mt-3 text-sm font-semibold text-amber-800 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 p-3 rounded-xl border border-amber-200/60 dark:border-amber-900/50" role="alert">
                {{ $message }}
            </p>

            <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-left">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                    <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Akun guru</dt>
                    <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $teacherName ?? '—' }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                    <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tanggal server (Asia/Jakarta)</dt>
                    <dd class="mt-1 font-mono font-semibold text-slate-900 dark:text-white">{{ $serverDate }}</dd>
                </div>
            </dl>

            <p class="mt-6 text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                Status Guru Piket ditetapkan dari jadwal pada tabel
                <span class="font-mono text-slate-700 dark:text-slate-300">teacher_duty_schedules</span> untuk tanggal server,
                dan diperiksa ulang pada setiap permintaan.
            </p>

            <div class="mt-7 flex items-center justify-center gap-3">
                <a href="{{ route('guru.dashboard') }}"
                   class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
