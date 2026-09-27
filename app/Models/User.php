<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Role RESMI sistem (PRD 01 §5 — tepat 3 peran).
     *
     * ADDENDUM §8: "piket" BUKAN role. Status Guru Piket ditentukan dari
     * tabel teacher_duty_schedules pada tanggal berjalan, bukan dari kolom
     * user mana pun (users.is_piket dilarang).
     */
    public const ROLE_ADMIN = 'admin';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_SUPERVISOR = 'supervisor';

    /** @var array<int, string> */
    public const OFFICIAL_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_TEACHER,
        self::ROLE_SUPERVISOR,
    ];

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * Helper otorisasi server-side. Perbandingan dilakukan case-insensitive
     * dan memakai nilai kolom role dari database (bukan input klien).
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array(
            strtolower((string) $this->role),
            array_map('strtolower', $roles),
            true
        );
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(self::ROLE_TEACHER);
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole(self::ROLE_SUPERVISOR);
    }

    /**
     * Profil guru milik akun ini (null bila bukan guru / profil belum dibuat).
     * Dasar seluruh pemeriksaan kepemilikan resource (anti-IDOR).
     */
    public function teacherId(): ?int
    {
        return $this->teacher()->exists()
            ? $this->teacher()->value('id')
            : null;
    }

    /** Role resmi yang dikenal sistem; dipakai validasi level aplikasi. */
    public static function isValidRole(?string $role): bool
    {
        return in_array(strtolower((string) $role), self::OFFICIAL_ROLES, true);
    }
}
