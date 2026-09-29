<?php

use App\Support\ErrorMask;
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

        // PRD 04 §10.1 — ERROR MASKING (lapis kedua).
        //
        // Lapis pertama = resources/views/errors/{4xx,5xx}.blade.php milik
        // aplikasi. View itu MENANG atas view vendor (path aplikasi didaftarkan
        // lebih dulu oleh RegisterErrorViewPaths) dan dipakai untuk status HTTP
        // >= 400 BAHKAN ketika APP_DEBUG=true, karena Handler::renderHttpException()
        // dipanggil sebelum renderer debug Symfony.
        //
        // Tanpa view tersebut, status yang tidak punya view bawaan — contohnya
        // 405 Method Not Allowed — jatuh ke Symfony HtmlErrorRenderer dan pada
        // APP_DEBUG=true mengirim halaman debug ±867 kB berisi 358 path file
        // framework + FQCN exception (hasil probe nyata pada server development).
        //
        // Callback ini menyaring APA PUN response error >= 400 yang masih
        // tersisa saat mode produksi (debug=false): halaman debug framework
        // diganti halaman generik, dan key exception/file/line/trace pada payload
        // JSON debug dibuang. Response < 400 dan mode debug tidak disentuh sama
        // sekali, jadi alur development pengembang tidak berubah.
        //
        // config('app.debug') dibaca SAAT request berjalan (bukan saat bootstrap)
        // supaya test dapat memproduksi kedua mode tanpa mengubah .env
        // development milik pengguna.
        $exceptions->respond(fn ($response) => ErrorMask::apply($response));
    })->create();
