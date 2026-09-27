<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
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
}
