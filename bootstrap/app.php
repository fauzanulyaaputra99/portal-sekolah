<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias `role` untuk penegakan otorisasi server-side per grup rute
        // (PRD 02 §6 butir 3, PRD 04 §3.2.1). Melanggar prefix role => 403.
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,

            // PRD 04 §3.2.4 / ADDENDUM §11–§14: gerbang scanner berbasis DATA
            // jadwal piket pada tanggal server, direvalidasi pada SETIAP request
            // (GET halaman maupun POST scan). Bukan role baru dan bukan flag user.
            'on.duty' => \App\Http\Middleware\EnsureTeacherOnDuty::class,
        ]);

        // PRD 04 §3.1.4: verifikasi akun aktif bersifat GLOBAL untuk seluruh
        // rute web — sesi diputasi seketika bila users.is_active = FALSE.
        //
        // Registrasi ganda yang ditemukan saat audit (dulu: global web append
        // DAN alias 'active' dipakai ulang pada grup route terproteksi) sudah
        // dibersihkan: rute tidak lagi menyematkan 'active' secara manual,
        // sehingga middleware dieksekusi tepat satu kali per request.
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        // PRD 04 §5.3 / OWASP A05: security headers GLOBAL (bukan per-rute),
        // sehingga berlaku pada seluruh response aplikasi — area admin/guru/
        // supervisor, halaman login, maupun response error.
        // Diprepend agar header juga tertempel saat response dibungkus lebih
        // awal, dan tetap terisi bila middleware dalam men-set header yang sama.
        $middleware->prepend([
            \App\Http\Middleware\SetSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
