@extends('layouts.guru')

@section('title', 'Profil Saya')
@section('subtitle', 'Area Guru — Profil')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Profil Saya</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Informasi pribadi dan riwayat penugasan mengajar</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('guru.profile.edit') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-colors shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Kontak
            </a>
            <a href="{{ route('guru.profile.password') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Ubah Password
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Biodata Card --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 px-6 py-8 text-center">
                    <div class="w-20 h-20 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-3 shadow-inner">
                        <span class="text-3xl font-bold text-white">
                            {{ strtoupper(substr($teacher->full_name ?? '?', 0, 1)) }}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white leading-tight">{{ $teacher->full_name ?? 'N/A' }}</h3>
                    <p class="text-indigo-200 text-xs mt-1 font-mono">{{ $teacher->nip ?? 'NIP belum diisi' }}</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Username</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium mt-0.5">{{ $user->username }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Email</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium mt-0.5">{{ $user->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Jenis Kelamin</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium mt-0.5">
                            @if($teacher->gender === 'L')
                                Laki-laki
                            @elseif($teacher->gender === 'P')
                                Perempuan
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">No. Telepon / WA</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium mt-0.5">{{ $teacher->phone_number ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status Kepegawaian</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium mt-0.5">{{ $teacher->employment_status ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Alamat</p>
                        <p class="text-sm text-slate-900 dark:text-white mt-0.5">{{ $teacher->address ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Penugasan --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/75 dark:bg-slate-900/50">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Riwayat Penugasan Mengajar
                    </h3>
                </div>
                <div class="p-6">
                    @if($teacher->teachingAssignments && $teacher->teachingAssignments->count() > 0)
                        <div class="space-y-3">
                            @foreach($teacher->teachingAssignments as $assignment)
                                <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                            {{ $assignment->subject->name ?? 'N/A' }}
                                        </h4>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                                            {{ $assignment->classRoom->gradeLevel->name ?? '' }}
                                            {{ $assignment->classRoom->name ?? '' }}
                                        </p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                                            Tahun Ajaran: {{ $assignment->academicYear->name ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Belum ada penugasan</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Riwayat penugasan mengajar akan muncul di sini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
