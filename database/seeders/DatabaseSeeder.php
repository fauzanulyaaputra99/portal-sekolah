<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\GradeLevel;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Initial Settings (ADR 02: Dynamic teacher attendance cutoff)
        Setting::updateOrCreate(
            ['key' => 'teacher_attendance_late_cutoff'],
            [
                'value' => '07:15:00',
                'description' => 'Batas jam toleransi check-in guru sebelum dianggap terlambat (WIB)',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'school_name'],
            [
                'value' => 'SMP Demo Portal Sekolah',
                'description' => 'Nama resmi institusi sekolah',
            ]
        );

        // 2. Academic Year
        $academicYear = AcademicYear::updateOrCreate(
            ['name' => '2026/2027', 'semester' => 'GANJIL'],
            [
                'is_active' => true,
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
            ]
        );

        // 3. Grade Levels (7, 8, 9)
        $grade7 = GradeLevel::updateOrCreate(['name' => 'Tingkat 7'], ['level_order' => 7]);
        $grade8 = GradeLevel::updateOrCreate(['name' => 'Tingkat 8'], ['level_order' => 8]);
        $grade9 = GradeLevel::updateOrCreate(['name' => 'Tingkat 9'], ['level_order' => 9]);

        // 4. Initial Users (Admin/TU, Guru Contoh, Atasan/Supervisor)
        $adminUser = User::updateOrCreate(
            ['username' => 'admin_tu'],
            [
                'email' => 'admin@sekolah.demo',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $guruUser = User::updateOrCreate(
            ['username' => '198501012010011001'],
            [
                'email' => 'guru.budi@sekolah.demo',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'is_active' => true,
            ]
        );

        $supervisorUser = User::updateOrCreate(
            ['username' => 'supervisor_kepsek'],
            [
                'email' => 'kepsek@sekolah.demo',
                'password' => Hash::make('password123'),
                'role' => 'supervisor',
                'is_active' => true,
            ]
        );

        // 5. Teacher Profile
        $teacherBudi = Teacher::updateOrCreate(
            ['user_id' => $guruUser->id],
            [
                'nip' => '198501012010011001',
                'full_name' => 'Budi Santoso, S.Pd.',
                'gender' => 'L',
                'phone_number' => '081234567890',
                'employment_status' => 'GTY',
                'address' => 'Jl. Pendidikan No. 12, Kota Pendidikan',
            ]
        );

        // 6. Baseline Class 7A (Dynamic Class Demo)
        $class7A = ClassRoom::updateOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => '7A'],
            [
                'grade_level_id' => $grade7->id,
                'homeroom_teacher_id' => $teacherBudi->id,
            ]
        );

        // 7. Subjects
        $subjectMtk = Subject::updateOrCreate(
            ['code' => 'MTK-07'],
            [
                'name' => 'Matematika Kelas 7',
                'category' => 'UMUM',
            ]
        );

        $subjectBindo = Subject::updateOrCreate(
            ['code' => 'BIN-07'],
            [
                'name' => 'Bahasa Indonesia Kelas 7',
                'category' => 'UMUM',
            ]
        );

        // 8. Teaching Assignment (Guru Budi -> MTK-07 -> 7A -> 2026/2027)
        TeachingAssignment::updateOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'teacher_id' => $teacherBudi->id,
                'subject_id' => $subjectMtk->id,
                'class_id' => $class7A->id,
            ]
        );

        // 9. Baseline Sample Students (27 siswa dummy untuk development)
        for ($i = 1; $i <= 27; $i++) {
            $nis = sprintf('262707%02d', $i);
            $student = Student::updateOrCreate(
                ['nis' => $nis],
                [
                    'nisn' => '0098' . sprintf('%06d', $i),
                    'full_name' => 'Siswa Contoh ' . $i . ' (Dev Data)',
                    'gender' => ($i % 2 === 0) ? 'P' : 'L',
                    'birth_date' => '2013-05-10',
                    'parent_name' => 'Orang Tua Siswa ' . $i,
                    'parent_phone' => '0812987654' . sprintf('%02d', $i),
                    'status' => 'AKTIF',
                ]
            );

            ClassEnrollment::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'student_id' => $student->id,
                ],
                [
                    'class_id' => $class7A->id,
                ]
            );
        }
    }
}
