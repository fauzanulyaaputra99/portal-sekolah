<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
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
