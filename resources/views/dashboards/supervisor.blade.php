@extends('layouts.admin')

@section('title', 'Dashboard Supervisor')
@section('subtitle', 'Area Supervisor')

@section('content')
<div class="space-y-6">
    {{-- Sapaan --}}
    <div>
        <h2 class="text-2xl font-bold text-slate-900">
            Selamat datang, {{ $user->username }}
        </h2>
        <p class="text-sm text-slate-500 mt-1">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- SUP-DASH-001: Statistik Guru --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Statistik Guru</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Total Guru</span>
                    <span class="text-lg font-bold text-slate-900">{{ $totalTeachers }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Guru Aktif</span>
                    <span class="text-lg font-bold text-emerald-600">{{ $activeTeachers }}</span>
                </div>
                @if($totalTeachers > 0)
                <div class="pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ round(($activeTeachers / $totalTeachers) * 100) }}%"></div>
                        </div>
                        <span class="text-xs text-slate-500">{{ round(($activeTeachers / $totalTeachers) * 100) }}%</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Rasio guru aktif</p>
                </div>
                @endif
            </div>
        </div>

        {{-- SUP-DASH-002: Ringkasan Dokumen (Placeholder) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Status Dokumen Guru</h3>
                <span class="text-xs px-2 py-1 bg-amber-100 text-amber-700 rounded-full font-medium">Menunggu A-03</span>
            </div>
            <div class="p-5">
                @if($documentStats['total'] === 0)
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm text-slate-500">Metrik dokumen akan tersedia setelah integrasi Google Drive (A-03) selesai.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Draft</span>
                            <span class="font-semibold text-slate-900">{{ $documentStats['draft'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Dikirim</span>
                            <span class="font-semibold text-blue-600">{{ $documentStats['submitted'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Disetujui</span>
                            <span class="font-semibold text-emerald-600">{{ $documentStats['approved'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Perlu Revisi</span>
                            <span class="font-semibold text-rose-600">{{ $documentStats['revision_requested'] }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Aksi Cepat</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('supervisor.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Monitoring Dokumen
            </a>
            <span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 text-slate-400 text-sm font-medium rounded-lg cursor-not-allowed" title="Menunggu A-03 GoogleDriveService">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Review Dokumen (Segera)
            </span>
        </div>
    </div>
</div>
@endsection
