<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendanceSession;
use App\Models\TeachingAssignment;
use App\Services\StudentAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controller absensi siswa per mata pelajaran (PRD 01 §7.5 GUR-ATT-001–004).
 *
 * Acuan:
 * - PRD 01 §7.5: GUR-ATT-001 (buka sesi), GUR-ATT-002 (pre-fill HADIR),
 *   GUR-ATT-003 (input status), GUR-ATT-004 (simpan & kunci manual)
 * - PRD 05 §5.11: ADR 14 — penutupan manual (tidak otomatis)
 * - PRD 04 §3.2.3: anti-IDOR, cek kepemilikan assignment server-side
 * - PRD 02 §13: skema student_attendance_sessions & student_attendance_records
 */
class StudentAttendanceController extends Controller
{
    public function __construct(
        private StudentAttendanceService $attendanceService
    ) {}

    /**
     * Daftar sesi absensi milik guru yang login.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StudentAttendanceSession::class);

        $filters = $request->only(['status', 'date_from', 'date_to']);
        $sessions = $this->attendanceService->getTeacherSessions(
            $request->user(),
            $filters
        );

        $assignments = $this->attendanceService->getTeacherAssignments($request->user());

        return view('guru.attendance.index', [
            'sessions' => $sessions,
            'assignments' => $assignments,
            'filters' => $filters,
        ]);
    }

    /**
     * Form buka sesi absensi baru (GUR-ATT-001).
     */
    public function create(Request $request)
    {
        Gate::authorize('create', StudentAttendanceSession::class);

        $assignments = $this->attendanceService->getTeacherAssignments($request->user());

        if ($assignments->isEmpty()) {
            return redirect()
                ->route('guru.dashboard')
                ->with('error', 'Anda belum memiliki penugasan mengajar.');
        }

        // Pre-select assignment jika ada di query parameter
        $selectedAssignment = null;
        if ($request->has('assignment_id')) {
            $selectedAssignment = $assignments->firstWhere('id', $request->input('assignment_id'));
        }

        return view('guru.attendance.create', [
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
        ]);
    }

    /**
     * Simpan sesi absensi baru + pre-fill siswa HADIR (GUR-ATT-001 & GUR-ATT-002).
     */
    public function store(Request $request)
    {
        Gate::authorize('create', StudentAttendanceSession::class);

        $validated = $request->validate([
            'teaching_assignment_id' => 'required|exists:teaching_assignments,id',
            'session_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ], [
            'teaching_assignment_id.required' => 'Penugasan mengajar wajib dipilih.',
            'teaching_assignment_id.exists' => 'Penugasan mengajar tidak valid.',
            'session_date.required' => 'Tanggal sesi wajib diisi.',
            'session_date.date' => 'Format tanggal tidak valid.',
            'session_date.before_or_equal' => 'Tanggal sesi tidak boleh di masa depan.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ]);

        try {
            $session = $this->attendanceService->openSession(
                teachingAssignmentId: $validated['teaching_assignment_id'],
                sessionDate: $validated['session_date'],
                openedByUser: $request->user(),
                notes: $validated['notes'] ?? null
            );

            return redirect()
                ->route('guru.attendance.edit', $session)
                ->with('success', 'Sesi absensi berhasil dibuka. Silakan input kehadiran siswa.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuka sesi: ' . $e->getMessage());
        }
    }

    /**
     * Detail sesi absensi (read-only atau edit sesuai status).
     */
    public function show(StudentAttendanceSession $session)
    {
        // Route model binding otomatis resolve via UUID (getRouteKeyName)
        $session->load([
            'teachingAssignment.subject',
            'teachingAssignment.classRoom.gradeLevel',
            'teachingAssignment.academicYear',
            'teachingAssignment.teacher.user',
            'openedByTeacher.user',
            'records.student',
            'records.correctedByUser',
        ]);

        Gate::authorize('view', $session);

        $statistics = $this->attendanceService->getSessionStatistics($session);
        $isEditable = $this->attendanceService->isSessionEditable($session);

        return view('guru.attendance.show', [
            'session' => $session,
            'statistics' => $statistics,
            'isEditable' => $isEditable,
        ]);
    }

    /**
     * Form edit kehadiran siswa (GUR-ATT-003).
     *
     * Hanya bisa diakses jika sesi masih OPEN.
     */
    public function edit(StudentAttendanceSession $session)
    {
        // Route model binding otomatis resolve via UUID (getRouteKeyName)
        $session->load([
            'teachingAssignment.subject',
            'teachingAssignment.classRoom.gradeLevel',
            'teachingAssignment.academicYear',
            'records.student',
        ]);

        Gate::authorize('update', $session);

        if (!$this->attendanceService->isSessionEditable($session)) {
            return redirect()
                ->route('guru.attendance.show', $session)
                ->with('info', 'Sesi sudah ditutup. Gunakan mode tampilan.');
        }

        $statistics = $this->attendanceService->getSessionStatistics($session);

        return view('guru.attendance.edit', [
            'session' => $session,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Update kehadiran siswa dalam sesi (GUR-ATT-003).
     */
    public function update(Request $request, StudentAttendanceSession $session)
    {
        // Route model binding otomatis resolve via UUID (getRouteKeyName)
        Gate::authorize('update', $session);

        $validated = $request->validate([
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:HADIR,IZIN,SAKIT,ALPHA',
            'attendance.*.notes' => 'nullable|string|max:255',
        ], [
            'attendance.required' => 'Data kehadiran wajib diisi.',
            'attendance.*.status.required' => 'Status kehadiran wajib dipilih.',
            'attendance.*.status.in' => 'Status kehadiran tidak valid (HADIR/IZIN/SAKIT/ALPHA).',
            'attendance.*.notes.max' => 'Catatan maksimal 255 karakter.',
        ]);

        try {
            $this->attendanceService->updateAttendance($session, $validated['attendance']);

            return back()->with('success', 'Kehadiran siswa berhasil disimpan.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan kehadiran: ' . $e->getMessage());
        }
    }

    /**
     * Tutup sesi absensi (GUR-ATT-004).
     *
     * Manual close sesuai ADR 14 (PRD 05 §5.11).
     */
    public function close(Request $request, StudentAttendanceSession $session)
    {
        // Route model binding otomatis resolve via UUID (getRouteKeyName)
        Gate::authorize('update', $session);

        try {
            $this->attendanceService->closeSession($session);

            return redirect()
                ->route('guru.attendance.show', $session)
                ->with('success', 'Sesi absensi berhasil ditutup dan dikunci.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menutup sesi: ' . $e->getMessage());
        }
    }

    /**
     * Buka kembali sesi absensi (untuk koreksi sebelum final).
     *
     * Hanya bisa jika belum ada koreksi dari Admin/TU.
     */
    public function reopen(Request $request, StudentAttendanceSession $session)
    {
        // Route model binding otomatis resolve via UUID (getRouteKeyName)
        Gate::authorize('update', $session);

        try {
            $this->attendanceService->reopenSession($session);

            return redirect()
                ->route('guru.attendance.edit', $session)
                ->with('success', 'Sesi berhasil dibuka kembali untuk koreksi.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuka kembali sesi: ' . $e->getMessage());
        }
    }
}
