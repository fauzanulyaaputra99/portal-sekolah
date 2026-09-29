@extends('layouts.admin')

@section('title', 'Absensi Masuk/Pulang Siswa')
@section('subtitle', 'Administrasi — Monitoring Absensi Sekolah (PRD 01 §6.9 ADM-ABS-001)')

{{--
    Rekap harian absensi masuk/pulang siswa.
    - Filter tanggal/status hanyalah PEMFILTER data, BUKAN kontrol akses:
      otorisasi ditegakkan middleware `role:admin,supervisor` +
      SchoolAttendancePolicy::viewAny (PRD 04 §2/§3.2).
    - Tautan "Koreksi" hanya muncul untuk pemegang hak koreksi (Admin/TU)
      lewat @can('correct', ...) = Policy; pada Supervisor link tidak dirender
      dan tetap 403 bila URL diketik manual (PRD 01 §5.4, §13.7).
    - Semua keluaran {{ }} (di-escape). Tidak ada {!! !!} (PRD 04 §8).
--}}
@section('content')
    {{-- Statistik harian --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs px-5 py-4">
            <p class="text-xs font-semibold text-slate-500">Siswa Aktif</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['aktif'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs px-5 py-4">
            <p class="text-xs font-semibold text-slate-500">Record Hari Ini</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['tercatat'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200/80 shadow-xs px-5 py-4 bg-emerald-50/60">
            <p class="text-xs font-semibold text-emerald-700">Sudah MASUK</p>
            <p class="mt-1 text-2xl font-bold text-emerald-800">{{ $stats['masuk'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-200/80 shadow-xs px-5 py-4 bg-indigo-50/60">
            <p class="text-xs font-semibold text-indigo-700">Sudah PULANG</p>
            <p class="mt-1 text-2xl font-bold text-indigo-800">{{ $stats['pulang'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-200/80 shadow-xs px-5 py-4 bg-rose-50/60">
            <p class="text-xs font-semibold text-rose-700">Belum MASUK</p>
            <p class="mt-1 text-2xl font-bold text-rose-800">{{ $stats['belum'] }}</p>
        </div>
    </div>

    <div class="mt-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="px-6 py-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Rekap Absensi Masuk/Pulang Siswa</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Tanggal berjalan server: <span class="font-mono">{{ $today }}</span> (Asia/Jakarta).
                    Waktu scan, tanggal, dan operator selalu berasal dari server — bukan dari perangkat klien.
                </p>
            </div>
            <div class="text-sm">
                <p class="text-xs font-semibold text-slate-500">Guru Piket pada {{ $date }}</p>
                <ul class="mt-1 space-y-0.5 text-right">
                    @forelse ($guruPiket as $schedule)
                        <li class="font-semibold text-slate-800">{{ $schedule->teacher?->full_name ?? '(profil guru tidak ditemukan)' }}</li>
                    @empty
                        <li class="text-slate-500">Belum ada jadwal piket.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.school-attendances.index') }}"
              class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex flex-wrap items-end gap-3">
            <div>
                <label for="filter-date" class="block text-xs font-semibold text-slate-600">Tanggal</label>
                <input type="date" id="filter-date" name="date" value="{{ $filters['date'] }}"
                       class="mt-1 block rounded-lg border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100">
            </div>
            <div>
                <label for="filter-status" class="block text-xs font-semibold text-slate-600">Status</label>
                <select id="filter-status" name="status"
                        class="mt-1 block rounded-lg border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100">
                    <option value="">Semua status</option>
                    <option value="MASUK" @selected($filters['status'] === 'MASUK')>Sudah masuk, belum pulang</option>
                    <option value="SUDAH_PULANG" @selected($filters['status'] === 'SUDAH_PULANG')>Sudah pulang</option>
                    <option value="BELUM_MASUK" @selected($filters['status'] === 'BELUM_MASUK')>Record tanpa jam masuk</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Terapkan
                </button>
                <a href="{{ route('admin.school-attendances.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-800 transition">
                    Reset
                </a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Siswa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">MASUK</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Operator MASUK</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">PULANG</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Operator PULANG</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $record->student?->full_name ?? '(siswa tidak ditemukan)' }}</div>
                                <div class="text-xs text-slate-400 font-mono">
                                    NIS {{ $record->student?->nis ?? '—' }}
                                    @if ($record->student && $record->student->status !== 'AKTIF')
                                        · {{ $record->student->status }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-slate-700">{{ $record->check_in_time?->format('H:i') ?? '—' }}</span>
                                @if ($record->scan_mode_in)
                                    <span class="ml-1 text-[10px] font-bold uppercase text-slate-400">{{ $record->scan_mode_in }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $record->checkInByTeacher?->full_name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-slate-700">{{ $record->check_out_time?->format('H:i') ?? '—' }}</span>
                                @if ($record->scan_mode_out)
                                    <span class="ml-1 text-[10px] font-bold uppercase text-slate-400">{{ $record->scan_mode_out }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $record->checkOutByTeacher?->full_name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                @if ($record->is_corrected)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700">dikoreksi</span>
                                @endif
                                @can('correct', $record)
                                    <a href="{{ route('admin.school-attendances.correct', $record) }}"
                                       class="ml-1 inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                        Koreksi
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                Belum ada record absensi sekolah pada tanggal {{ $date }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $records->links() }}
            </div>
        @endif
    </div>

    <p class="mt-4 text-xs text-slate-400">
        Scan oleh Guru Piket tercatat pada <span class="font-mono">audit_logs</span> dengan aksi
        <span class="font-mono">SCHOOL_ATT_SCAN</span>; setiap koreksi Admin/TU tercatat sebagai
        <span class="font-mono">SCHOOL_ATT_CORRECT</span> lengkap dengan nilai lama, nilai baru,
        alasan, actor, dan waktu server (PRD 04 §9.1–§9.2).
    </p>
@endsection
