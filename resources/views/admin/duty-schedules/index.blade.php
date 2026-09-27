@extends('layouts.admin')

@section('title', 'Jadwal Guru Piket')
@section('subtitle', 'Administrasi — Jadwal Piket Guru (ADDENDUM §8–§18)')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="px-6 py-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Jadwal Guru Piket</h2>
                <p class="mt-1 text-sm text-slate-500 max-w-2xl">
                    Status <span class="font-semibold">Guru Piket</span> bukan role tambahan. Seorang guru menjadi
                    operator scanner hanya karena memiliki jadwal pada tabel ini untuk tanggal berjalan
                    (tanggal server: <span class="font-mono">{{ $today }}</span> — Asia/Jakarta).
                </p>
            </div>
            @can('create', App\Models\TeacherDutySchedule::class)
                <a href="{{ route('admin.duty-schedules.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Jadwal
                </a>
            @endcan
        </div>

        {{-- Filter: hanya pemfilter data, BUKAN kontrol akses. Otorisasi tetap server-side. --}}
        <form method="GET" action="{{ route('admin.duty-schedules.index') }}"
              class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex flex-wrap items-end gap-3">
            <div>
                <label for="filter-date" class="block text-xs font-semibold text-slate-600">Tanggal</label>
                <input type="date" id="filter-date" name="date" value="{{ $filters['date'] }}"
                       class="mt-1 block rounded-lg border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100">
            </div>
            <div>
                <label for="filter-teacher" class="block text-xs font-semibold text-slate-600">Guru</label>
                <select id="filter-teacher" name="teacher_id"
                        class="mt-1 block rounded-lg border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100">
                    <option value="">Semua guru</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected($filters['teacher_id'] === $teacher->id)>
                            {{ $teacher->full_name }}@if($teacher->nip) — {{ $teacher->nip }}@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Terapkan
                </button>
                <a href="{{ route('admin.duty-schedules.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-800 transition">
                    Reset
                </a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Guru</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Catatan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Dibuat oleh</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)
                        <tr class="{{ $schedule->schedule_date->toDateString() === $today ? 'bg-emerald-50/60' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-900">{{ $schedule->schedule_date->format('d/m/Y') }}</span>
                                @if ($schedule->schedule_date->toDateString() === $today)
                                    <span class="ml-2 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">hari ini</span>
                                @endif
                                <div class="text-xs text-slate-400">{{ $schedule->schedule_date->locale('id')->translatedFormat('l') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $schedule->teacher?->full_name ?? '(profil guru tidak ditemukan)' }}</div>
                                @if ($schedule->teacher?->nip)
                                    <div class="text-xs text-slate-400 font-mono">NIP {{ $schedule->teacher->nip }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600 max-w-xs">
                                {{ $schedule->notes ?: '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-slate-700">{{ $schedule->createdByUser?->username ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $schedule->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    @can('update', $schedule)
                                        <a href="{{ route('admin.duty-schedules.edit', $schedule) }}"
                                           class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                            Ubah
                                        </a>
                                    @endcan
                                    @can('delete', $schedule)
                                        <form method="POST"
                                              action="{{ route('admin.duty-schedules.destroy', $schedule) }}"
                                              onsubmit="return confirm('Hapus jadwal piket tanggal {{ $schedule->schedule_date->format('d/m/Y') }} ini? Histori absensi yang sudah tercatat tidak akan berubah.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition">
                                                Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                Belum ada jadwal piket.
                                @can('create', App\Models\TeacherDutySchedule::class)
                                    Klik <span class="font-semibold">Tambah Jadwal</span> untuk menetapkan Guru Piket.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($schedules->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>

    <p class="mt-4 text-xs text-slate-400">
        Setiap tambah/ubah/hapus jadwal tercatat pada <span class="font-mono">audit_logs</span>
        dengan aksi <span class="font-mono">DUTY_SCHEDULE_CREATE / UPDATE / DELETE</span>
        (actor, tanggal jadwal, guru lama, guru baru, timestamp server).
    </p>
@endsection
