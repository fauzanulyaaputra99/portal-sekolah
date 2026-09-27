<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login pengguna.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username, NIP, atau Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $inputIdentifier = trim($credentials['username']);
        $throttleKey = Str::transliterate(Str::lower($inputIdentifier) . '|' . $request->ip());

        // 1. Rate Limiting: Maksimal 5 percobaan gagal per 60 detik (ADR / PRD)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditService::logLoginFailed($inputIdentifier, $request, null, 'rate_limited');

            throw ValidationException::withMessages([
                'username' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ])->status(429);
        }

        // 2. Pencarian akun berdasarkan username atau email
        $user = User::where('username', $inputIdentifier)
            ->orWhere('email', $inputIdentifier)
            ->first();

        // 3. Verifikasi kata sandi dengan hashing Laravel (Argon2id/Bcrypt)
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            AuditService::logLoginFailed($inputIdentifier, $request, $user, 'invalid_credentials');

            // Pesan generik agar tidak membocorkan keberadaan username/email
            throw ValidationException::withMessages([
                'username' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
        }

        // 4. Verifikasi status keaktifan akun
        if (! $user->is_active) {
            RateLimiter::hit($throttleKey, 60);
            AuditService::logLoginFailed($inputIdentifier, $request, $user, 'inactive_account');

            throw ValidationException::withMessages([
                'username' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
        }

        // 5. Login berhasil: reset rate limiter, perbarui metadata, regenerasi session
        RateLimiter::clear($throttleKey);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        Auth::login($user, $request->boolean('remember'));

        // Regenerasi session ID untuk mitigasi Session Fixation (OWASP / PRD)
        $request->session()->regenerate();

        // Audit log login sukses
        AuditService::logLoginSuccess($user, $request);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Proses logout pengguna secara aman.
     */
    public function logout(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            AuditService::logLogout($user, $request);
        }

        Auth::logout();

        // Invalidate session & regenerasi CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar dari sistem.');
    }
}
