<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Document;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\StudentAttendanceSession;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Prompt 25 — Authorization & RBAC.
 *
 * Menegakkan (PRD 01 §5 & §5.4, PRD 02 §6, PRD 04 §3.2 & §16.2):
 *  - pemisahan area /admin, /guru, /supervisor secara server-side;
 *  - penolakan role yang tidak berhak = HTTP 403 (bukan redirect ambigu);
 *  - akses URL langsung tetap terlindungi (bukan sekadar sembunyikan menu);
 *  - Proteksi IDOR pada level resource (Policy);
 *  - tepat 3 role resmi, TANPA role/flag "piket";
 *  - EnsureUserIsActive (Prompt 24) tetap bekerja pada rute role-scoped.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function makeUser(string $role, string $username): User
    {
        return User::create([
            'username' => $username,
            'email' => "{$username}@sekolah.demo",
            'password' => 'rahasia123',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeTeacherUser(string $username): array
    {
        $user = $this->makeUser(User::ROLE_TEACHER, $username);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nip' => strtoupper(substr(md5($username), 0, 18)),
            'full_name' => 'Guru ' . $username,
            'gender' => 'L',
        ]);

        return [$user->fresh(), $teacher];
    }

    /** @return array{0: AcademicYear, 1: ClassRoom, 2: Subject} */
    private function makeAcademicContext(): array
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'GANJIL',
            'is_active' => true,
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
        ]);

        $grade = GradeLevel::create(['name' => 'Tingkat 7', 'level_order' => 7]);

        $class = ClassRoom::create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
        ]);

        $subject = Subject::create(['code' => 'MTK-07', 'name' => 'Matematika', 'category' => 'UMUM']);

        return [$year, $class, $subject];
    }

    // =================================================================
    // A. ROLE MIDDLEWARE — area admin
    // =================================================================

    public function test_admin_can_access_admin_area(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_rbac');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSee('Admin / Tata Usaha');
    }

    public function test_teacher_is_forbidden_from_admin_area(): void
    {
        [$teacher] = $this->makeTeacherUser('guru_rbac_a');

        // PRD 04 §3.2.2: 403 Forbidden, BUKAN redirect.
        $this->actingAs($teacher)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_supervisor_is_forbidden_from_admin_area(): void
    {
        $sup = $this->makeUser(User::ROLE_SUPERVISOR, 'kepsek_rbac');

        $this->actingAs($sup)->get('/admin/dashboard')->assertForbidden();
    }

    // =================================================================
    // A. ROLE MIDDLEWARE — area guru
    // =================================================================

    public function test_teacher_can_access_guru_area(): void
    {
        [$teacher] = $this->makeTeacherUser('guru_rbac_b');

        $this->actingAs($teacher)
            ->get('/guru/dashboard')
            ->assertStatus(200)
            ->assertSee('Guru');
    }

    public function test_admin_is_forbidden_from_teacher_area(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_rbac_guru');

        // ADR 04 FINAL: Admin/TU & Guru adalah akun & area terpisah.
        $this->actingAs($admin)->get('/guru/dashboard')->assertForbidden();
    }

    public function test_supervisor_is_forbidden_from_teacher_area(): void
    {
        $sup = $this->makeUser(User::ROLE_SUPERVISOR, 'kepsek_rbac_guru');

        $this->actingAs($sup)->get('/guru/dashboard')->assertForbidden();
    }

    // =================================================================
    // A. ROLE MIDDLEWARE — area supervisor
    // =================================================================

    public function test_supervisor_can_access_supervisor_area(): void
    {
        $sup = $this->makeUser(User::ROLE_SUPERVISOR, 'kepsek_rbac_sup');

        $this->actingAs($sup)
            ->get('/supervisor/dashboard')
            ->assertStatus(200)
            ->assertSee('Atasan / Supervisor');
    }

    public function test_admin_is_forbidden_from_supervisor_area(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_rbac_sup');

        // ADR 17 & §5.4: area review dokumen adalah hak supervisor.
        $this->actingAs($admin)->get('/supervisor/dashboard')->assertForbidden();
    }

    public function test_teacher_is_forbidden_from_supervisor_area(): void
    {
        [$teacher] = $this->makeTeacherUser('guru_rbac_sup');

        $this->actingAs($teacher)->get('/supervisor/dashboard')->assertForbidden();
    }

    // =================================================================
    // B. DIRECT URL ACCESS & GUEST
    // =================================================================

    public function test_guest_is_redirected_to_login_on_role_areas(): void
    {
        // Tamu belum terautentikasi -> perilaku standar auth (bukan 403).
        foreach (['/admin/dashboard', '/guru/dashboard', '/supervisor/dashboard'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_role_check_is_not_bypassable_by_header_or_query_tricks(): void
    {
        $teacher = $this->makeUser(User::ROLE_TEACHER, 'guru_rbac_spoof');

        // Mencoba "memalsukan" role lewat input klien; server hanya percaya
        // kolom users.role dari database.
        $this->actingAs($teacher)
            ->withHeaders(['X-Role' => 'admin', 'X-Requested-Role' => 'admin'])
            ->get('/admin/dashboard?role=admin')
            ->assertForbidden();
    }

    public function test_role_middleware_denies_when_no_role_configured(): void
    {
        // Deny by default: `role:` tanpa argumen tidak boleh meloloskan siapa pun,
        // bahkan admin.
        Route::middleware(['auth', 'role:'])->get('/__test/no-role', fn () => 'OK')->name('test.norole');

        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_rbac_norole');

        $this->actingAs($admin)->get('/__test/no-role')->assertForbidden();
    }

    public function test_authorization_denial_is_logged_server_side(): void
    {
        Log::spy();

        $teacher = $this->makeUser(User::ROLE_TEACHER, 'guru_rbac_log');

        $this->actingAs($teacher)->get('/admin/dashboard')->assertForbidden();

        Log::shouldHaveReceived('warning')
            ->with('Authorization denied', \Mockery::on(fn ($c) => ($c['user_role'] ?? null) === 'teacher'
                && ($c['path'] ?? null) === 'admin/dashboard'));
    }

    // =================================================================
    // C. IDOR — Dokumen (PRD 04 §16.2, §5.3, ADR 04)
    // =================================================================

    private function makeDocumentOf(Teacher $teacher): Document
    {
        [$year, $class, $subject] = $this->makeAcademicContext();

        return Document::create([
            'teacher_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'category' => 'RPP',
            'title' => 'RPP Milik ' . $teacher->full_name,
            'status' => 'DRAFT',
        ]);
    }

    public function test_teacher_cannot_view_or_modify_document_of_another_teacher(): void
    {
        [$guruA, $teacherA] = $this->makeTeacherUser('guru_idor_a');
        [$guruB, $teacherB] = $this->makeTeacherUser('guru_idor_b');

        $document = $this->makeDocumentOf($teacherB);

        // Guru B (pemilik) boleh.
        $this->assertTrue($guruB->fresh()->can('view', $document));
        $this->assertTrue($guruB->fresh()->can('update', $document));

        // Guru A bukan pemilik -> DITOLAK (IDOR).
        $this->assertFalse($guruA->fresh()->can('view', $document));
        $this->assertFalse($guruA->fresh()->can('update', $document));
        $this->assertFalse($guruA->fresh()->can('delete', $document));
        $this->assertFalse($guruA->fresh()->can('submit', $document));
    }

    public function test_supervisor_can_read_but_not_modify_teacher_document(): void
    {
        [, $teacher] = $this->makeTeacherUser('guru_sup_doc');
        $sup = $this->makeUser(User::ROLE_SUPERVISOR, 'kepsek_sup_doc');

        $document = $this->makeDocumentOf($teacher);

        // Read-only + hak review (PRD 01 §5.3: tidak dapat mengedit/menghapus file guru).
        $this->assertTrue($sup->can('view', $document));
        $this->assertTrue($sup->can('review', $document));
        $this->assertFalse($sup->can('update', $document));
        $this->assertFalse($sup->can('delete', $document));
        $this->assertFalse($sup->can('create', Document::class));
        $this->assertFalse($sup->can('submit', $document));
    }

    public function test_admin_cannot_create_or_modify_teacher_documents(): void
    {
        [, $teacher] = $this->makeTeacherUser('guru_admin_doc');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_rbac_doc');

        $document = $this->makeDocumentOf($teacher);

        // ADR 04 FINAL + §5.4: Admin tidak punya modul unggah/review dokumen,
        // dan tidak dapat mengubah file guru secara sepihak.
        $this->assertFalse($admin->can('create', Document::class));
        $this->assertFalse($admin->can('update', $document));
        $this->assertFalse($admin->can('delete', $document));
        $this->assertFalse($admin->can('review', $document));

        // Monitoring kelengkapan = hak baca.
        $this->assertTrue($admin->can('view', $document));
    }

    public function test_teacher_without_profile_cannot_create_documents(): void
    {
        // Fail closed: akun role teacher tanpa baris `teachers` ditolak.
        $orphan = $this->makeUser(User::ROLE_TEACHER, 'guru_tanpa_profil');

        $this->assertFalse($orphan->can('create', Document::class));
        $this->assertNull($orphan->teacherId());
    }

    // =================================================================
    // C. IDOR — Sesi absensi mata pelajaran (PRD 02 §6, PRD 04 §16.2)
    // =================================================================

    private function makeSessionOf(array $teacherPair): StudentAttendanceSession
    {
        [$user, $teacher] = $teacherPair;
        [$year, $class, $subject] = $this->makeAcademicContext();

        $assignment = TeachingAssignment::create([
            'academic_year_id' => $year->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
        ]);

        return StudentAttendanceSession::create([
            'teaching_assignment_id' => $assignment->id,
            'session_date' => '2026-09-28',
            'meeting_order' => 1,
            'status' => 'OPEN',
            'opened_by_teacher_id' => $teacher->id,
        ]);
    }

    public function test_teacher_cannot_edit_attendance_session_outside_assignment(): void
    {
        $pairA = $this->makeTeacherUser('guru_att_a');
        $pairB = $this->makeTeacherUser('guru_att_b');

        $session = $this->makeSessionOf($pairA);

        // Pemegang penugasan -> boleh.
        $this->assertTrue($pairA[0]->fresh()->can('update', $session));

        // Guru lain mencoba akses via ID sesi dari URL -> 403 (anti-IDOR).
        $this->assertFalse($pairB[0]->fresh()->can('update', $session));
        $this->assertFalse($pairB[0]->fresh()->can('view', $session));
        $this->assertFalse($pairB[0]->fresh()->can('delete', $session));
        $this->assertFalse($pairB[0]->fresh()->can('correct', $session));
    }

    public function test_only_admin_may_correct_or_delete_attendance_sessions(): void
    {
        $pairA = $this->makeTeacherUser('guru_att_c');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_att_c');
        $sup = $this->makeUser(User::ROLE_SUPERVISOR, 'kepsek_att_c');

        $session = $this->makeSessionOf($pairA);

        // ADM-ABS-003: koreksi = hak khusus Admin/TU.
        $this->assertTrue($admin->can('correct', $session));
        $this->assertTrue($admin->can('delete', $session));

        // Supervisor read-only; guru non-pemilik ditolak.
        $this->assertFalse($sup->can('correct', $session));
        $this->assertFalse($sup->can('delete', $session));
        $this->assertTrue($sup->can('view', $session));
        $this->assertFalse($pairA[0]->fresh()->can('correct', $session));
    }

    // =================================================================
    // C. IDOR — Teaching assignment (PRD 01 §5.4, §6.7)
    // =================================================================

    public function test_teaching_assignment_read_only_for_owner_and_crud_for_admin(): void
    {
        [$guruA, $teacherA] = $this->makeTeacherUser('guru_assign_a');
        [$guruB, $teacherB] = $this->makeTeacherUser('guru_assign_b');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_assign');
        [$year, $class, $subject] = $this->makeAcademicContext();

        $own = TeachingAssignment::create([
            'academic_year_id' => $year->id,
            'teacher_id' => $teacherA->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
        ]);

        $other = TeachingAssignment::create([
            'academic_year_id' => $year->id,
            'teacher_id' => $teacherB->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
        ]);

        // Guru: read-only milik sendiri (bukan CRUD).
        $this->assertTrue($guruA->fresh()->can('view', $own));
        $this->assertFalse($guruA->fresh()->can('view', $other));
        $this->assertFalse($guruA->fresh()->can('update', $own));
        $this->assertFalse($guruA->fresh()->can('create', TeachingAssignment::class));

        // Admin/TU: CRUD penuh.
        $this->assertTrue($admin->can('create', TeachingAssignment::class));
        $this->assertTrue($admin->can('update', $other));
        $this->assertTrue($admin->can('delete', $other));
    }

    // =================================================================
    // D. ACCOUNT ACTIVE (Prompt 24) tetap berlaku pada rute role-scoped
    // =================================================================

    public function test_inactive_admin_session_is_terminated_on_admin_route(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_nonaktif_rbac');

        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);

        $admin->update(['is_active' => false]);

        $this->get('/admin/dashboard')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    // =================================================================
    // E. LARANGAN PRD: role/flag "piket" tidak pernah ada
    // =================================================================

    public function test_piket_is_not_an_official_role(): void
    {
        $this->assertSame(['admin', 'teacher', 'supervisor'], User::OFFICIAL_ROLES);
        $this->assertFalse(User::isValidRole('piket'));
        $this->assertFalse(User::isValidRole('guru_piket'));
        $this->assertTrue(User::isValidRole('admin'));
    }

    public function test_route_using_piket_role_is_denied_even_for_admin(): void
    {
        // Konfigurasi rute dengan role di luar 3 role resmi harus MENOLAK,
        // bukan meloloskan (pengaman terhadap munculnya "role piket").
        Route::middleware(['auth', 'role:piket'])->get('/__test/piket', fn () => 'OK');

        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_piket_guard');
        $this->actingAs($admin)->get('/__test/piket')->assertForbidden();

        // ...dan akun ber-role 'piket' pun tidak akan pernah sah dibuat.
        $this->assertFalse(User::isValidRole('PIKET'));
    }

    public function test_no_route_exists_for_scanner_yet(): void
    {
        // Prompt 25 TIDAK boleh membangun fitur scanner.
        $this->assertFalse(Route::has('guru.school-attendance-scanner'));
    }

    public function test_dashboard_foundation_route_still_available_for_all_roles(): void
    {
        // Rute /dashboard Prompt 24 tidak boleh di-rewrite/hilang.
        foreach (['admin', 'teacher', 'supervisor'] as $role) {
            $user = $role === 'teacher'
                ? $this->makeTeacherUser("guru_dash_{$role}")[0]
                : $this->makeUser($role, "dash_{$role}");

            $this->actingAs($user)->get('/dashboard')->assertStatus(200);
        }
    }
}
