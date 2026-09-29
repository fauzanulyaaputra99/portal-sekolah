<?php

namespace App\Http\Requests\Teacher;

use App\Services\SchoolAttendanceScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi bentuk input scanner (PRD 04 §8, §16.4; PRD 02 §4b butir 2–3).
 *
 * PENTING — whitelist, bukan blacklist.
 * Hanya DUA field yang diambil dari klien: `barcode` dan `mode`. Seluruh nilai
 * lain yang dikirim klien (`teacher_id`, `operator_id`, `check_in_by_teacher_id`,
 * `attendance_date`, `tanggal`, `waktu`, `timestamp`, `role`, `is_active`) TIDAK
 * PERNAH dibaca oleh aplikasi ini: service memakai `$request->input('barcode')`
 * dan `$request->input('mode')` saja (PRD 04 §3.2.4 "nilai dari klien diabaikan").
 * Jadi memalsukan field-field itu tidak memiliki efek apa pun — bukan sekadar
 * "divalidasi lalu ditolak", melainkan diabaikan sejak desain.
 *
 * Otoritas (siapa boleh scan, tanggal apa, jam berapa) TIDAK ditentukan di sini:
 * ditegakkan server-side oleh EnsureTeacherOnDuty + SchoolAttendanceScanService.
 */
class SchoolAttendanceScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi scanner adalah urusan jadwal piket server-side dan sudah
        // ditegakkan middleware SEBELUM request ini; diulang sekali lagi di
        // dalam service (defense in depth). Form request ini tidak mengulang
        // keputusan otorisasi.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'barcode' => [
                'required',
                'string',
                'max:' . SchoolAttendanceScanService::MAX_BARCODE_LENGTH,
            ],
            'mode' => [
                'required',
                'string',
                // Enum EKSPISIT (ADR 18). Mode tidak boleh disimpulkan server
                // dari kondisi data (ADDENDUM §1 — auto-detect dilarang).
                Rule::in(SchoolAttendanceScanService::MODES),
            ],
        ];
    }

    /**
     * Aturan validasi TIDAK dilonggarkan sedikit pun: parent tetap melempar
     * ValidationException sehingga responsnya tetap redirect-back + $errors.
     *
     * Yang ditambahkan hanya JEJAK AUDIT atas peristiwa scan yang nyata terjadi
     * (PRD 04 §9.1: event = "Guru Piket melakukan scan barcode siswa"). Form
     * request ini jalan SEBELUM service, jadi tanpa hook ini penolakan bentuk
     * input (mode invalid dsb.) tidak akan pernah tercatat sama sekali.
     *
     * auditRejectedAttempt() menilai ULANG status piket dari database dan
     * sengaja tidak menulis apa pun bila guru tidak bertugas, sehingga jalur ini
     * tidak bisa dipakai untuk menandai audit oleh non-Guru-Piket.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $messages = $validator->errors()->messages();

        $outcome = match (true) {
            isset($messages['mode']) => SchoolAttendanceScanService::OUTCOME_MODE_INVALID,
            isset($messages['barcode']) && $this->barcodeIsBlank() => SchoolAttendanceScanService::OUTCOME_BARCODE_EMPTY,
            isset($messages['barcode']) => SchoolAttendanceScanService::OUTCOME_BARCODE_TOO_LONG,
            default => null,
        };

        if ($outcome !== null) {
            app(SchoolAttendanceScanService::class)->auditRejectedAttempt(
                $this,
                $this->user(),
                $outcome,
                // Hanya jumlah error + panjang input; TIDAK ada nilai klien.
                [
                    'error_fields' => array_keys($messages),
                    'barcode_length' => is_string($this->input('barcode'))
                        ? mb_strlen(trim((string) $this->input('barcode')))
                        : 0,
                ],
            );
        }

        parent::failedValidation($validator);
    }

    private function barcodeIsBlank(): bool
    {
        return ! is_string($this->input('barcode')) || trim((string) $this->input('barcode')) === '';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'barcode.required' => 'Barcode wajib dipindai atau diisi.',
            'barcode.max' => 'Barcode tidak valid.',
            'mode.required' => 'Mode scan wajib dipilih (MASUK atau PULANG).',
            'mode.in' => 'Mode scan tidak valid. Pilih MASUK atau PULANG.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $mode = $this->input('mode');

        $this->merge([
            'barcode' => is_string($this->input('barcode')) ? trim($this->input('barcode')) : $this->input('barcode'),
            // Normalisasi kapitalisasi saja; nilai tetap divalidasi terhadap
            // enum di rules(). Server TIDAK pernah memilih mode untuk pengguna.
            'mode' => is_string($mode) ? strtoupper(trim($mode)) : $mode,
        ]);
    }

    /**
     * Hanya input yang sah secara bisnis yang diteruskan (PRD 04 §8).
     *
     * @return array{barcode: string, mode: string}
     */
    public function scanInput(): array
    {
        $validated = $this->validated();

        return [
            'barcode' => (string) ($validated['barcode'] ?? ''),
            'mode' => (string) ($validated['mode'] ?? ''),
        ];
    }
}
