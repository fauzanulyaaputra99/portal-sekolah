@extends('layouts.guru')

@section('title', 'Scanner Absensi Siswa')
@section('subtitle', 'Guru Piket — Absensi Masuk/Pulang Siswa')

@section('content')
<div class="space-y-6">
    {{-- Status otoritas scanner: hasil revalidasi server-side per request. --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Akses Scanner Aktif
                </div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $teacher->full_name }}</h2>
                @if ($teacher->nip)
                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500 font-mono">NIP {{ $teacher->nip }}</p>
                @endif
            </div>
            <dl class="text-right text-sm">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tanggal server</dt>
                <dd class="mt-0.5 font-mono font-bold text-slate-900 dark:text-white">{{ $serverDate }}</dd>
                <dt class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Jadwal piket</dt>
                <dd class="mt-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                    #{{ $dutySchedule?->id ?? '—' }} (tersimpan di database)
                </dd>
            </dl>
        </div>
    </div>

    <form id="scan-form" method="POST" action="{{ route('guru.school-attendance-scanner.store') }}"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
        @csrf

        {{-- SATUNYA mode yang dikirim ke server; dipilih eksplisit oleh guru. --}}
        <input type="hidden" name="mode" id="scan-mode" value="{{ $activeMode }}">

        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
            <p class="text-sm font-bold text-slate-900 dark:text-white">1. Pilih mode scan</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Mode tidak disimpulkan otomatis — scan pertama belum tentu MASUK.
            </p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                @foreach ($modes as $option)
                    <button type="button"
                            class="scan-mode-button rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 px-4 py-3 text-sm font-bold tracking-wide transition text-slate-700 dark:text-slate-200 hover:border-indigo-400 dark:hover:border-indigo-500"
                            data-mode="{{ $option }}"
                            aria-pressed="{{ $option === $activeMode ? 'true' : 'false' }}">
                        [ {{ $option }} ]
                    </button>
                @endforeach
            </div>
        </div>

        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
            <p class="text-sm font-bold text-slate-900 dark:text-white">2. Pindai barcode kartu siswa</p>
            <div class="mt-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-950 overflow-hidden shadow-inner">
                <div id="qr-reader" class="w-full"></div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" id="camera-start"
                        class="rounded-xl bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition">
                    Nyalakan Kamera
                </button>
                <button type="button" id="camera-stop"
                        class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Matikan Kamera
                </button>
                <p id="camera-status" class="text-xs text-slate-500 dark:text-slate-400" role="status">Kamera belum aktif.</p>
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                Kamera butuh HTTPS (atau localhost) dan izin dari browser. Bila kamera tidak
                tersedia, gunakan input manual di bawah.
            </p>
        </div>

        <div class="px-6 py-5">
            <label for="scan-barcode" class="text-sm font-bold text-slate-900 dark:text-white">3. Kode barcode / NIS</label>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <input type="text" id="scan-barcode" name="barcode"
                       value="{{ old('barcode') }}"
                       maxlength="50" autocomplete="off" spellcheck="false" inputmode="text"
                       placeholder="mis. 26270701"
                       class="flex-1 min-w-[12rem] rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono text-sm shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                <button type="submit" id="scan-submit"
                        class="rounded-xl bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition">
                    Proses Scan
                </button>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Hasil pemindaian kamera terisi otomatis ke kolom ini.
            </p>
        </div>
    </form>

    {{-- Recak 5 scan terakhir oleh guru ini pada tanggal berjalan --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/75 dark:bg-slate-900/50">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recak scan Anda hari ini</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">5 pemindaian terakhir oleh Anda pada tanggal server {{ $serverDate }}.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700/60 text-sm">
                <thead class="bg-slate-50/50 dark:bg-slate-900/30 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left">Siswa</th>
                        <th scope="col" class="px-6 py-3.5 text-left">NIS</th>
                        <th scope="col" class="px-6 py-3.5 text-left">MASUK</th>
                        <th scope="col" class="px-6 py-3.5 text-left">PULANG</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse ($lastScan as $row)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/20 transition-colors">
                            <td class="px-6 py-3.5">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $row['nama'] }}</span>
                                @if ($row['dikoreksi'])
                                    <span class="ml-2 inline-flex rounded-full bg-amber-100 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400">dikoreksi admin</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $row['nis'] ?? '—' }}</td>
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-700 dark:text-slate-300">{{ $row['masuk'] ?? '—' }}</td>
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-700 dark:text-slate-300">{{ $row['pulang'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                Belum ada scan hari ini dari akun Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/scanner.js'])
@endpush
