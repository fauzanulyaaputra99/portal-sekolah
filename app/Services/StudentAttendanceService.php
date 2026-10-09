<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentAttendanceSession;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service untuk absensi siswa per mata pelajaran (PRD 01 §7.5).
 *
 * Acuan:
 * - PRD 01 §7.5 GUR-ATT-001–004: buka sesi, pre-fill HADIR, simpan, kunci manual
 * - PRD 05 §5.11: ADR 14 — penutupan sesi MANUAL (tidak otomatis)
 * - PRD 02 §13: tabel student_attendance_sessions, student_attendance_records
 * - PRD 04 §3.2.3: anti-IDOR, validasi kepemilikan assignment
 */
class StudentAttendanceService
{
    /**
     * Ambil daftar teaching assignment milik guru yang login,
     * beserta informasi terkait untuk ditampilkan di daftar sesi.
     */
    public function getTeacherAssignments(User $user)
    {
        $teacherId = $user->teacherId();

        if ($teacherId === null) {
            return collect();
        }

        return TeachingAssignment::query()
            ->where('teacher_id', $teacherId)
            ->with([
                'academicYear',
                'subject',
                'classRoom.gradeLevel',
                'attendanceSessions' => function ($query) {
                    $query->latest('session_date')->latest('created_at')->limit(5);
                }
            ])
            ->get();
    }

    /**
     * Ambil daftar sesi absensi milik guru (untuk halaman index).
     */
    public function getTeacherSessions(User $user, array $filters = [])
    {
        $teacherId = $user->teacherId();

        if ($teacherId === null) {
            return collect();
        }

        $query = StudentAttendanceSession::query()
            ->whereHas('teachingAssignment', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })
            ->with([
                'teachingAssignment.subject',
                'teachingAssignment.classRoom.gradeLevel',
                'teachingAssignment.academicYear',
                'openedByTeacher',
                'records'
            ]);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->where('session_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('session_date', '<=', $filters['date_to']);
        }

