<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Penegak otorisasi ROLE di sisi server (PRD 04 §3.2.1 & §3.2.2, PRD 02 §6).
 *
 * Dilarang mengandalkan UI/Blade sebagai kontrol keamanan
 * ("security through obscurity is prohibited" — PRD 04 §2).
 *
 * Perilaku:
 * - Deny by default: tanpa argumen role, akses DITOLAK.
 * - Tamu (belum login) -> AuthenticationException (redirect ke login / 401 JSON),
 *   bukan 403, sesuai perilaku autentikasi standar.
 * - Login tapi role tidak sesuai -> HTTP 403 Forbidden (BUKAN redirect ambigu),
 *   sesuai PRD 04 §3.2.2 dan PRD 02 §6 butir 3.
 * - Role diverifikasi dari kolom `users.role` pada sesi server-side,
 *   BUKAN dari input klien (header/field/cookie).
 *
 * Hanya 3 role resmi (PRD 01 §5): admin, teacher, supervisor.
 * TIDAK ADA role "piket" — status Guru Piket ditentukan oleh
 * teacher_duty_schedules pada tanggal berjalan (ADDENDUM §8),
 * dan bukan kewenangan middleware ini.
 */
class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  Role resmi yang diizinkan untuk rute ini.
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException(
                'Unauthenticated.', [], route('login')
            );
        }

        // Deny by default: `role:` tanpa argumen tidak pernah meloloskan akses.
        if ($roles === []) {
            $this->deny($request, $user, null, 'no_role_configured');
        }

        $allowed = array_map('strtolower', $roles);

        // Validasi konfigurasi: role di luar 3 role resmi tidak boleh dipakai.
        $official = User::OFFICIAL_ROLES;
        $unknown = array_diff($allowed, $official);

        if ($unknown !== []) {
            // Konfigurasi rute salah — tolak, jangan sampai meloloskan siapa pun.
            $this->deny($request, $user, $allowed, 'unknown_role_in_route_config');
        }

        if (! in_array(strtolower((string) $user->role), $allowed, true)) {
            $this->deny($request, $user, $allowed, 'role_mismatch');
        }

        return $next($request);
    }

    /**
     * Catat penolakan otorisasi ke log internal aplikasi (PRD 04 §10.2) lalu
     * lempar HTTP 403. Payload berisi data non-rahasia saja (tanpa cookie/token).
     */
    private function deny(Request $request, User $user, ?array $allowed, string $reason): void
    {
        Log::warning('Authorization denied', [
            'reason' => $reason,
            'user_id' => $user->id,
            'user_role' => $user->role,
            'required_roles' => $allowed,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        throw new AccessDeniedHttpException('Akses Ditolak: Anda tidak memiliki hak akses untuk area ini.');
    }
}
