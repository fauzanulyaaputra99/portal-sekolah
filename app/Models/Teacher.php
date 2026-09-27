<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
        'full_name',
        'gender',
        'phone_number',
        'employment_status',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_id');
    }

    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'homeroom_teacher_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'teacher_id');
    }

    /**
     * Jadwal piket guru ini (PRD 02 §10.15b).
     * Status "Guru Piket" ditentukan dari relasi ini pada tanggal berjalan,
     * BUKAN dari role atau flag users.is_piket (ADDENDUM §8 — dilarang).
     */
    public function dutySchedules(): HasMany
    {
        return $this->hasMany(TeacherDutySchedule::class, 'teacher_id');
    }

    /**
     * Absensi masuk sekolah di mana guru ini bertindak sebagai operator
     * scanner saat bertugas piket (PRD 02 §10.15).
     */
    public function schoolCheckIns(): HasMany
    {
        return $this->hasMany(SchoolAttendance::class, 'check_in_by_teacher_id');
    }

    /**
     * Absensi pulang sekolah dengan guru ini sebagai operator scanner.
     */
    public function schoolCheckOuts(): HasMany
    {
        return $this->hasMany(SchoolAttendance::class, 'check_out_by_teacher_id');
    }
}
