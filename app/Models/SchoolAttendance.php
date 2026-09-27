<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolAttendance extends Model
{
    use HasFactory;

    /**
     * Absensi Masuk/Pulang SEKOLAH (PRD 02 §10.15).
     * Satu baris = satu siswa untuk satu tanggal (UNIQUE student_id+attendance_date).
     *
     * @var string
     */
    protected $table = 'student_school_attendances';

    /**
     * Mass assignment protection (PRD 02 §20.4): hanya kolom yang sah secara
     * bisnis. Kolom operator/mode/koreksi hanya boleh diisi oleh jalur service
     * resmi (bukan input mentah klien).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'check_in_by_teacher_id',
        'check_out_by_teacher_id',
        'scan_mode_in',
        'scan_mode_out',
        'is_corrected',
        'corrected_by_user_id',
        'correction_reason',
        'corrected_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'corrected_at' => 'datetime',
        'is_corrected' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /** Guru Piket operator scan MASUK. */
    public function checkInByTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'check_in_by_teacher_id');
    }

    /** Guru Piket operator scan PULANG. */
    public function checkOutByTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'check_out_by_teacher_id');
    }

    /** Admin/TU pelaksana koreksi manual (jalur resmi Keputusan A1). */
    public function correctedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
