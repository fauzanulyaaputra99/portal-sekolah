<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentAttendanceSession;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_migrations_and_foreign_keys_work_correctly(): void
    {
        // 1. Create Academic Year & Grade Level
        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'GANJIL',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Tingkat 7',
            'level_order' => 7,
        ]);

        // 2. Create User and Teacher Profile
        $userTeacher = User::create([
            'username' => '198501012010011001',
            'email' => 'budi@sekolah.demo',
            'password' => 'secret123',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'user_id' => $userTeacher->id,
            'nip' => '198501012010011001',
            'full_name' => 'Budi Santoso, S.Pd.',
            'gender' => 'L',
        ]);

        // 3. Create Class Room (Dynamic Class)
        $class7A = ClassRoom::create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => '7A',
            'homeroom_teacher_id' => $teacher->id,
        ]);

        // 4. Create Subject and Teaching Assignment
        $subjectMtk = Subject::create([
            'code' => 'MTK-07',
            'name' => 'Matematika',
            'category' => 'UMUM',
        ]);

        $assignment = TeachingAssignment::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subjectMtk->id,
            'class_id' => $class7A->id,
        ]);

        // 5. Create Student and Class Enrollment
        $student = Student::create([
            'nis' => '26270701',
            'nisn' => '0098000001',
            'full_name' => 'Ahmad Dahlan',
            'gender' => 'L',
            'status' => 'AKTIF',
        ]);

        $enrollment = ClassEnrollment::create([
            'academic_year_id' => $academicYear->id,
            'class_id' => $class7A->id,
            'student_id' => $student->id,
        ]);

        // 6. Test Document Versioning Relationships
        $document = Document::create([
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'subject_id' => $subjectMtk->id,
            'class_id' => $class7A->id,
            'category' => 'RPP',
            'title' => 'RPP Bab 1 Aljabar',
            'status' => 'DRAFT',
        ]);

        $version1 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'gdrive_file_id' => 'gdrive_file_abc123',
            'original_filename' => 'rpp_bab1.pdf',
            'file_size_bytes' => 1024000,
            'mime_type' => 'application/pdf',
            'file_hash_sha256' => hash('sha256', 'dummy_content'),
            'uploaded_by_user_id' => $userTeacher->id,
        ]);

        $document->update(['current_version_id' => $version1->id]);

        // 7. Test Student Attendance Session and Record
        $attSession = StudentAttendanceSession::create([
            'teaching_assignment_id' => $assignment->id,
            'session_date' => '2026-08-01',
            'meeting_order' => 1,
            'status' => 'OPEN',
            'opened_by_teacher_id' => $teacher->id,
        ]);

        $attRecord = StudentAttendanceRecord::create([
            'session_id' => $attSession->id,
            'student_id' => $student->id,
            'status' => 'HADIR',
        ]);

        // Assertions
        $this->assertDatabaseHas('users', ['username' => '198501012010011001']);
        $this->assertDatabaseHas('teachers', ['full_name' => 'Budi Santoso, S.Pd.']);
        $this->assertDatabaseHas('classes', ['name' => '7A']);
        $this->assertDatabaseHas('teaching_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseHas('class_enrollments', ['student_id' => $student->id]);
        $this->assertDatabaseHas('documents', ['title' => 'RPP Bab 1 Aljabar']);
        $this->assertDatabaseHas('document_versions', ['gdrive_file_id' => 'gdrive_file_abc123']);
        $this->assertDatabaseHas('student_attendance_records', ['status' => 'HADIR']);

        $this->assertEquals($version1->id, $document->fresh()->currentVersion->id);
        $this->assertEquals('Budi Santoso, S.Pd.', $class7A->homeroomTeacher->full_name);
        $this->assertEquals('7A', $student->classEnrollments->first()->classRoom->name);
    }
}
