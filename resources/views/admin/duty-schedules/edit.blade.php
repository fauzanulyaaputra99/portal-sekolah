@extends('layouts.admin')

@section('title', 'Ubah Jadwal Guru Piket')
@section('subtitle', 'Administrasi — Jadwal Piket Guru / Ubah')

@section('content')
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-5 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Ubah Jadwal Piket</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Jadwal saat ini:
                    <span class="font-semibold text-slate-700">{{ $schedule->schedule_date->format('d/m/Y') }}</span>
                    — <span class="font-semibold text-slate-700">{{ $schedule->teacher?->full_name ?? '(profil guru tidak ditemukan)' }}</span>
                </p>
            </div>

            <form method="POST" action="{{ route('admin.duty-schedules.update', $schedule) }}" class="px-6 py-6 space-y-5">
                @csrf
                @method('PUT')

                @include('admin.duty-schedules._form')

                <div class="pt-2 flex items-center gap-3">
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        Perbarui Jadwal
                    </button>
                    <a href="{{ route('admin.duty-schedules.index') }}"
                       class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Batal
                    </a>
                </div>
            </form>

            <div class="px-6 pb-6">
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3">
                    Perubahan hanya memengaruhi verifikasi akses scanner mulai sekarang.
                    Record absensi yang sudah tersimpan (operator, waktu, siswa) tidak diubah
                    oleh perubahan jadwal ini.
                </p>
            </div>
        </div>
    </div>
@endsection
