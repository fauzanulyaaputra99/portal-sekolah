<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Catat event login berhasil ke audit log.
     */
    public static function logLoginSuccess(User $user, Request $request): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'action' => AuditLog::ACTION_LOGIN_SUCCESS,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => [
                'username' => $user->username,
                'email' => $user->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Catat event login gagal ke audit log.
     * Kata sandi / credential mentah TIDAK PERNAH dicatat demi keamanan (OWASP / PRD).
     */
    public static function logLoginFailed(?string $attemptedIdentifier, Request $request, ?User $user = null, string $reason = 'invalid_credentials'): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'action' => AuditLog::ACTION_LOGIN_FAILED,
            'auditable_type' => $user ? User::class : null,
            'auditable_id' => $user?->id,
            'old_values' => null,
            'new_values' => [
                'attempted_username' => $attemptedIdentifier ? substr($attemptedIdentifier, 0, 50) : null,
                'reason' => $reason,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Catat event logout ke audit log.
     */
    public static function logLogout(User $user, Request $request): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'action' => AuditLog::ACTION_LOGOUT,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => [
                'username' => $user->username,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Catat perubahan DATA master/operasional ke audit log (PRD 04 §9.1,
     * PRD 01 §6.10 ADM-LOG-001 — "perubahan jadwal piket" wajib dicatat).
     *
     * Struktur dibuat identik dengan audit login agar satu format untuk
     * seluruh auditor. Hanya nilai non-sensitif yang boleh dikirim
     * (PRD 04 §10.2): id, nama, tanggal, dan kode aksi.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  int|null  $auditableId  Id eksplisit, dipakai untuk aksi DELETE
     *                                 saat model target sudah tidak tersedia.
     */
    public static function logDataChange(
        User $actor,
        string $action,
        Model|string $target,
        ?array $oldValues,
        ?array $newValues,
        Request $request,
        ?int $auditableId = null,
    ): AuditLog {
        $targetType = $target instanceof Model ? $target::class : $target;
        $targetId = $auditableId ?? ($target instanceof Model ? $target->getKey() : null);

        return AuditLog::create([
            'user_id' => $actor->id,
            'role' => $actor->role,
            'action' => $action,
            'auditable_type' => $targetType,
            'auditable_id' => $targetId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
