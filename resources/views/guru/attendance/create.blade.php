@extends('layouts.guru')

@section('title', 'Buka Sesi Absensi Baru')
@section('subtitle', 'Area Guru — Buka Sesi Absensi')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('guru.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('guru.attendance.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Absensi Siswa</a>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 dark:text-white font-medium">Buka Sesi Baru</span>
    </nav>

    {{-- Header --}}
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Buka Sesi Absensi</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pilih penugasan mengajar dan tanggal untuk membuka sesi absensi baru.</p>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('guru.attendance.store') }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-xs p-6 sm:p-8 space-y-6">
        @csrf

        {{-- Teaching Assignment Selection --}}
        <div>
            <label for="teaching_assignment_id" class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Penugasan Mengajar <span class="text-rose-500">*</span>
            </label>
            <select name="teaching_assignment_id" id="teaching_assignment_id" required
                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('teaching_assignment_id') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror">
                <option value="">-- Pilih Mata Pelajaran & Kelas --</option>
                @foreach($assignments as $assignment)
                    <option value="{{ $assignment->id }}" {{ (old('teaching_assignment_id') == $assignment->id || ($selectedAssignment && $selectedAssignment->id === $assignment->id)) ? 'selected' : '' }}>
                        {{ $assignment->subject->name ?? 'N/A' }} — {{ $assignment->classRoom->gradeLevel->name ?? '' }} {{ $assignment->classRoom->name ?? '' }}
                        ({{ $assignment->academicYear->name ?? 'N/A' }})
                    </option>
                @endforeach
            </select>
            @error('teaching_assignment_id')
                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Session Date --}}
        <div>
            <label for="session_date" class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Tanggal Sesi <span class="text-rose-500">*</span>
            </label>
            <input type="date" name="session_date" id="session_date" required
                   value="{{ old('session_date', now()->format('Y-m-d')) }}"
                   max="{{ now()->format('Y-m-d') }}"
                   class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('session_date') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror">
            @error('session_date')
                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Tanggal tidak boleh di masa depan.</p>
        </div>

        {{-- Notes --}}
        <div>
            <label for="notes" class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Catatan <span class="text-slate-400 font-normal">(opsional)</span>
            </label>
            <textarea name="notes" id="notes" rows="3" maxlength="500"
                      class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('notes') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                      placeholder="Catatan topik bahasan atau keterangan sesi...">{{ old('notes') }}</textarea>
            @error('notes')
                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Info Box --}}
        <div class="p-4 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-xs text-indigo-900 dark:text-indigo-300 leading-relaxed">
                    <p class="font-semibold mb-1">Informasi alur absensi:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-indigo-800 dark:text-indigo-300">
                        <li>Semua siswa di kelas ini awalnya otomatis berstatus <strong>HADIR</strong>.</li>
                        <li>Guru dapat mengubah status siswa menjadi <strong>IZIN</strong>, <strong>SAKIT</strong>, atau <strong>ALPHA</strong>.</li>
                        <li>Tutup sesi secara manual setelah jam pelajaran selesai.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buka Sesi Absensi
            </button>
            <a href="{{ route('guru.attendance.index') }}"
               class="px-5 py-2.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm font-medium transition-colors">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
