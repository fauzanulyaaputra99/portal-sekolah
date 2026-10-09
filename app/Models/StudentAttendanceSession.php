<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudentAttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'teaching_assignment_id',
        'session_date',
        'meeting_order',
        'status',
        'opened_at',
        'closed_at',
        'opened_by_teacher_id',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Boot: auto-generate UUID saat membuat record baru.
     * UUID digunakan sebagai route key publik (anti-IDOR, PRD 04 §3.2.3).
     * PK tetap BIGINT auto-increment untuk performa FK (ADR 15).
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * Route model binding menggunakan UUID, bukan auto-increment ID.
     * URL: /guru/attendance/{uuid}/edit (bukan /guru/attendance/1/edit).
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function openedByTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'opened_by_teacher_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(StudentAttendanceRecord::class, 'session_id');
    }
}
