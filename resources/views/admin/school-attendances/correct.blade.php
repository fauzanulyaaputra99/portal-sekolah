@extends('layouts.admin')

@section('title', 'Koreksi Absensi Siswa')
@section('subtitle', 'Administrasi — Koreksi Manual Absensi Masuk/Pulang (ADM-ABS-003)')

{{--
    Form koreksi manual oleh Admin/TU (PRD 01 §6.9 ADM-ABS-003, §13.7;
    PRD 02 §5 alur; PRD 05 §5.10). Ini adalah SATU-SATUNYA jalur resmi untuk
    kasus "PULANG tanpa catatan MASUK" yang ditolak scanner
    [FINAL — KEPUTUSAN A1]: sistem dilarang membuat check_in palsu lewat scan,
    jadi nilai jam diisi manual oleh Admin/TU dengan alasan + audit.

    Yang sengaja TIDAK ada pada form ini:
    - input tanggal record (attendance_date tidak dapat dipindah; lihat
      AttendanceCorrectionService);
    - input nama actor / waktu koreksi / operator scan: actor dari sesi login,
      corrected_at dari clock server, operator scan historis tetap utuh
      (PRD 01 §13.7 "histori terkunci");
    - field mode scan: scan_mode_* disinkronkan service, tidak dari payload.

    Rute PATCH juga dijaga middleware `role:admin` + Policy::correct(), jadi
    Supervisor yang mengetik URL ini menerima 403 (PRD 01 §5.4).
--}}
@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-5 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Koreksi Absensi</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $attendance->student?->full_name ?? '(siswa tidak ditemukan)' }}
                    — NIS <span class="font-mono">{{ $attendance->student?->nis ?? '—' }}</span>
                    · tanggal <span class="font-mono">{{ $attendance->attendance_date?->format('d/m/Y') }}</span>
                </p>
                <p class="mt-2 text-xs text-slate-400">
                    Tanggal record tidak dapat diubah. Nilai lama tersimpan utuh pada
                    <span class="font-mono">audit_logs.old_values</span> sebelum dan sesudah koreksi.
                </p>
            </div>

            {{-- Jejak asli (read-only) --}}
            <dl class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-3 border-b border-slate-200 bg-slate-50/60 text-sm">
                <div class="p-4 rounded-xl bg-white border border-slate-200">
                    <dt class="text-xs font-semibold text-slate-500">MASUK tercatat</dt>
                    <dd class="mt-1 font-mono font-semibold text-slate-900">
                        {{ $attendance->check_in_time?->format('Y-m-d H:i') ?? '(kosong)' }}
                    </dd>
                    <dd class="mt-1 text-xs text-slate-500">
                        Operator: {{ $attendance->checkInByTeacher?->full_name ?? '—' }}
                        · mode <span class="font-mono">{{ $attendance->scan_mode_in ?? '—' }}</span>
                    </dd>
                </div>
                <div class="p-4 rounded-xl bg-white border border-slate-200">
                    <dt class="text-xs font-semibold text-slate-500">PULANG tercatat</dt>
                    <dd class="mt-1 font-mono font-semibold text-slate-900">
                        {{ $attendance->check_out_time?->format('Y-m-d H:i') ?? '(kosong)' }}
                    </dd>
                    <dd class="mt-1 text-xs text-slate-500">
                        Operator: {{ $attendance->checkOutByTeacher?->full_name ?? '—' }}
                        · mode <span class="font-mono">{{ $attendance->scan_mode_out ?? '—' }}</span>
                    </dd>
                </div>
                @if ($attendance->is_corrected)
                    <div class="sm:col-span-2 p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <dt class="text-xs font-semibold text-amber-700">Koreksi sebelumnya</dt>
                        <dd class="mt-1 text-xs text-amber-800">
                            Oleh <span class="font-semibold">{{ $attendance->correctedByUser?->username ?? '—' }}</span>
                            pada <span class="font-mono">{{ $attendance->corrected_at?->format('Y-m-d H:i') ?? '—' }}</span>
                            — alasan: {{ $attendance->correction_reason ?: '(tanpa alasan tersimpan)' }}
                        </dd>
                    </div>
                @endif
            </dl>

            <form method="POST" action="{{ route('admin.school-attendances.update', $attendance) }}"
                  class="px-6 py-6 space-y-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="check_in_time" class="block text-sm font-semibold text-slate-700">Jam MASUK</label>
                        <input type="datetime-local" id="check_in_time" name="check_in_time" step="60"
                               value="{{ old('check_in_time', $attendance->check_in_time?->format('Y-m-d\TH:i')) }}"
                               class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100">
                        <p class="mt-1 text-xs text-slate-400">Kosongkan untuk menghapus catatan masuk (pulang harus dikosongkan lebih dulu).</p>
                    </div>
                    <div>
                        <label for="check_out_time" class="block text-sm font-semibold text-slate-700">Jam PULANG</label>
                        <input type="datetime-local" id="check_out_time" name="check_out_time" step="60"
                               value="{{ old('check_out_time', $attendance->check_out_time?->format('Y-m-d\TH:i')) }}"
                               class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100">
                        <p class="mt-1 text-xs text-slate-400">Tidak boleh lebih awal dari jam masuk.</p>
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-semibold text-slate-700">
                        Alasan koreksi <span class="text-rose-600">*</span>
                    </label>
                    <textarea id="reason" name="reason" rows="4" required
                              minlength="{{ $minReason }}"
                              class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100"
                              placeholder="mis. Siswa datang sakit dan izin, scan masuk tidak dilakukan di gerbang.">{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">
                        Wajib diisi minimal {{ $minReason }} karakter. Alasan disimpan pada record dan pada
                        <span class="font-mono">audit_logs</span> (aksi SCHOOL_ATT_CORRECT).
                    </p>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <button type="submit"
                            class="rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 transition">
                        Simpan Koreksi
                    </button>
                    <a href="{{ route('admin.school-attendances.index', ['date' => $attendance->attendance_date?->toDateString()]) }}"
                       class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Batal
                    </a>
                </div>
            </form>

            <div class="px-6 pb-6">
                <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-xl p-3">
                    Waktu koreksi ditafsirkan pada zona waktu server (<span class="font-mono">Asia/Jakarta</span>)
                    dan actor koreksi diambil dari sesi login Admin/TU — keduanya tidak dapat dikirim lewat formulir ini.
                </p>
            </div>
        </div>
    </div>
@endsection
