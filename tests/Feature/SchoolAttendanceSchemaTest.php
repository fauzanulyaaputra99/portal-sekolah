<?php

namespace Tests\Feature;

use App\Models\SchoolAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Adjustment A — Penyelarasan Database Foundation dengan PRD FINAL.
 *
 * Menjamin skema fondasi fitur Absensi Masuk/Pulang Sekolah (barcode) dan
 * Jadwal Guru Piket, sekaligus mengunci aturan yang DILARANG oleh ADDENDUM
 * sebagai guard anti-regresi.
 *
 * Referensi: 02-architecture-database.md §10.6, §10.15, §10.15b, §17.1, §17.2.
 */
class SchoolAttendanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: buat guru + siswa minimal untuk uji constraint.
     */
    private function makeTeacherAndStudent(string $suffix = '1'): array
    {
        $user = User::create([
            'username' => 'guru_' . $suffix,
            'email' => "guru{$suffix}@sekolah.demo",
            'password' => 'rahasia123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nip' => '19850101201001' . $suffix . '001',
            'full_name' => 'Guru Uji ' . $suffix,
            'gender' => 'L',
        ]);

        $student = Student::create([
            'nis' => '262707' . $suffix . '9',
            'nisn' => '00980000' . $suffix . '9',
            'barcode_code' => 'BC-' . $suffix,
            'full_name' => 'Siswa Uji ' . $suffix,
            'gender' => 'L',
            'status' => 'AKTIF',
        ]);

        return [$user, $teacher, $student];
    }

    // =====================================================================
    // 1 & 2 — students.barcode_code tersedia & unik
    // =====================================================================

    public function test_students_table_has_barcode_code_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('students', 'barcode_code'),
            'Kolom students.barcode_code wajib ada (PRD 02 §10.6).'
        );
    }

    public function test_barcode_code_is_unique_per_student(): void
    {
        [, , $studentA] = $this->makeTeacherAndStudent('a');

        // NIS/NISN berbeda, barcode sama -> harus ditolak database.
        $this->expectException(QueryException::class);

        Student::create([
            'nis' => '26270799',
            'nisn' => '0098000099',
            'barcode_code' => $studentA->barcode_code,
            'full_name' => 'Siswa Barcode Kembar',
            'gender' => 'P',
            'status' => 'AKTIF',
        ]);
    }

    // =====================================================================
    // 3 & 4 — student_school_attendances
    // =====================================================================

    public function test_student_school_attendances_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('student_school_attendances'));

        $required = [
            'student_id', 'attendance_date', 'check_in_time', 'check_out_time',
            'check_in_by_teacher_id', 'check_out_by_teacher_id',
            'scan_mode_in', 'scan_mode_out', 'is_corrected',
            'corrected_by_user_id', 'correction_reason', 'corrected_at',
            'created_at', 'updated_at',
        ];

        foreach ($required as $column) {
            $this->assertTrue(
                Schema::hasColumn('student_school_attendances', $column),
                "Kolom student_school_attendances.{$column} wajib ada (PRD 02 §10.15)."
            );
        }
    }

    public function test_one_school_attendance_record_per_student_per_date(): void
    {
        $this->assertTrue(
            Schema::hasTable('student_school_attendances'),
            'PRD 02 §10.15 mewajibkan tabel absensi masuk/pulang sekolah.'
        );

        [$user, $teacher, $student] = $this->makeTeacherAndStudent('c');
        $date = '2026-09-28';

        SchoolAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => $date,
            'check_in_time' => '2026-09-28 06:45:00',
            'check_in_by_teacher_id' => $teacher->id,
            'scan_mode_in' => 'MASUK',
        ]);

        // Siswa sama, tanggal sama -> duplikasi ditolak level database
        // (uq_student_school_att_entry) — pencegahan scan MASUK ganda.
        $this->expectException(QueryException::class);

        SchoolAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => $date,
            'check_in_time' => '2026-09-28 07:05:00',
            'check_in_by_teacher_id' => $teacher->id,
            'scan_mode_in' => 'MASUK',
        ]);
    }

    public function test_different_students_on_same_date_are_allowed(): void
    {
        [$user, $teacher] = $this->makeTeacherAndStudent('d');

        $s1 = Student::create(['nis' => '26270801', 'barcode_code' => 'BC-D1', 'full_name' => 'Satu', 'gender' => 'L', 'status' => 'AKTIF']);
        $s2 = Student::create(['nis' => '26270802', 'barcode_code' => 'BC-D2', 'full_name' => 'Dua', 'gender' => 'P', 'status' => 'AKTIF']);

        foreach ([$s1, $s2] as $s) {
            SchoolAttendance::create([
                'student_id' => $s->id,
                'attendance_date' => '2026-09-28',
                'check_in_time' => '2026-09-28 06:50:00',
                'check_in_by_teacher_id' => $teacher->id,
                'scan_mode_in' => 'MASUK',
            ]);
        }

        $this->assertSame(2, SchoolAttendance::where('attendance_date', '2026-09-28')->count());
    }

    public function test_scan_mode_is_constrained_to_masuk_and_pulang(): void
    {
        [, $teacher, $student] = $this->makeTeacherAndStudent('e');

        // Mode scanner EKSPISIT (ADR 18). Nilai di luar MASUK/PULANG
        // harus ditolak pada level database (CHECK constraint).
        $this->expectException(QueryException::class);

        DB::insert(
            'INSERT INTO student_school_attendances
                (student_id, attendance_date, check_in_time, check_in_by_teacher_id, scan_mode_in)
             VALUES (?, ?, ?, ?, ?)',
            [$student->id, '2026-09-28', '2026-09-28 06:40:00', $teacher->id, 'CHECK-IN-AUTO']
        );
    }

    public function test_school_attendance_records_operator_and_correction_fields(): void
    {
        [$user, $teacher, $student] = $this->makeTeacherAndStudent('f');

        $attendance = SchoolAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => '2026-09-28',
            'check_in_time' => '2026-09-28 06:45:00',
            'check_in_by_teacher_id' => $teacher->id,
            'scan_mode_in' => 'MASUK',
            'check_out_time' => '2026-09-28 13:10:00',
            'check_out_by_teacher_id' => $teacher->id,
            'scan_mode_out' => 'PULANG',
            'is_corrected' => true,
            'corrected_by_user_id' => $user->id,
            'correction_reason' => 'Kartu siswa tertinggal, dikoreksi Admin/TU.',
            'corrected_at' => '2026-09-28 14:00:00',
        ]);

        $this->assertSame($student->id, $attendance->student->id);
        $this->assertSame($teacher->id, $attendance->checkInByTeacher->id);
        $this->assertSame($teacher->id, $attendance->checkOutByTeacher->id);
        $this->assertSame($user->id, $attendance->correctedByUser->id);
        $this->assertTrue($attendance->is_corrected);
        $this->assertSame('MASUK', $attendance->scan_mode_in);
        $this->assertSame('PULANG', $attendance->scan_mode_out);
    }

    // =====================================================================
    // 5 & 6 — teacher_duty_schedules
    // =====================================================================

    public function test_teacher_duty_schedules_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('teacher_duty_schedules'));

        foreach (['schedule_date', 'teacher_id', 'created_by_user_id', 'notes', 'created_at', 'updated_at'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('teacher_duty_schedules', $column),
                "Kolom teacher_duty_schedules.{$column} wajib ada (PRD 02 §10.15b)."
            );
        }
    }

    public function test_duty_schedule_is_unique_per_teacher_per_date(): void
    {
        [$user, $teacher] = $this->makeTeacherAndStudent('g');

        TeacherDutySchedule::create([
            'schedule_date' => '2026-09-28',
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        TeacherDutySchedule::create([
            'schedule_date' => '2026-09-28',
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $user->id,
        ]);
    }

    public function test_duty_schedule_date_based_lookup_works(): void
    {
        [$user, $teacher] = $this->makeTeacherAndStudent('h');

        TeacherDutySchedule::create([
            'schedule_date' => '2026-09-28',
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $user->id,
            'notes' => 'Piket harian',
        ]);

        // Inilah pola kueri otorisasi scanner per-request (tanggal server).
        $onDuty = TeacherDutySchedule::where('teacher_id', $teacher->id)
            ->whereDate('schedule_date', '2026-09-28')
            ->exists();

        $this->assertTrue($onDuty);

        // Tanggal lain => tidak bertugas (dasar pesan "bukan Guru Piket").
        $onOtherDate = TeacherDutySchedule::where('teacher_id', $teacher->id)
            ->whereDate('schedule_date', '2026-09-29')
            ->exists();

        $this->assertFalse($onOtherDate);
    }

    public function test_duty_schedule_relations(): void
    {
        [$user, $teacher] = $this->makeTeacherAndStudent('i');

        $schedule = TeacherDutySchedule::create([
            'schedule_date' => '2026-10-01',
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $user->id,
        ]);

        $this->assertSame($teacher->id, $schedule->teacher->id);
        $this->assertSame($user->id, $schedule->createdByUser->id);
        $this->assertSame(1, $teacher->dutySchedules()->count());
    }

    // =====================================================================
    // 7 — teacher_attendances sudah dihapus
    // =====================================================================

    public function test_legacy_teacher_attendances_table_is_removed(): void
    {
        $this->assertFalse(
            Schema::hasTable('teacher_attendances'),
            'Fitur absensi mandiri guru DIHAPUS dari PRD FINAL (ADR 01/02/03 OBSOLETE).'
        );
    }

    public function test_teacher_attendance_model_no_longer_exists(): void
    {
        $this->assertFalse(
            class_exists('App\Models\TeacherAttendance'),
            'Model TeacherAttendance tidak boleh lagi ada di aplikasi.'
        );

        $this->assertFalse(
            method_exists(Teacher::class, 'attendances'),
            'Relasi Teacher::attendances() (presensi guru) harus dihapus.'
        );
    }

    // =====================================================================
    // 8 — absensi MATA PELAJARAN tetap ada (bukan legacy)
    // =====================================================================

    public function test_subject_attendance_tables_are_preserved(): void
    {
        $this->assertTrue(
            Schema::hasTable('student_attendance_sessions'),
            'Absensi mata pelajaran tetap requirement (PRD 01 §7.5).'
        );
        $this->assertTrue(
            Schema::hasTable('student_attendance_records'),
            'Absensi mata pelajaran tetap requirement (PRD 01 §7.5).'
        );
    }

    // =====================================================================
    // 9 & 10 — guru piket BUKAN role/flag
    // =====================================================================

    public function test_users_table_has_no_is_piket_flag(): void
    {
        $this->assertFalse(
            Schema::hasColumn('users', 'is_piket'),
            'ADDENDUM §8 MELARANG users.is_piket. Status piket = jadwal + tanggal.'
        );
    }

    public function test_no_piket_role_is_persisted(): void
    {
        $allowedRoles = ['admin', 'teacher', 'supervisor'];

        $this->seed(DatabaseSeeder::class);

        $roles = User::pluck('role')->unique()->all();

        foreach ($roles as $role) {
            $this->assertContains(
                $role,
                $allowedRoles,
                "Role '{$role}' tidak dikenal. Role 'piket' dilarang oleh PRD FINAL."
            );
        }

        $this->assertSame(
            0,
            User::where('role', 'piket')->count(),
            'Status Guru Piket tidak boleh disimpan sebagai role.'
        );
    }

    // =====================================================================
    // 11 — seeder mengisi barcode demo secara deterministik & unik
    // =====================================================================

    public function test_seeder_generates_unique_barcodes_for_all_demo_students(): void
    {
        $this->seed(DatabaseSeeder::class);

        $total = Student::count();
        $this->assertSame(27, $total);

        $withBarcode = Student::whereNotNull('barcode_code')->count();
        $this->assertSame(27, $withBarcode, 'Semua siswa demo wajib punya identifier barcode.');

        $distinct = DB::table('students')->distinct()->count('barcode_code');
        $this->assertSame(27, $distinct, 'Barcode antar siswa harus unik.');
    }

    public function test_seeder_is_idempotent_for_barcodes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $first = Student::orderBy('id')->pluck('barcode_code')->all();

        // Menjalankan seed dua kali TIDAK boleh mengubah-ubah nilai barcode.
        $this->seed(DatabaseSeeder::class);
        $second = Student::orderBy('id')->pluck('barcode_code')->all();

        $this->assertSame($first, $second, 'Barcode demo harus deterministik, bukan acak.');
    }

    public function test_seeder_no_longer_creates_teacher_attendance_cutoff_setting(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertFalse(
            DB::table('settings')->where('key', 'teacher_attendance_late_cutoff')->exists(),
            'Setting batas terlambat guru obsolete (ADR 02 OBSOLETE) dan tidak boleh dibuat lagi.'
        );
    }
}
