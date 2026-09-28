<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penempelan Web Security Headers secara GLOBAL (PRD 04 §5.3, PRD 05 §22,
 * OWASP A05 Security Misconfiguration + OWASP Laravel Cheat Sheet).
 *
 * Didaftarkan pada tumpukan middleware global (bukan per-rute) sehingga
 * berlaku pada SELURUH response aplikasi — area admin, guru, supervisor,
 * halaman login, maupun response error — bukan hanya halaman scanner.
 *
 * Nilai header mengacu PRD 04 §5.3:
 * - X-Content-Type-Options: nosniff      -> tolak MIME sniffing (A05/A03).
 * - X-Frame-Options: SAMEORIGIN          -> anti-clickjacking; sekaligus
 *   mencegah scanner kamera HP dipanggil dari iframe pihak ketiga
 *   (PRD 04 §21 catatan kontrol berlapis kamera).
 * - Referrer-Policy: strict-origin-when-cross-origin -> batas bocor referrer.
 * - Permissions-Policy: camera=(self), microphone=(), geolocation=()
 *   -> ADDENDUM §25 / ADR 20 [FINAL — Keputusan B2]: camera=(self) WAJIB agar
 *      scanner barcode berbasis kamera HP Guru Piket dapat berjalan.
 *      DILARANG camera=() (memblokir scanner). microphone/geolocation nol.
 *      Kamera tetap dikontrol permission browser + otorisasi server-side
 *      Guru Piket (teacher_duty_schedules), bukan oleh header ini.
 * - Strict-Transport-Security -> hanya pada saluran HTTPS (produksi).
 *   Sengaja TIDAK dipaksakan pada http://localhost agar hasil probe lokal
 *   tidak menyesatkan sebagai "bukti HSTS" (PRD 04 §5.3: produksi ber-HTTPS).
 *
 * Catatan CSP: Content-Security-Policy TIDAK dipasang di sini. PRD 04 §5.3
 * hanya menyatakan CSP "dirancang agar kompatibel dengan aset build Vite dan
 * styling antarmuka" — belum ada nilai final. Menempelkan CSP ketat sekarang
 * akan merusak view yang memakai atribut style/inline dan font CDN. Dibiarkan
 * sebagai pekerjaan eksplisit (lihat laporan security), bukan diam-diam.
 */
class SetSecurityHeaders
{
    /**
     * Header yang selalu dikirim (netral terhadap skema protokol).
     */
    private const ALWAYS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=()',
    ];

    /**
     * HSTS: max-age 1 tahun + includeSubDomains (PRD 04 §5.3).
     */
    private const HSTS = 'max-age=31536000; includeSubDomains';

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        foreach (self::ALWAYS as $name => $value) {
            $response->headers->set($name, $value);
        }

        // HSTS hanya bermakna (dan hanya aman dikirim) di atas HTTPS.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', self::HSTS);
        }

        // Reduksi fingerprint stack.
        // `headers->remove()` saja TIDAK CUKUP: dengan php.ini `expose_php=On`
        // engine PHP menyisipkan X-Powered-By langsung ke daftar header SAPI,
        // jadi ia tidak pernah ada di kantong header Symfony (dibuktikan lewat
        // probe HTTP). header_remove() membersihkan versi SAPI-nya.
        // Penghapusan DEFINITIF tetap milik konfigurasi server
        // (`expose_php=Off` atau `fastcgi_hide_header X-Powered-By`) dan
        // dilaporkan sebagai temuan terbuka, bukan diklaim selesai di sini.
        $response->headers->remove('X-Powered-By');
        header_remove('X-Powered-By');

        return $response;
    }
}
