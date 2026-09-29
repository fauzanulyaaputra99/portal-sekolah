<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\SchoolAttendanceScanRequest;
use App\Models\SchoolAttendance;
use App\Models\Teacher;
use App\Services\SchoolAttendanceScanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Scanner Absensi Masuk/Pulang Siswa oleh Guru Piket
 * (PRD 02 §6 rute /guru/school-attendance-scanner, PRD 01 §7.4 & §13,
 * ADDENDUM §1–§7 & §11–§16, AC-SCAN-01..06).
 *
 * Pembagian tanggung jawab:
 * - Middleware `auth` + `role:teacher` + `EnsureTeacherOnDuty`: autentikasi,
 *   role, dan status piket (server-side, direvalidasi SETIAP request).
 * - Controller: membaca hasil otorisasi, memanggil service, merangkai pesan UI.
 *   TIDAK ada logika transaksi/tanggal di sini.
 * - Service: transaksi DB, validasi otoritatif barcode & mode, stempel waktu
 *   server, operator dari sesi, audit `SCHOOL_ATT_SCAN`.
 *
 * Controller ini SENGAJA tidak pernah memakai input klien untuk identitas,
 * tanggal, atau waktu. `teacher_id`, `operator_id`, `attendance_date`,
 * `timestamp`, `role` tidak dibaca sama sekali — lihat
 * SchoolAttendanceScanRequest (whitelist `barcode` + `mode`).
 *
 * Guru piket TIDAK punya hak koreksi (PRD 01 §13.7): tidak ada aksi edit,
 * hapus, maupun daftar riwayat pada controller ini.
 */
class SchoolAttendanceScanController extends Controller
{
    public function __construct(
        private readonly SchoolAttendanceScanService $scanner,
    ) {}

    /**
     * GET /guru/school-attendance-scanner — halaman scanner.
     *
     * Middleware sudah memastikan pengakses adalah guru terjadwal piket pada
     * tanggal server; pemeriksaan di bawah hanya penjaga ganda agar rute ini
     * tidak pernah merender panel scanner tanpa jadwal yang sah.
     */
    public function index(Request $request): View
    {
        $teacher = $this->teacherFor($request);

        return view('guru.school-attendance-scanner.index', [
            'teacher' => $teacher,
            'serverDate' => $this->scanner->serverDate(),
            'dutySchedule' => $this->scanner->dutyScheduleFor($teacher),
            'modes' => SchoolAttendanceScanService::MODES,
            'activeMode' => $this->activeModeFor($request),
            'lastScan' => $this->lastScanFor($teacher),
        ]);
    }

    /**
     * Mode yang masih aktif setelah scan terakhir (ADDENDUM §7: mode bertahan
     * antar-scan tanpa logout).
     *
     * Nilainya berasal dari flash yang DITULIS SERVER pada redirect store(),
     * dan tetap dinormalisasi terhadap enum MODES — jadi sesi yang berisi nilai
     * asing tidak pernah diteruskan ke tombol pilihan mode (PRD 04 §8).
     */
    private function activeModeFor(Request $request): string
    {
        $flashed = $request->session()->get(SchoolAttendanceScanService::SESSION_ACTIVE_MODE_KEY);

        return in_array($flashed, SchoolAttendanceScanService::MODES, true)
            ? $flashed
            : SchoolAttendanceScanService::MODE_MASUK;
    }

