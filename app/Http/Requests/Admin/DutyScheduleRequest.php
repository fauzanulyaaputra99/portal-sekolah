<?php

namespace App\Http\Requests\Admin;

use App\Services\TeacherDutyScheduleService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input Jadwal Guru Piket (Admin/TU).
 *
 * PRD 02 §4c butir 2: `schedule_date` + `teacher_id` wajib ada.
 *
 * PENTING (PRD 04 §3.2.1): form ini HANYA memvalidasi bentuk data.
 * Kewenangan (siapa boleh menyimpan) TIDAK ditentukan di sini dan tidak
 * ditentukan oleh hidden field mana pun — ditegakkan server-side oleh
 * `TeacherDutySchedulePolicy` + Gate di controller. Actor `created_by_user_id`
 * diambil dari sesi login di service, bukan dari payload.
 *
 * Aturan `exists:teachers,id` hanya lapisan cepat; service tetap memvalidasi
 * secara otoritatif (profil guru terhubung akun & akun aktif).
 */
class DutyScheduleRequest extends FormRequest
{
    /**
     * Authorization dilakukan eksplisit di controller lewat Gate/policy
     * (satu jalur otorisasi untuk semua aksi mutasi — PRD 04 §3.2.3).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'teacher_id' => [
                'required',
                'integer',
                'exists:teachers,id',
            ],
            'schedule_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:' . TeacherDutyScheduleService::MAX_NOTES_LENGTH,
            ],
        ];
    }

    /**
     * Pesan berbahasa Indonesia agar jelas bagi petugas TU.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'teacher_id.required' => 'Guru wajib dipilih.',
            'teacher_id.integer' => 'Guru yang dipilih tidak valid.',
            'teacher_id.exists' => 'Guru yang dipilih tidak ditemukan pada master data Guru.',
            'schedule_date.required' => 'Tanggal piket wajib diisi.',
            'schedule_date.date_format' => 'Format tanggal tidak valid. Gunakan YYYY-MM-DD (contoh: 2026-09-28).',
            'notes.max' => 'Catatan melebihi :max karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'teacher_id' => is_numeric($this->input('teacher_id'))
                ? (int) $this->input('teacher_id')
                : $this->input('teacher_id'),
            'schedule_date' => is_string($this->input('schedule_date'))
                ? trim($this->input('schedule_date'))
                : $this->input('schedule_date'),
        ]);
    }
}
