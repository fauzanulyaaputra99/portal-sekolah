@extends('layouts.guru')

@section('title', 'Dashboard Guru')
@section('subtitle', 'Area Guru — Portal Sekolah')

@section('content')
<div class="space-y-6">
    {{-- Sapaan --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                Selamat datang, {{ $teacher->full_name ?? $user->username }}!
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80">
                Tahun Ajaran 2026/2027 Ganjil
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- GUR-DASH-001: Jadwal Mengajar Hari Ini --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Jadwal Mengajar Hari Ini</h3>
                </div>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">{{ $todaySchedules->count() }} Jadwal</span>
            </div>
            <div class="p-5">
                @if($todaySchedules->count() > 0)
                    <div class="space-y-3">
                        @foreach($todaySchedules as $schedule)
                            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-100 dark:border-slate-700/60">
                                <div class="shrink-0 w-16 text-center py-1 px-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ $schedule->teachingAssignment->subject->name ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                        Kelas: {{ $schedule->teachingAssignment->classRoom->gradeLevel->name ?? '' }} {{ $schedule->teachingAssignment->classRoom->name ?? '' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tidak ada jadwal mengajar hari ini.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- GUR-DASH-002: Status Guru Piket Hari Ini --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Status Piket Hari Ini</h3>
                </div>
            </div>
            <div class="p-5">
                @if($isOnDutyToday)
                    <div class="flex items-start gap-4 p-4 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60">
                        <div class="shrink-0 w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">Anda bertugas piket hari ini!</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Gunakan scanner kamera untuk mencatat absensi masuk dan pulang siswa.</p>
                            <a href="{{ route('guru.school-attendance-scanner.index') }}"
                               class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition-colors shadow-2xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Buka Scanner Absensi
                            </a>
                        </div>
                    </div>
                @else
                    <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-100 dark:border-slate-700/60">
                        <div class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 dark:text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Tidak sedang piket</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Anda tidak terjadwal sebagai Guru Piket pada hari ini.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- GUR-DASH-003: Ringkasan Dokumen (Placeholder) --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden lg:col-span-2">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/80 flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Perangkat Ajar & Dokumen Guru</h3>
            </div>
            <div class="p-5">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 flex items-center justify-center text-blue-500 dark:text-blue-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Repositori Dokumen (Fase Integrasi)</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            Pengelolaan dokumen RPP, ATP, PROTA, dan PROSEM siap diintegrasikan dengan Google Drive API setelah konfigurasi cloud storage diaktifkan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links Zen CBT Style --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs p-6">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Aksi Cepat Menu Utama</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <a href="{{ route('guru.attendance.index') }}"
               class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/70 dark:border-slate-700/70 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all group">
                <div class="p-3 rounded-xl bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-2xs group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors block">Absensi Siswa</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Input kehadiran kelas & mapel</span>
                </div>
            </a>

            <a href="{{ route('guru.school-attendance-scanner.index') }}"
               class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/70 dark:border-slate-700/70 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:border-emerald-200 dark:hover:border-emerald-800 transition-all group">
                <div class="p-3 rounded-xl bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-2xs group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors block">Scanner Piket</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Scan barcode masuk/pulang</span>
                </div>
            </a>

            <a href="{{ route('guru.profile.show') }}"
               class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-200/70 dark:border-slate-700/70 hover:bg-purple-50 dark:hover:bg-purple-950/40 hover:border-purple-200 dark:hover:border-purple-800 transition-all group">
                <div class="p-3 rounded-xl bg-white dark:bg-slate-800 text-purple-600 dark:text-purple-400 shadow-2xs group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors block">Profil Saya</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Biodata, kontak, dan sandi</span>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
