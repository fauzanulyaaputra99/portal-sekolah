@extends('layouts.scanner')

@section('title', 'Scanner Terkunci')
@section('subtitle', 'Guru — Scanner Absensi Siswa (terkunci)')

{{--
    Panel terkunci (PRD 01 §7.4 GUR-PIK-001, PRD 04 §3.2.4 & §16.5).
    Dirender dengan status HTTP 403 oleh EnsureTeacherOnDuty / controller.
    Teks $message berasal dari konstanta service (teks persis PRD) dan
    dikeluarkan lewat {{ }} => di-escape. Tidak ada tombol scan di sini:
    satu-satunya aksi sah adalah keluar / kembali ke dashboard.
--}}
@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-10 text-center">
            <div class="mx-auto h-16 w-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center">
                <span class="text-3xl" aria-hidden="true">&#128274;</span>
            </div>

            <h2 class="mt-5 text-lg font-bold text-slate-900">Scanner Absensi Dikunci</h2>

            <p class="mt-3 text-sm font-semibold text-amber-800" role="alert">
                {{ $message }}
            </p>

            <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-left">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <dt class="text-xs font-semibold text-slate-500">Akun guru</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $teacherName ?? '—' }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <dt class="text-xs font-semibold text-slate-500">Tanggal server (Asia/Jakarta)</dt>
                    <dd class="mt-1 font-mono font-semibold text-slate-900">{{ $serverDate }}</dd>
                </div>
            </dl>

            <p class="mt-6 text-xs text-slate-500 max-w-md mx-auto">
                Status Guru Piket ditetapkan dari jadwal pada tabel
                <span class="font-mono">teacher_duty_schedules</span> untuk tanggal server,
                dan diperiksa ulang pada setiap permintaan. Bukan role tambahan dan bukan flag akun.
            </p>

            <div class="mt-7 flex items-center justify-center gap-3">
                <a href="{{ route('guru.dashboard') }}"
                   class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection
