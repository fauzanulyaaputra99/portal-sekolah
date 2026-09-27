@extends('layouts.admin')

@section('title', 'Tambah Jadwal Guru Piket')
@section('subtitle', 'Administrasi — Jadwal Piket Guru / Tambah')

@section('content')
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-5 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Tambah Jadwal Piket</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Guru yang dijadwalkan pada tanggal berikut otomatis memiliki hak akses scanner
                    pada tanggal tersebut. Tidak ada role baru yang dibuat.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.duty-schedules.store') }}" class="px-6 py-6 space-y-5">
                @csrf

                @include('admin.duty-schedules._form')

                <div class="pt-2 flex items-center gap-3">
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        Simpan Jadwal
                    </button>
                    <a href="{{ route('admin.duty-schedules.index') }}"
                       class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
