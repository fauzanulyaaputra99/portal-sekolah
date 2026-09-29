<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SchoolAttendanceScanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang otorisasi scanner: hanya Guru yang TERJADWAL PIKET pada tanggal
 * server yang boleh membuka/memakai scanner (PRD 04 §3.2.4, §16.5;
 * ADDENDUM §11–§14; PRD 01 §7.4 GUR-PIK-001 & AC-SCAN-05).
 *
 * Rangkaian middleware pada rute scanner:
 *   auth  ->  role:teacher  ->  EnsureUserIsActive (global web)  ->  **ini**
 *
 * - `role:teacher` menolak admin & supervisor lebih dulu (HTTP 403) karena
 *   scanner BUKAN kewenangan area mereka (PRD 01 §5.4).
 * - Middleware ini menambahkan syarat KEDUA yang berbasis DATA: baris pada
 *   `teacher_duty_schedules` untuk `teacher_id` sesi login pada TANGGAL SERVER.
 *   Status "Guru Piket" bukan role dan bukan flag user (ADDENDUM §8/§10).
 *
 * Revalidasi: kelas ini dieksekusi pada SETIAP request — GET (buka halaman)
 * maupun POST (submit scan) — tanpa cache, sehingga halaman scanner yang
 * tetap terbuka setelah jadwal dicabut tidak dapat lagi dipakai
 * (ADDENDUM §12–§14).
 *
 * Respons penolakan = HTTP **403** dengan body view "fitur terkunci"
 * (PRD 01 §GUR-PIK-001) yang memuat teks persis dari PRD:
 *   "Anda hari ini bukan Guru Piket."   (akses awal, guru non-piket)
 *   "Jadwal piket Anda telah berubah. ..." (jadwal dicabut saat halaman terbuka)
 * Sengaja TIDAK memakai halaman galat generik: teks terkunci adalah bagian
 * dari spesifikasi UI, dan body eksplisit tidak bergantung pada APP_DEBUG
 * sehingga tidak berisiko membocorkan detail internal (PRD 04 §10.1).
 */
class EnsureTeacherOnDuty
{
    public function __construct(
        private readonly SchoolAttendanceScanService $scanner,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        // auth sudah menjamin user login; tetap gagal tertutup bila tidak.
        if ($user === null) {
            return $this->lockedResponse($request, SchoolAttendanceScanService::MESSAGE_NOT_ON_DUTY);
        }

        // Penjaga ganda (defense in depth): middleware `role:teacher` sudah
        // menegakkan role dari kolom users.role di sesi server. Kalau sampai
        // rute baru salah dipasang, role non-guru tetap tidak diberi akses.
        if (! $user->isTeacher()) {
            return $this->lockedResponse($request, SchoolAttendanceScanService::MESSAGE_NOT_ON_DUTY);
        }

        $teacher = $this->scanner->findOnDutyTeacher($request, $user);

        if ($teacher === null) {
            return $this->lockedResponse($request, $this->scanner->denialMessage($request, $user));
        }

        // Guru terverifikasi bertugas. Data ini dikirim ke view HANYA untuk
        // tampilan; service memvalidasi ulang pada tiap pemanggilan scan().
        $request->attributes->set('scanner_teacher', $teacher);

        return $next($request);
    }

    private function lockedResponse(Request $request, string $message): Response
    {
        return response()
            ->view('guru.school-attendance-scanner.locked', [
                'message' => $message,
                'serverDate' => $this->scanner->serverDate(),
                'teacherName' => $request->user()?->teacher?->full_name ?? $request->user()?->username,
            ], Response::HTTP_FORBIDDEN);
    }
}
