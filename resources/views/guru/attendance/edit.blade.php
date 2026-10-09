@extends('layouts.guru')

@section('title', 'Input Kehadiran Siswa')
@section('subtitle', 'Area Guru — Input Kehadiran')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('guru.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('guru.attendance.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Absensi Siswa</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 dark:text-white font-medium">Input Kehadiran</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Input Kehadiran Siswa</h2>
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
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/80 p-3.5 text-center shadow-2xs">
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $statistics['total'] }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Siswa</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-emerald-200 dark:border-emerald-800/80 p-3.5 text-center shadow-2xs">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $statistics['hadir'] }}</p>
            <p class="text-xs text-emerald-700 dark:text-emerald-400 font-medium">Hadir</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 dark:border-blue-800/80 p-3.5 text-center shadow-2xs">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $statistics['izin'] }}</p>
            <p class="text-xs text-blue-700 dark:text-blue-400 font-medium">Izin</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-800/80 p-3.5 text-center shadow-2xs">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $statistics['sakit'] }}</p>
            <p class="text-xs text-amber-700 dark:text-amber-400 font-medium">Sakit</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-rose-200 dark:border-rose-800/80 p-3.5 text-center shadow-2xs">
            <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $statistics['alpha'] }}</p>
            <p class="text-xs text-rose-700 dark:text-rose-400 font-medium">Alpha</p>
        </div>
    </div>

    {{-- Attendance Form --}}
    <form method="POST" action="{{ route('guru.attendance.update', $session) }}" id="attendanceForm">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50/75 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-4 py-3.5 w-12 text-center">#</th>
                            <th class="px-4 py-3.5">Nama Siswa</th>
                            <th class="px-4 py-3.5 text-center w-20">H</th>
                            <th class="px-4 py-3.5 text-center w-20">I</th>
                            <th class="px-4 py-3.5 text-center w-20">S</th>
                            <th class="px-4 py-3.5 text-center w-20">A</th>
                            <th class="px-4 py-3.5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @forelse($session->records as $index => $record)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/20 transition-colors attendance-row" data-student-id="{{ $record->student_id }}">
                                <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400 font-mono text-xs text-center">{{ $index + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $record->student->full_name ?? 'N/A' }}</span>
                                    @if($record->is_corrected)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">DIKOREKSI</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <input type="radio" name="attendance[{{ $record->student_id }}][status]" value="HADIR"
                                           {{ ($record->status === 'HADIR') ? 'checked' : '' }}
                                           class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-600 attendance-radio"
                                           data-student="{{ $record->student_id }}" data-status="HADIR">
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <input type="radio" name="attendance[{{ $record->student_id }}][status]" value="IZIN"
                                           {{ ($record->status === 'IZIN') ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 attendance-radio"
                                           data-student="{{ $record->student_id }}" data-status="IZIN">
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <input type="radio" name="attendance[{{ $record->student_id }}][status]" value="SAKIT"
                                           {{ ($record->status === 'SAKIT') ? 'checked' : '' }}
                                           class="w-4 h-4 text-amber-600 focus:ring-amber-500 border-slate-300 dark:border-slate-600 attendance-radio"
                                           data-student="{{ $record->student_id }}" data-status="SAKIT">
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <input type="radio" name="attendance[{{ $record->student_id }}][status]" value="ALPHA"
                                           {{ ($record->status === 'ALPHA') ? 'checked' : '' }}
                                           class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-slate-300 dark:border-slate-600 attendance-radio"
                                           data-student="{{ $record->student_id }}" data-status="ALPHA">
                                </td>
                                <td class="px-4 py-3.5">
                                    <input type="text" name="attendance[{{ $record->student_id }}][notes]"
                                           value="{{ $record->notes }}"
                                           placeholder="Keterangan opsional..."
                                           maxlength="255"
                                           class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                    Tidak ada data siswa dalam sesi ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- Form terpisah untuk Tutup Sesi (mencegah nested form) --}}
    @if($session->status === 'OPEN')
        <form id="closeSessionForm" method="POST" action="{{ route('guru.attendance.close', $session) }}" class="hidden"
              onsubmit="return confirm('Apakah Anda yakin ingin menutup sesi ini? Setelah ditutup, sesi menjadi read-only.');">
            @csrf
        </form>
    @endif

    {{-- Actions Bar --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
        <a href="{{ route('guru.attendance.index') }}"
           class="text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-medium transition-colors">
            ← Kembali ke Daftar Sesi
        </a>
        <div class="flex items-center gap-3">
            @if($session->status === 'OPEN')
                <button type="submit" form="attendanceForm"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Kehadiran
                </button>

                <button type="button" onclick="if(confirm('Apakah Anda yakin ingin menutup sesi ini? Setelah ditutup, sesi menjadi read-only.')) { document.getElementById('closeSessionForm').submit(); }"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Tutup Sesi
                </button>
            @else
                <a href="{{ route('guru.attendance.show', $session) }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-colors">
                    Lihat Detail
                </a>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function initAttendanceRadioListeners() {
        document.querySelectorAll('.attendance-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                const row = this.closest('tr');
                row.classList.remove('bg-emerald-50/50', 'dark:bg-emerald-950/20', 'bg-blue-50/50', 'dark:bg-blue-950/20', 'bg-amber-50/50', 'dark:bg-amber-950/20', 'bg-rose-50/50', 'dark:bg-rose-950/20');
                
                const status = this.dataset.status;
                if (status === 'HADIR') {
                    row.classList.add('bg-emerald-50/50', 'dark:bg-emerald-950/20');
                } else if (status === 'IZIN') {
                    row.classList.add('bg-blue-50/50', 'dark:bg-blue-950/20');
                } else if (status === 'SAKIT') {
                    row.classList.add('bg-amber-50/50', 'dark:bg-amber-950/20');
                } else if (status === 'ALPHA') {
                    row.classList.add('bg-rose-50/50', 'dark:bg-rose-950/20');
                }
            });
        });

        // Initialize row colors on load
        document.querySelectorAll('.attendance-radio:checked').forEach(radio => {
            const row = radio.closest('tr');
            const status = radio.dataset.status;
            if (status === 'HADIR') {
                row.classList.add('bg-emerald-50/50', 'dark:bg-emerald-950/20');
            } else if (status === 'IZIN') {
                row.classList.add('bg-blue-50/50', 'dark:bg-blue-950/20');
            } else if (status === 'SAKIT') {
                row.classList.add('bg-amber-50/50', 'dark:bg-amber-950/20');
            } else if (status === 'ALPHA') {
                row.classList.add('bg-rose-50/50', 'dark:bg-rose-950/20');
            }
        });
    }

    document.addEventListener('turbo:load', initAttendanceRadioListeners);
    if (document.readyState !== 'loading') {
        initAttendanceRadioListeners();
    } else {
        document.addEventListener('DOMContentLoaded', initAttendanceRadioListeners);
    }
</script>
@endpush
@endsection