    /**
     * POST /guru/school-attendance-scanner — proses satu scan.
     *
     * Respons selalu redirect kembali ke halaman scanner dengan pesan flash
     * (bukan JSON, bukan raw exception): scanner dipakai pada HP guru melalui
     * browser dan bentuk form + kamera adalah alur resminya (PRD 01 §13.2).
     */
    public function store(SchoolAttendanceScanRequest $request): RedirectResponse|Response
    {
        $mode = $request->scanInput()['mode'];

        try {
            // Service MEMANGGIL ULANG revalidasi jadwal -> 403 bila jadwal
            // dicabut/dipindah di tengah sesi (ADDENDUM §13), dan menolak
            // mode/barcode yang tidak sah.
            $attendance = $this->scanner->scan(
                $request,
                $request->user(),
                $request->scanInput()['barcode'],
                $mode,
            );
        } catch (AccessDeniedHttpException $e) {
            // Halaman scanner DIKUNCI dengan panel terkunci + teks persis dari
            // PRD (bukan halaman galat generik framework) — PRD 04 §3.2.4/§16.5,
            // PRD 01 §GUR-PIK-007. Sengaja tidak memakai $e->getMessage() mentah
            // ke view tanpa kontrol: pesan service sudah merupakan teks UI.
            return response()
                ->view('guru.school-attendance-scanner.locked', [
                    'message' => $e->getMessage(),
                    'serverDate' => $this->scanner->serverDate(),
                    'teacherName' => $request->user()?->teacher?->full_name ?? $request->user()?->username,
                ], Response::HTTP_FORBIDDEN);
        }

        // Mode aktif dipertahankan antar-scan tanpa logout (ADDENDUM §7).
        return redirect()
            ->route('guru.school-attendance-scanner.index')
            ->with('scan_status', $this->successMessage($attendance))
            ->with(SchoolAttendanceScanService::SESSION_ACTIVE_MODE_KEY, $mode);
    }

    /**
     * Pesan sukses sesuai mode terakhir yang tercatat pada baris ini.
     * Nama siswa TIDAK ditempel mentah ke flash string tanpa escaping view —
     * Blade {{ }} meng-escape-nya saat dirender (PRD 04 §8).
     */
    private function successMessage(SchoolAttendance $attendance): string
    {
        $student = $attendance->student;

        if ($attendance->scan_mode_out === SchoolAttendanceScanService::MODE_PULANG) {
            return 'PULANG tercatat untuk ' . ($student?->full_name ?? 'siswa')
                . ' pukul ' . $attendance->check_out_time?->format('H:i') . '.';
        }

        return 'MASUK tercatat untuk ' . ($student?->full_name ?? 'siswa')
            . ' pukul ' . $attendance->check_in_time?->format('H:i') . '.';
    }

    /**
     * Guru terverifikasi — SENGAJA dinilai ulang terhadap database dan tidak
     * pernah memakai attribute hasil middleware sebagai sumber otoritas.
     * Dengan begini, tidak ada satu pun jalur (termasuk rute yang dipasang
     * tanpa EnsureTeacherOnDuty) yang bisa merender panel scanner tanpa jadwal
     * piket yang sah pada tanggal server (PRD 04 §3.2.4).
     */
    private function teacherFor(Request $request): Teacher
    {
        return $this->scanner->authorizeOrDeny($request, $request->user());
    }

    /**
     * Recak scan terakhir oleh guru ini pada tanggal berjalan (UX saja).
     *
     * Dibatasi tanggal server + operator = guru sesi login, jadi guru tidak
     * diberi daftar absensi siswa secara umum (bukan kewenangannya).
     *
     * @return array<int, array<string, mixed>>
     */
    private function lastScanFor(Teacher $teacher): array
    {
        return SchoolAttendance::query()
            ->with('student:id,full_name,nis')
            ->whereDate('attendance_date', $this->scanner->serverDate())
            ->where(fn ($query) => $query
                ->where('check_in_by_teacher_id', $teacher->getKey())
                ->orWhere('check_out_by_teacher_id', $teacher->getKey()))
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (SchoolAttendance $row) => [
                'nama' => $row->student?->full_name ?? '(siswa tidak ditemukan)',
                'nis' => $row->student?->nis,
                'masuk' => $row->check_in_time?->format('H:i'),
                'pulang' => $row->check_out_time?->format('H:i'),
                'dikoreksi' => (bool) $row->is_corrected,
            ])
            ->all();
    }
}
