@extends('layouts.guru')

@section('title', 'Detail Sesi Absensi')
@section('subtitle', 'Area Guru — Detail Sesi')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('guru.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('guru.attendance.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Absensi Siswa</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 dark:text-white font-medium">Detail Sesi</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Detail Sesi Absensi</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300 mt-1 font-medium">
                {{ $session->teachingAssignment->subject->name ?? 'N/A' }} —
                {{ $session->teachingAssignment->classRoom->gradeLevel->name ?? '' }}
                {{ $session->teachingAssignment->classRoom->name ?? '' }}
            </p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                {{ \Carbon\Carbon::parse($session->session_date)->format('d M Y') }} • Pertemuan Ke-{{ $session->meeting_order }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($session->status === 'OPEN')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Sesi Terbuka
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                    Sesi Tertutup
                </span>
            @endif
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/80 p-4 text-center shadow-2xs">
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $statistics['total'] }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Total Siswa</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-emerald-200 dark:border-emerald-800/80 p-4 text-center shadow-2xs">
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $statistics['hadir'] }}</p>
            <p class="text-xs text-emerald-700 dark:text-emerald-400 font-medium mt-1">Hadir</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 dark:border-blue-800/80 p-4 text-center shadow-2xs">
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $statistics['izin'] }}</p>
            <p class="text-xs text-blue-700 dark:text-blue-400 font-medium mt-1">Izin</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-800/80 p-4 text-center shadow-2xs">
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $statistics['sakit'] }}</p>
            <p class="text-xs text-amber-700 dark:text-amber-400 font-medium mt-1">Sakit</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-rose-200 dark:border-rose-800/80 p-4 text-center shadow-2xs">
            <p class="text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $statistics['alpha'] }}</p>
            <p class="text-xs text-rose-700 dark:text-rose-400 font-medium mt-1">Alpha</p>
        </div>
    </div>

    {{-- Session Info --}}
    @if($session->notes || $session->opened_at || $session->closed_at)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs p-5 space-y-3">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Informasi Sesi</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                @if($session->opened_at)
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Dibuka:</span>
                        <span class="ml-2 text-slate-900 dark:text-white font-medium">{{ \Carbon\Carbon::parse($session->opened_at)->format('d M Y H:i') }}</span>
                    </div>
                @endif
                @if($session->closed_at)
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Ditutup:</span>
                        <span class="ml-2 text-slate-900 dark:text-white font-medium">{{ \Carbon\Carbon::parse($session->closed_at)->format('d M Y H:i') }}</span>
                    </div>
                @endif
                @if($session->notes)
                    <div class="sm:col-span-2">
                        <span class="text-slate-500 dark:text-slate-400">Catatan:</span>
                        <p class="mt-1 text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-900/60 rounded-xl p-3 text-sm border border-slate-100 dark:border-slate-800">{{ $session->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Attendance Records Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/75 dark:bg-slate-900/50">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Daftar Kehadiran Siswa</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50/50 dark:bg-slate-900/30 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="px-4 py-3.5 w-12 text-center">#</th>
                        <th class="px-4 py-3.5">Nama Siswa</th>
                        <th class="px-4 py-3.5 text-center w-28">Status</th>
                        <th class="px-4 py-3.5">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($session->records as $index => $record)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/20 transition-colors">
                            <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400 font-mono text-xs text-center">{{ $index + 1 }}</td>
                            <td class="px-4 py-3.5">
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $record->student->full_name ?? 'N/A' }}</span>
                                @if($record->is_corrected)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">DIKOREKSI ADMIN</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @switch($record->status)
                                    @case('HADIR')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">Hadir</span>
                                        @break
                                    @case('IZIN')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300">Izin</span>
                                        @break
                                    @case('SAKIT')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">Sakit</span>
                                        @break
                                    @case('ALPHA')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">Alpha</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">{{ $record->status }}</span>
                                @endswitch
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">
                                {{ $record->notes ?? '-' }}
                                @if($record->is_corrected && $record->correction_reason)
                                    <div class="mt-1 text-xs text-rose-600 dark:text-rose-400 italic">
                                        Koreksi: {{ $record->correction_reason }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                Tidak ada data kehadiran dalam sesi ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
        <a href="{{ route('guru.attendance.index') }}"
           class="text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-medium transition-colors">
            ← Kembali ke Daftar Sesi
        </a>
        <div class="flex items-center gap-3">
            @if($isEditable)
                <a href="{{ route('guru.attendance.edit', $session->id) }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Kehadiran
                </a>
            @elseif($session->status === 'CLOSED' && !$statistics['corrected'])
                <form method="POST" action="{{ route('guru.attendance.reopen', $session->id) }}" class="inline"
                      onsubmit="return confirm('Buka kembali sesi ini untuk koreksi?');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        Buka Kembali
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
