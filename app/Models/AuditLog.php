<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public const ACTION_LOGIN_SUCCESS = 'LOGIN_SUCCESS';
    public const ACTION_LOGIN_FAILED = 'LOGIN_FAILED';
    public const ACTION_LOGOUT = 'LOGOUT';

    /**
     * Aksi CRUD Jadwal Guru Piket (ADDENDUM §17, PRD 01 §6.11 ADM-PIK-004,
     * PRD 04 §9.1 baris "Jadwal Piket Guru").
     *
     * Status "Guru Piket" bukan role — seluruh perubahannya wajib teraudit.
     */
    public const ACTION_DUTY_SCHEDULE_CREATE = 'DUTY_SCHEDULE_CREATE';

    public const ACTION_DUTY_SCHEDULE_UPDATE = 'DUTY_SCHEDULE_UPDATE';

    public const ACTION_DUTY_SCHEDULE_DELETE = 'DUTY_SCHEDULE_DELETE';

    /**
     * Aksi modul Absensi Masuk/Pulang SEKOLAH (PRD 04 §9.1 baris
     * "Absensi Masuk/Pulang Siswa", PRD 02 §4b butir 8, PRD 05 §22).
     *
     * SCHOOL_ATT_SCAN  : scan barcode siswa (MASUK atau PULANG) oleh Guru Piket.
     * SCHOOL_ATT_CORRECT: koreksi record oleh Admin/TU — WAJIB disertai alasan
     *                    (minimal 10 karakter) dan nilai lama/baru terimpan
     *                    lengkap agar histori scan tetap dapat ditelusuri
     *                    (PRD 01 §13.7).
     */
    public const ACTION_SCHOOL_ATT_SCAN = 'SCHOOL_ATT_SCAN';

    public const ACTION_SCHOOL_ATT_CORRECT = 'SCHOOL_ATT_CORRECT';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
