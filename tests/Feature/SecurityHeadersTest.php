<?php

namespace Tests\Feature;

use App\Http\Middleware\SetSecurityHeaders;
use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OWASP A05 (Security Misconfiguration) — verifikasi Web Security Headers
 * sesuai PRD 04 §5.3 & PRD 05 §22, termasuk Permissions-Policy camera=(self)
 * hasil ADDENDUM §25 / ADR 20 [FINAL — Keputusan B2].
 *
 * Prinsip pengujian:
 * - Header diuji pada response yang BENAR-BENAR dibuat aplikasi (HTTP local),
 *   bukan pada konfigurasi web server yang tidak bisa dibuktikan dari sini.
 * - Diuji pada beberapa jenis response (publik, terautentikasi, 403, 404)
 *   untuk membuktikan penempelan bersifat GLOBAL, bukan per halaman scanner.
 * - HSTS sengaja TIDAK diklaim lulus pada HTTP localhost; diuji sebagai
 *   "tidak dipaksakan di HTTP" + "ada saat request HTTPS" (proxy TLS milik
 *   web server/reverse proxy di produksi — PRD 04 §5.2).
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=()',
    ];

    private function makeAdmin(string $username = 'admin_headers'): User
    {
        return User::create([
            'username' => $username,
            'email' => "{$username}@sekolah.demo",
            'password' => 'rahasia123',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, string>  $names
     */
    private function assertHeadersPresent(\Illuminate\Testing\TestResponse $response, string $on): void
    {
        foreach (self::EXPECTED as $name => $value) {
            $this->assertSame(
                $value,
                $response->headers->get($name),
                "Header {$name} wajib '{$value}' pada {$on}. "
                    . 'Didapat: ' . var_export($response->headers->get($name), true)
            );
        }
    }

    public function test_security_headers_middleware_is_registered_globally(): void
    {
        // Sumber kebenaran tumpukan GLOBAL adalah HTTP Kernel, bukan Router.
        $global = app(\Illuminate\Contracts\Http\Kernel::class)->getGlobalMiddleware();

        $this->assertContains(
            SetSecurityHeaders::class,
            $global,
            'SetSecurityHeaders wajib berada pada tumpukan middleware GLOBAL (PRD 04 §5.3).'
        );

        // Menjadi middleware global = tidak terikat satu grup rute/page tertentu,
        // sehingga header berlaku juga untuk response error (diuji terpisah).
        $this->assertSame(
            SetSecurityHeaders::class,
            $global[array_key_first($global)],
            'SetSecurityHeaders sebaiknya berada paling awal agar header tidak tertimpa.'
        );
    }

    public function test_guest_login_page_receives_all_security_headers(): void
    {
        $response = $this->get('/login')->assertOk();

        $this->assertHeadersPresent($response, 'response publik (guest, HTTP 200)');
    }

    public function test_authenticated_admin_area_receives_security_headers(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertHeadersPresent($response, 'response terautentikasi area admin');
    }

    public function test_duty_schedule_index_receives_security_headers_for_admin_and_supervisor(): void
    {
        $admin = $this->makeAdmin();

        $teacher = Teacher::create([
            'user_id' => User::create([
                'username' => 'guru_headers',
                'email' => 'guru_headers@sekolah.demo',
                'password' => 'rahasia123',
                'role' => User::ROLE_TEACHER,
                'is_active' => true,
            ])->id,
            'nip' => '198501012010019999',
            'full_name' => 'Guru Header',
            'gender' => 'L',
        ]);

        TeacherDutySchedule::create([
            'schedule_date' => '2026-09-28',
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $admin->id,
            'notes' => 'piket pagi',
        ]);

        $supervisor = User::create([
            'username' => 'supervisor_headers',
            'email' => 'supervisor_headers@sekolah.demo',
            'password' => 'rahasia123',
            'role' => User::ROLE_SUPERVISOR,
            'is_active' => true,
        ]);

        foreach ([$admin, $supervisor] as $viewer) {
            $this->assertHeadersPresent(
                $this->actingAs($viewer)->get(route('admin.duty-schedules.index'))->assertOk(),
                'halaman jadwal piket (' . $viewer->role . ')'
            );
        }
    }

    public function test_forbidden_response_still_carries_security_headers(): void
    {
        // 403 dilempar middleware role => jalur exception. Header wajib tetap ada.
        $teacher = $this->makeAdmin('guru_forbidden_headers');
        $teacher->update(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($teacher->fresh())->get(route('admin.dashboard'));

        $response->assertForbidden();
        $this->assertHeadersPresent($response, 'response HTTP 403 (penolakan otorisasi)');
    }

    public function test_not_found_response_still_carries_security_headers(): void
    {
        $response = $this->get('/rute-yang-tidak-ada-xyz');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertHeadersPresent($response, 'response HTTP 404');
    }

    public function test_camera_is_allowed_but_microphone_and_geolocation_are_denied(): void
    {
        // ADR 20 [FINAL — Keputusan B2]: camera=(self) WAJIB ada agar scanner
        // kamera HP dapat berjalan; camera=() DILARANG; microphone/geolocation nol.
        $policy = $this->get('/login')->assertOk()->headers->get('Permissions-Policy');

        $this->assertIsString($policy);
        $this->assertStringContainsString('camera=(self)', $policy, 'ADR 20: kamera origin sendiri diizinkan.');
        $this->assertStringNotContainsString('camera=()', $policy, 'camera=() memblokir scanner — dilarang PRD 04 §21.');
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }

    public function test_hsts_is_not_forced_on_http_but_sent_on_https(): void
    {
        // Bukti development lokal: HTTP TIDAK boleh ikut mengirim HSTS.
        $http = $this->get('/login')->assertOk();
        $this->assertNull(
            $http->headers->get('Strict-Transport-Security'),
            'HSTS tidak boleh dipaksakan pada http://localhost (bukti palsu di development).'
        );

        // Produksi = HTTPS (PRD 04 §5.2). Ditiru dengan URI absolut sehingga
        // Request::secure() bernilai true (server vars mentah diabaikan Symfony
        // saat trusted-headers belum dikonfigurasi).
        $https = $this->call('GET', 'https://sekolah.example/login');

        $this->assertTrue(
            request()->secure(),
            'Prasyarat test: request harus dianggap secure agar klaim HSTS valid.'
        );
        $https->assertOk();
        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $https->headers->get('Strict-Transport-Security'),
            'HSTS wajib aktif pada saluran HTTPS sesuai PRD 04 §5.3.'
        );
    }

    public function test_x_powered_by_is_removed_from_application_response(): void
    {
        // (a) Kantong header Symfony: tidak boleh membawa X-Powered-By,
        //     diverifikasi pada beberapa jenis response.
        foreach ([$this->get('/login'), $this->get('/rute-tidak-ada-xyz')] as $response) {
            $this->assertNull(
                $response->headers->get('X-Powered-By'),
                'Response HTTP ' . $response->getStatusCode() . ' tidak boleh membawa X-Powered-By di header bag.'
            );
        }

        // (b) GUARD ANTI-REGRESI. Assertion (a) SAJA menyesatkan: dengan
        //     php.ini `expose_php=On`, X-Powered-By disisipkan engine PHP di luar
        //     header bag, jadi (a) hijau walau header itu masih terkirim di wire.
        //     Pembuktian wire-level dilakukan lewat probe HTTP eksternal
        //     (hasil: header hilang). Middleware WAJIB tetap memanggil
        //     header_remove() level engine agar perbaikan tidak hilang diam-diam.
        $source = file_get_contents(base_path('app/Http/Middleware/SetSecurityHeaders.php'));

        $this->assertMatchesRegularExpression(
            '/header_remove\s*\(\s*[\'"]X-Powered-By[\'"]\s*\)/',
            $source,
            'SetSecurityHeaders wajib memanggil header_remove(\'X-Powered-By\'), bukan hanya headers->remove().'
        );

        // (c) CATATAN TEMUAN (sengaja TIDAK diassert): pemblokiran definitif
        //     X-Powered-By adalah konfigurasi web server/PHP (`expose_php=Off`
        //     atau `fastcgi_hide_header X-Powered-By`). Menyetel itu justru membuat
        //     nilai runtime berubah, jadi tidak dijadikan ekspektasi test.
        //     Status: TERBUKA di sisi server (dipisahkan dari scope aplikasi).
    }

    /**
     * OWASP A02/A05 - halaman error TIDAK boleh membocorkan detail sensitif
     * pada mode produksi (APP_DEBUG=false). config('app.debug') di-toggle di
     * sini agar bukti tidak bergantung pada .env development (debug=true).
     */
    public function test_production_error_pages_do_not_leak_sensitive_details(): void
    {
        config(['app.debug' => false]);

        $teacher = User::create([
            'username' => 'guru_leak',
            'email' => 'guru_leak@sekolah.demo',
            'password' => 'rahasia123',
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);

        $cases = [
            '404' => $this->get('/rute-tidak-ada-xyz'),
            '403' => $this->actingAs($teacher)->get(route('admin.dashboard')),
            'guest' => $this->get(route('admin.dashboard')),
        ];

        // PATTERN dibuat dari bagian yang dirakit agar tidak perlu escaping sulit.
        $forbidden = [
            'stack trace frame' => '/#0\\s+.{0,200}\\.php\\(\\d+\\)/',
            'vendor path' => '/vendor.(laravel|symfony|composer)/i',
            'workspace path' => '/D:.anti/i',
            'APP_KEY' => '/APP_KEY/',
            'db credential' => '/DB_PASSWORD/',
            'encrypted cookie payload' => '/eyJpdiI6/',
            'php version' => '/PHP Version|PHP\\/8\\./i',
        ];

        foreach ($cases as $label => $response) {
            $body = $response->getContent();
            $this->assertIsString($body);

            foreach ($forbidden as $name => $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $body,
                    "Response {$label} (HTTP {$response->getStatusCode()}) membocorkan {$name} pada mode produksi."
                );
            }
        }

        // Header keamanan tetap menempel pada response error mode produksi.
        $this->assertHeadersPresent($cases['404'], 'response 404 mode produksi');
    }

    public function test_session_cookie_flags_match_configuration(): void
    {
        // Flag cookie DIVERIFIKASI DARI Set-Cookie yang benar-benar dikeluarkan,
        // bukan dari config (session.driver sengaja di-override 'array' oleh
        // phpunit.xml, jadi membaca config di sini tidak membuktikan apa-apa).
        $cookies = collect($this->get('/login')->headers->getCookies());

        $session = $cookies->first(fn ($c) => $c->getName() === config('session.cookie'));
        $this->assertNotNull($session, 'Cookie sesi wajib dikirim.');

        // PRD 04 §5.1: HttpOnly TRUE -> cookie sesi tidak terbaca JavaScript.
        $this->assertTrue((bool) $session->isHttpOnly(), 'HttpOnly wajib TRUE pada cookie sesi.');

        // PRD 04 §5.1: SameSite Lax.
        $this->assertSame('lax', strtolower((string) $session->getSameSite()), 'SameSite wajib Lax.');

        // Path dibatasi ke aplikasi.
        $this->assertSame((string) config('session.path'), $session->getPath());

        // secure=false di sini BENAR karena request HTTP lokal; pada HTTPS
        // konfigurasi 'secure' => null membuat cookie otomatis Secure.
        $this->assertNull(config('session.secure'), "session.secure harus auto (null) agar produksi HTTPS otomatis Secure.");

        // XSRF-TOKEN memang TIDAK HttpOnly oleh desain Laravel (dibaca JS untuk
        // header X-XSRF-TOKEN). Bukan temuan, tapi wajib dinyatakan di laporan.
        $xsrf = $cookies->first(fn ($c) => $c->getName() === 'XSRF-TOKEN');
        $this->assertNotNull($xsrf, 'Cookie XSRF-TOKEN wajib ada untuk CSRF.');
        $this->assertSame('lax', strtolower((string) $xsrf->getSameSite()));

        // Nama cookie berbasis APP_NAME/SESSION_COOKIE, bukan fingerprint bawaan.
        $this->assertNotEmpty(config('session.cookie'));
        $this->assertSame('laravel-session', config('session.cookie'));
    }
}
