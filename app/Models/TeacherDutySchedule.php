<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDutySchedule extends Model
{
    use HasFactory;

    /**
     * Jadwal piket guru (PRD 02 §10.15b).
     *
     * SATU-SATUNYA sumber kebenaran status "Guru Piket". Dinilai ulang pada
     * SETIAP request scanner terhadap tanggal server (Asia/Jakarta) —
     * perubahan jadwal langsung memengaruhi otorisasi tanpa perubahan kode,
     * tanpa WebSocket/Redis/Queue (PRD 02 §21).
     *
     * @var string
     */
    protected $table = 'teacher_duty_schedules';

    /**
     * created_by_user_id diisi dari sesi login (actor server-side),
     * bukan dari input klien.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'schedule_date',
        'teacher_id',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /** Admin/TU yang membuat jadwal (akuntabilitas, ADDENDUM §17). */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
