@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('subtitle', 'Area Administrasi')

@section('content')
<div class="space-y-6">
    {{-- Welcome Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-xs transition-colors duration-200">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Akses Server-Side Ditegakkan</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                    Selamat datang, {{ $user->username }}!
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Area Admin / Tata Usaha — Portal Sekolah
                </p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>

        {{-- Stats Grid --}}
        <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700/60 transition-colors duration-200">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Route area</dt>
                <dd class="mt-1 font-mono font-semibold text-slate-900 dark:text-white">{{ $areaRoute }}</dd>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700/60 transition-colors duration-200">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Role sesi (DB)</dt>
                <dd class="mt-1 font-mono font-semibold text-slate-900 dark:text-white">{{ $user->role }}</dd>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700/60 transition-colors duration-200">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Status akun</dt>
                <dd class="mt-1 font-semibold {{ $user->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $user->is_active ? 'AKTIF' : 'NONAKTIF' }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Quick Access Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Jadwal Piket --}}
        <a href="{{ route('admin.duty-schedules.index') }}" class="group bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700/80 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <h3 class="mt-4 font-semibold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Jadwal Piket</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kelola jadwal guru piket</p>
        </a>

        {{-- Absensi Sekolah --}}
        <a href="{{ route('admin.school-attendances.index') }}" class="group bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700/80 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <h3 class="mt-4 font-semibold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Absensi Sekolah</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Monitor & koreksi absensi siswa</p>
        </a>

        {{-- Placeholder: Master Data --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700/80 opacity-50">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700/50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
            </div>
            <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">Master Data</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Coming soon...</p>
        </div>
    </div>

    {{-- Info Note --}}
    <div class="bg-indigo-50 dark:bg-indigo-950/40 rounded-xl p-4 border border-indigo-200 dark:border-indigo-800/60 transition-colors duration-200">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-indigo-800 dark:text-indigo-300">
                <strong>Catatan:</strong> Dashboard ini adalah fondasi pemisahan area. Modul lengkap (master data guru, siswa, kelas, mata pelajaran, tahun ajaran, repositori dokumen, monitoring, laporan) akan ditambahkan pada tahap implementasi berikutnya.
            </p>
        </div>
    </div>
</div>
@endsection
