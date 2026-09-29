@extends('layouts.scanner')

@section('title', 'Scanner Absensi Siswa')
@section('subtitle', 'Guru Piket — Absensi Masuk/Pulang Siswa')

{{--
    Panel scanner (PRD 01 §7.4 GUR-PIK-002..005, ADDENDUM §1–§7, AC-SCAN-01..04).

    Yang TIDAK ada di halaman ini (dan memang tidak boleh ada):
    - Input tanggal/jam: tanggal & waktu selalu dari clock server (ADDENDUM §2).
    - Input operator / role / id guru: operator diambil dari sesi login oleh
      service (PRD 04 §3.2.4). Tidak ada field tersembunyi untuk itu.
    - Auto-detect mode: guru memilih MASUK atau PULANG secara eksplisit
      (ADDENDUM §1, ADR 18).
    - Aksi koreksi/hapus/riwayat umum: bukan hak Guru Piket (PRD 01 §13.7).

    Semua keluaran memakai {{ }} (di-escape Blade) — tidak ada {!! !!} (PRD 04 §8).
--}}
@section('content')
    <div class="grid gap-5">
        {{-- Status otoritas scanner: hasil revalidasi server-side per request. --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs px-6 py-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Akses Scanner Aktif</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $teacher->full_name }}</h2>
                    @if ($teacher->nip)
                        <p class="mt-0.5 text-xs text-slate-400 font-mono">NIP {{ $teacher->nip }}</p>
                    @endif
                </div>
                <dl class="text-right text-sm">
                    <dt class="text-xs font-semibold text-slate-500">Tanggal server</dt>
                    <dd class="mt-1 font-mono font-bold text-slate-900">{{ $serverDate }}</dd>
                    <dt class="mt-2 text-xs font-semibold text-slate-500">Jadwal piket</dt>
                    <dd class="mt-1 text-xs font-semibold text-emerald-700">
                        #{{ $dutySchedule?->id ?? '—' }} (tersimpan di database)
                    </dd>
                </dl>
            </div>
        </div>

        <form id="scan-form" method="POST" action="{{ route('guru.school-attendance-scanner.store') }}"
              class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            @csrf

            {{-- SATUNYA mode yang dikirim ke server; dipilih eksplisit oleh guru. --}}
            <input type="hidden" name="mode" id="scan-mode" value="{{ $activeMode }}">

            <div class="px-6 py-5 border-b border-slate-200">
                <p class="text-sm font-semibold text-slate-700">1. Pilih mode scan</p>
                <p class="mt-1 text-xs text-slate-500">
                    Mode tidak disimpulkan otomatis — scan pertama belum tentu MASUK.
                </p>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    @foreach ($modes as $option)
                        <button type="button"
                                class="scan-mode-button rounded-xl border px-4 py-3 text-base font-bold tracking-wide transition"
                                data-mode="{{ $option }}"
                                aria-pressed="{{ $option === $activeMode ? 'true' : 'false' }}">
                            [ {{ $option }} ]
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="px-6 py-5 border-b border-slate-200">
                <p class="text-sm font-semibold text-slate-700">2. Pindai barcode kartu siswa</p>
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-900 overflow-hidden">
                    {{-- html5-qrcode merender video kamera ke elemen ini (ADR 20, Keputusan B2).
                         Kamera hanya dipakai untuk membaca kode; tidak ada rekaman yang disimpan. --}}
                    <div id="qr-reader" class="w-full"></div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="button" id="camera-start"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        Nyalakan Kamera
                    </button>
                    <button type="button" id="camera-stop"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Matikan Kamera
                    </button>
                    <p id="camera-status" class="text-xs text-slate-500" role="status">Kamera belum aktif.</p>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Kamera butuh HTTPS (atau localhost) dan izin dari browser. Bila kamera tidak
                    tersedia, gunakan input manual di bawah — keduanya mengirim nilai yang sama.
                </p>
            </div>

            <div class="px-6 py-5">
                <label for="scan-barcode" class="text-sm font-semibold text-slate-700">3. Kode barcode / NIS</label>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <input type="text" id="scan-barcode" name="barcode"
                           value="{{ old('barcode') }}"
                           maxlength="50" autocomplete="off" spellcheck="false" inputmode="text"
                           placeholder="mis. 26270701"
                           class="flex-1 min-w-[12rem] rounded-lg border-slate-300 bg-white font-mono text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100">
                    <button type="submit" id="scan-submit"
                            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 transition">
                        Proses Scan
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Hasil pemindaian kamera terisi otomatis ke kolom ini. Nilai scan tidak
                    pernah disimpan di perangkat Anda.
                </p>
            </div>
        </form>

        {{-- Recak 5 scan terakhir oleh guru ini pada tanggal berjalan (UX, bukan wewenang baca umum). --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Recak scan Anda hari ini</h3>
                <p class="mt-0.5 text-xs text-slate-500">5 pemindaian terakhir oleh Anda pada tanggal server {{ $serverDate }}.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Siswa</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">NIS</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">MASUK</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">PULANG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($lastScan as $row)
                            <tr>
                                <td class="px-6 py-3">
                                    <span class="font-semibold text-slate-900">{{ $row['nama'] }}</span>
                                    @if ($row['dikoreksi'])
                                        <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700">dikoreksi admin</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $row['nis'] ?? '—' }}</td>
                                <td class="px-6 py-3 font-mono text-xs text-slate-700">{{ $row['masuk'] ?? '—' }}</td>
                                <td class="px-6 py-3 font-mono text-xs text-slate-700">{{ $row['pulang'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
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
