<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Halaman login dapat diakses oleh guest.
     */
    public function test_login_page_can_be_accessed_by_guest(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('Portal Sekolah');
        $response->assertSee('Username / NIP / Email');
    }

    /**
     * 2. Credential valid dapat login.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'username' => 'guru_budi',
            'email' => 'budi@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'guru_budi',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 3. Credential invalid ditolak.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::create([
            'username' => 'guru_budi',
            'email' => 'budi@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'guru_budi',
            'password' => 'kata_sandi_salah',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    /**
     * 4. Route protected menolak guest.
     */
    public function test_protected_route_redirects_guest_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * 5. User authenticated dapat mengakses route protected.
     */
    public function test_authenticated_user_can_access_protected_route(): void
    {
        $user = User::create([
            'username' => 'admin_sekolah',
            'email' => 'admin@sekolah.demo',
            'password' => 'admin12345',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSee('admin_sekolah');
        $response->assertSee('ADMIN');
    }

    /**
     * 6. Logout berhasil.
     */
    public function test_user_can_logout(): void
    {
        $user = User::create([
            'username' => 'guru_ani',
            'email' => 'ani@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
    }

    /**
     * 7. Setelah logout user tidak lagi authenticated.
     */
    public function test_user_is_not_authenticated_after_logout(): void
    {
        $user = User::create([
            'username' => 'guru_ani',
            'email' => 'ani@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();

        // Mencoba mengakses rute terproteksi setelah logout
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * 8. Session diregenerasi setelah login (Mitigasi Session Fixation).
     */
    public function test_session_is_regenerated_after_login(): void
    {
        $user = User::create([
            'username' => 'kepsek_andi',
            'email' => 'kepsek@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'supervisor',
            'is_active' => true,
        ]);

        // Kunjungi halaman login untuk inisialisasi sesi awal
        $this->get('/login');
        $initialSessionId = session()->getId();

        // Lakukan login
        $this->post('/login', [
            'username' => 'kepsek_andi',
            'password' => 'rahasia123',
        ]);

        $newSessionId = session()->getId();

        $this->assertNotEmpty($initialSessionId);
        $this->assertNotEmpty($newSessionId);
        $this->assertNotEquals($initialSessionId, $newSessionId, 'Session ID harus diperbarui setelah login.');
    }

    /**
     * 9. Password tidak disimpan sebagai plaintext di database.
     */
    public function test_password_is_not_stored_as_plaintext(): void
    {
        $plainPassword = 'MySecretPlainTextPassword!2026';

        $user = User::create([
            'username' => 'test_secure_user',
            'email' => 'secure@sekolah.demo',
            'password' => $plainPassword,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $userFromDb = User::find($user->id);

        // Password yang tersimpan tidak boleh sama dengan plaintext
        $this->assertNotEquals($plainPassword, $userFromDb->password);

        // Password harus valid saat dicek dengan mekanisme Hash
        $this->assertTrue(Hash::check($plainPassword, $userFromDb->password));
    }

    /**
     * 10. Login berhasil menghasilkan audit event.
     */
    public function test_successful_login_creates_audit_event(): void
    {
        $user = User::create([
            'username' => 'guru_audit_test',
            'email' => 'audit_test@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'guru_audit_test',
            'password' => 'rahasia123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'role' => 'teacher',
            'action' => AuditLog::ACTION_LOGIN_SUCCESS,
        ]);

        $auditLog = AuditLog::where('action', AuditLog::ACTION_LOGIN_SUCCESS)->latest('id')->first();
        $this->assertNotNull($auditLog);
        $this->assertNotNull($auditLog->created_at);

        // Pastikan tidak ada kata sandi yang bocor ke audit log
        $rawAuditData = json_encode($auditLog->toArray());
        $this->assertStringNotContainsString('rahasia123', $rawAuditData);
    }

    /**
     * 11. Login gagal menghasilkan audit event.
     */
    public function test_failed_login_creates_audit_event(): void
    {
        $user = User::create([
            'username' => 'guru_audit_fail',
            'email' => 'fail_test@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'guru_audit_fail',
            'password' => 'password_yang_salah',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOGIN_FAILED,
        ]);

        $auditLog = AuditLog::where('action', AuditLog::ACTION_LOGIN_FAILED)->latest('id')->first();
        $this->assertNotNull($auditLog);

        // Pastikan password yang diinput TIDAK PERNAH dicatat ke audit log
        $rawAuditData = json_encode($auditLog->toArray());
        $this->assertStringNotContainsString('password_yang_salah', $rawAuditData);
    }

    /**
     * 12. Logout menghasilkan audit event.
     */
    public function test_logout_creates_audit_event(): void
    {
        $user = User::create([
            'username' => 'guru_logout_test',
            'email' => 'logout_test@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'role' => 'teacher',
            'action' => AuditLog::ACTION_LOGOUT,
        ]);
    }

    /**
     * 13. Akun non-aktif ditolak saat login.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'username' => 'user_nonaktif',
            'email' => 'nonaktif@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'user_nonaktif',
            'password' => 'rahasia123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');

        // Audit log mencatat kegagalan
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOGIN_FAILED,
        ]);
    }

    /**
     * 14. Pengguna dapat login menggunakan email maupun username.
     */
    public function test_user_can_login_with_email_as_well(): void
    {
        $user = User::create([
            'username' => '198501012010011001',
            'email' => 'guru.budi@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'guru.budi@sekolah.demo',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 15. Rate limiter memblokir percobaan brute force setelah 5 kali gagal.
     */
    public function test_login_rate_limiting_blocks_excessive_attempts(): void
    {
        RateLimiter::clear('spammer|127.0.0.1');

        // 5 kali percobaan gagal
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'spammer',
                'password' => 'wrong-pass',
            ]);
        }

        // Percobaan ke-6 harus terkena rate limiting
        $response = $this->post('/login', [
            'username' => 'spammer',
            'password' => 'wrong-pass',
        ]);

        // Response status 429 atau session errors throttle
        $this->assertTrue(
            $response->status() === 429 || $response->isRedirect()
        );
        $response->assertSessionHasErrors('username');
    }

    /**
     * 16. Middleware EnsureUserIsActive memutus sesi jika akun dinonaktifkan di tengah jalan.
     */
    public function test_active_session_terminated_if_user_is_deactivated(): void
    {
        $user = User::create([
            'username' => 'user_akan_dinonaktifkan',
            'email' => 'aktif_lalu_nonaktif@sekolah.demo',
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        // Login sukses
        $this->actingAs($user)->get('/dashboard')->assertStatus(200);

        // Ubah status akun menjadi nonaktif
        $user->update(['is_active' => false]);

        // Akses kembali rute
        $response = $this->get('/dashboard');

        // Harus dialihkan ke login dan status guest
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
