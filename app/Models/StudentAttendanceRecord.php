<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'notes',
        'is_corrected',
        'corrected_by_user_id',
        'correction_reason',
        'corrected_at',
    ];

    protected $casts = [
        'is_corrected' => 'boolean',
        'corrected_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StudentAttendanceSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function correctedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
