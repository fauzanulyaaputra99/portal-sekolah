<?php

namespace App\Http\Requests\Admin;

use App\Services\AttendanceCorrectionService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi bentuk input koreksi absensi masuk/pulang siswa (Admin/TU).
 *
 * Acuan: PRD 01 §6.9 ADM-ABS-003 (alasan minimal 10 karakter), PRD 02 §5
 * butir 2, PRD 04 §8 (input tidak terpercaya).
 *
 * Yang TIDAK diterima/dipakai dari klien:
 * - `attendance_date` — tanggal record tidak boleh dipindah lewat koreksi
 *   (satu baris = satu siswa satu hari; lihat AttendanceCorrectionService);
 * - `corrected_by_user_id` / `is_corrected` / `corrected_at` — actor dan
 *   stempel koreksi diambil dari sesi login + clock server;
 * - `check_in_by_teacher_id` / `scan_mode_*` — jejak operator scan asli tidak
 *   boleh ditimpa dari payload.
 * Field-field itu tidak ada dalam rules(), jadi `validated()` tidak akan pernah
 * mengembalikannya dan service tidak pernah membacanya.
 */
class SchoolAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi ditegakkan di controller lewat Gate/Policy
        // (SchoolAttendancePolicy::correct) — satu jalur untuk aksi mutasi.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Nilai jam dikirim eksplisit oleh Admin/TU; "" berarti dikosongkan.
            'check_in_time' => [
                'nullable',
                'string',
                'max:19',
            ],
            'check_out_time' => [
                'nullable',
                'string',
                'max:19',
            ],
            'reason' => [
                'required',
                'string',
                'min:' . AttendanceCorrectionService::MIN_REASON_LENGTH,
                'max:' . AttendanceCorrectionService::MAX_REASON_LENGTH,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.min' => 'Alasan koreksi wajib diisi minimal :min karakter.',
            'reason.max' => 'Alasan koreksi melebihi :max karakter.',
            'check_in_time.max' => 'Format waktu masuk tidak valid.',
            'check_out_time.max' => 'Format waktu pulang tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalise = function (mixed $value): ?string {
            if (! is_string($value)) {
                return null;
            }

            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            // Input datetime-local bernilai "YYYY-MM-DDTHH:MM" dinormalisasi
            // ke "YYYY-MM-DD HH:MM" sebelum diperiksa service (masih di-parse
            // pada timezone aplikasi, bukan timezone klien).
            return str_replace('T', ' ', $trimmed);
        };

        $merged = [
            'reason' => is_string($this->input('reason')) ? trim($this->input('reason')) : $this->input('reason'),
        ];

        // Hanya diset bila kunci benar-benar ada pada payload, supaya service
        // dapat membedakan "tidak diubah" vs "dikosongkan".
        if ($this->has('check_in_time')) {
            $merged['check_in_time'] = $normalise($this->input('check_in_time'));
        }

        if ($this->has('check_out_time')) {
            $merged['check_out_time'] = $normalise($this->input('check_out_time'));
        }

        $this->merge($merged);
    }
}