        return $query->latest('session_date')
            ->latest('created_at')
            ->paginate(20);
    }

    /**
     * Buka sesi absensi baru (GUR-ATT-001).
     *
     * Pre-fill seluruh siswa dengan status HADIR (GUR-ATT-002).
     * Meeting order otomatis increment dari sesi terakhir di assignment yang sama.
     *
     * @throws \Exception
     */
    public function openSession(
        int $teachingAssignmentId,
        string $sessionDate,
        User $openedByUser,
        ?string $notes = null
    ): StudentAttendanceSession {
        return DB::transaction(function () use (
            $teachingAssignmentId,
            $sessionDate,
            $openedByUser,
            $notes
        ) {
            $teacherId = $openedByUser->teacherId();

            if ($teacherId === null) {
                throw new \Exception('Guru tidak memiliki profil teacher yang valid.');
            }

            // Validasi: assignment milik guru ini?
            $assignment = TeachingAssignment::query()
                ->where('id', $teachingAssignmentId)
                ->where('teacher_id', $teacherId)
                ->firstOrFail();

            // Cek duplikasi sesi pada tanggal yang sama
            $existingSession = StudentAttendanceSession::query()
                ->where('teaching_assignment_id', $teachingAssignmentId)
                ->where('session_date', $sessionDate)
                ->exists();

            if ($existingSession) {
                throw new \Exception('Sesi absensi untuk tanggal ini sudah ada.');
            }

            // Hitung meeting order berikutnya
            $lastMeetingOrder = StudentAttendanceSession::query()
                ->where('teaching_assignment_id', $teachingAssignmentId)
                ->max('meeting_order') ?? 0;

            // Buat sesi
            $session = StudentAttendanceSession::create([
                'teaching_assignment_id' => $teachingAssignmentId,
                'session_date' => $sessionDate,
                'meeting_order' => $lastMeetingOrder + 1,
                'status' => 'OPEN',
                'opened_at' => now(),
                'opened_by_teacher_id' => $teacherId,
                'notes' => $notes,
            ]);

            // Pre-fill siswa dengan status HADIR (GUR-ATT-002)
            $this->prefillStudents($session, $assignment->class_id);

            return $session->load('records.student');
        });
    }

    /**
     * Pre-fill seluruh siswa di kelas dengan status HADIR (PRD 01 §7.5 GUR-ATT-002).
     */
    private function prefillStudents(StudentAttendanceSession $session, int $classId): void
    {
        // Ambil semua siswa yang terdaftar di kelas (class_enrollments)
        $enrollments = ClassEnrollment::query()
            ->where('class_id', $classId)
            ->where('academic_year_id', $session->teachingAssignment->academic_year_id)
            ->with('student')
            ->get();

        $records = $enrollments->map(function ($enrollment) use ($session) {
            return [
                'session_id' => $session->id,
                'student_id' => $enrollment->student_id,
                'status' => 'HADIR', // Default: HADIR (PRD 01 §7.5 GUR-ATT-002)
                'notes' => null,
                'is_corrected' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        if (!empty($records)) {
            StudentAttendanceRecord::insert($records);
        }
    }

    /**
     * Update status kehadiran siswa dalam sesi (GUR-ATT-003).
     *
     * Hanya boleh update jika sesi masih OPEN.
     *
     * @param array $attendanceData Format: ['student_id' => ['status' => 'HADIR|IZIN|SAKIT|ALPHA', 'notes' => '...']]
     * @throws \Exception
     */
    public function updateAttendance(
        StudentAttendanceSession $session,
        array $attendanceData
    ): StudentAttendanceSession {
        if ($session->status !== 'OPEN') {
            throw new \Exception('Sesi sudah ditutup. Tidak dapat mengubah kehadiran.');
        }

        DB::transaction(function () use ($session, $attendanceData) {
            foreach ($attendanceData as $studentId => $data) {
                StudentAttendanceRecord::query()
                    ->where('session_id', $session->id)
                    ->where('student_id', $studentId)
                    ->update([
                        'status' => $data['status'] ?? 'HADIR',
                        'notes' => $data['notes'] ?? null,
                        'updated_at' => now(),
                    ]);
            }
        });

        return $session->fresh(['records.student']);
    }

    /**
     * Tutup sesi absensi (GUR-ATT-004).
     *
     * Manual close sesuai ADR 14 (PRD 05 §5.11).
     * Setelah CLOSED, sesi menjadi read-only.
     *
     * @throws \Exception
     */
    public function closeSession(StudentAttendanceSession $session): StudentAttendanceSession
    {
        if ($session->status !== 'OPEN') {
            throw new \Exception('Sesi sudah ditutup sebelumnya.');
        }

        $session->update([
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);

        return $session->fresh();
    }

    /**
     * Reopen sesi absensi (untuk koreksi sebelum benar-benar final).
     *
     * Hanya untuk sesi yang baru saja ditutup dan belum ada koreksi admin.
     *
     * @throws \Exception
     */
    public function reopenSession(StudentAttendanceSession $session): StudentAttendanceSession
    {
        if ($session->status !== 'CLOSED') {
            throw new \Exception('Sesi tidak dalam status CLOSED.');
        }

        // Cek apakah ada record yang sudah dikoreksi admin
        $hasCorrectedRecords = $session->records()->where('is_corrected', true)->exists();

        if ($hasCorrectedRecords) {
            throw new \Exception('Sesi tidak dapat dibuka kembali karena sudah ada koreksi dari Admin/TU.');
        }

        $session->update([
            'status' => 'OPEN',
            'closed_at' => null,
        ]);

        return $session->fresh();
    }

    /**
     * Ambil detail sesi beserta semua record kehadiran siswa.
     */
    public function getSessionDetail(int $sessionId): ?StudentAttendanceSession
    {
        return StudentAttendanceSession::query()
            ->with([
                'teachingAssignment.subject',
                'teachingAssignment.classRoom.gradeLevel',
                'teachingAssignment.academicYear',
                'teachingAssignment.teacher.user',
                'openedByTeacher.user',
                'records.student' => function ($query) {
                    $query->orderBy('full_name');
                },
                'records.correctedByUser'
            ])
            ->find($sessionId);
    }

    /**
     * Validasi apakah sesi dapat diedit (masih OPEN).
     */
    public function isSessionEditable(StudentAttendanceSession $session): bool
    {
        return $session->status === 'OPEN';
    }

    /**
     * Hitung statistik kehadiran dari sesi.
     */
    public function getSessionStatistics(StudentAttendanceSession $session): array
    {
        $records = $session->records;
        $total = $records->count();

        return [
            'total' => $total,
            'hadir' => $records->where('status', 'HADIR')->count(),
            'izin' => $records->where('status', 'IZIN')->count(),
            'sakit' => $records->where('status', 'SAKIT')->count(),
            'alpha' => $records->where('status', 'ALPHA')->count(),
            'corrected' => $records->where('is_corrected', true)->count(),
        ];
    }
}
