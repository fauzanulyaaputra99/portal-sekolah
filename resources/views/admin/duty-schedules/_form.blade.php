{{--
    Field bersama form Jadwal Guru Piket (dipakai create & edit).
    Varian: $teachers (koleksi dari DB), $today (tanggal server), $schedule (nullable
    — hanya dikirim oleh edit.blade.php).

    Keamanan: TIDAK ada field tersembunyi pembawa actor/role/user_id.
    Actor (created_by_user_id) diisi server-side dari sesi login di service,
    dan kewenangan ditegakkan middleware role:admin + Gate di controller.
--}}
@php
    $current = $schedule ?? null;
@endphp

<div>
    <label for="teacher_id" class="block text-sm font-medium text-slate-700">
        Guru <span class="text-rose-600">*</span>
    </label>
    <select id="teacher_id" name="teacher_id" required
            class="mt-1.5 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100 @error('teacher_id') border-rose-300 ring-2 ring-rose-100 @enderror">
        <option value="">— Pilih Guru —</option>
        @foreach ($teachers as $teacher)
            <option value="{{ $teacher->id }}"
                @selected((int) old('teacher_id', $current->teacher_id ?? 0) === $teacher->id)>
                {{ $teacher->full_name }}@if($teacher->nip) — NIP {{ $teacher->nip }}@endif
            </option>
        @endforeach
    </select>
    @error('teacher_id')
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $error }}</p>
    @enderror
    <p class="mt-1.5 text-xs text-slate-400">
        Daftar guru diambil dari master data (tabel <span class="font-mono">teachers</span> dengan akun aktif).
    </p>
</div>

<div>
    <label for="schedule_date" class="block text-sm font-medium text-slate-700">
        Tanggal Piket <span class="text-rose-600">*</span>
    </label>
    <input type="date" id="schedule_date" name="schedule_date" required
           value="{{ old('schedule_date', $current?->schedule_date?->toDateString() ?? $today) }}"
           class="mt-1.5 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100 @error('schedule_date') border-rose-300 ring-2 ring-rose-100 @enderror">
    @error('schedule_date')
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $error }}</p>
    @enderror
    <p class="mt-1.5 text-xs text-slate-400">
        Tanggal berjalan sistem (server, Asia/Jakarta): <span class="font-mono font-semibold">{{ $today }}</span>.
        Tidak ada batasan hari — jadwal murni data, bukan aturan kode.
    </p>
</div>

<div>
    <label for="notes" class="block text-sm font-medium text-slate-700">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
    <textarea id="notes" name="notes" rows="3" maxlength="{{ \App\Services\TeacherDutyScheduleService::MAX_NOTES_LENGTH }}"
              class="mt-1.5 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-100 @error('notes') border-rose-300 ring-2 ring-rose-100 @enderror"
              placeholder="Contoh: piket gerbang pagi + jam pulang">{{ old('notes', $current->notes ?? '') }}</textarea>
    @error('notes')
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $error }}</p>
    @enderror
</div>
