@extends('layouts.guru')

@section('title', 'Absensi Siswa')
@section('subtitle', 'Area Guru — Absensi Siswa per Mapel')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Absensi Siswa</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola sesi absensi untuk kelas yang Anda ampu</p>
        </div>
        <a href="{{ route('guru.attendance.create') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Buka Sesi Baru
        </a>
    </div>

    {{-- Filter Section --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 p-5 shadow-xs">
        <form method="GET" action="{{ route('guru.attendance.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label for="status" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Status Sesi</label>
                <select name="status" id="status" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="OPEN" {{ ($filters['status'] ?? '') === 'OPEN' ? 'selected' : '' }}>Terbuka</option>
                    <option value="CLOSED" {{ ($filters['status'] ?? '') === 'CLOSED' ? 'selected' : '' }}>Tertutup</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Dari Tanggal</label>
                <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="date_to" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Sampai Tanggal</label>
                <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                    Filter
                </button>
                <a href="{{ route('guru.attendance.index') }}" class="px-4 py-2.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-sm font-semibold rounded-xl transition-colors text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Sessions Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
        @if($sessions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50/75 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-5 py-3.5">Tanggal</th>
                            <th class="px-5 py-3.5">Mata Pelajaran</th>
                            <th class="px-5 py-3.5">Kelas</th>
                            <th class="px-5 py-3.5">Pertemuan</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach($sessions as $session)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-5 py-4 text-slate-900 dark:text-white font-medium">
                                    {{ \Carbon\Carbon::parse($session->session_date)->format('d M Y') }}
                                </td>
                                <td class="px-5 py-4 text-slate-700 dark:text-slate-300 font-medium">
                                    {{ $session->teachingAssignment->subject->name ?? '-' }}
                                </td>
                                <td class="px-5 py-4 text-slate-600 dark:text-slate-300">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium">
                                        {{ $session->teachingAssignment->classRoom->gradeLevel->name ?? '' }}
                                        {{ $session->teachingAssignment->classRoom->name ?? '' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600 dark:text-slate-400">
                                    Ke-{{ $session->meeting_order }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($session->status === 'OPEN')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Terbuka
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                            Tertutup
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($session->status === 'OPEN')
                                            <a href="{{ route('guru.attendance.edit', $session) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Input Kehadiran
                                            </a>
                                        @else
                                            <a href="{{ route('guru.attendance.show', $session) }}"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors">
                                                Lihat Detail
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40">
                {{ $sessions->appends(request()->query())->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Belum ada sesi absensi</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-5 max-w-sm mx-auto">Mulai dengan membuka sesi absensi baru untuk kelas yang Anda ampu.</p>
                <a href="{{ route('guru.attendance.create') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buka Sesi Pertama
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
