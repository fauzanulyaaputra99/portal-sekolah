<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolAttendanceCorrectionRequest;
use App\Models\SchoolAttendance;
use App\Models\Student;
use App\Models\TeacherDutySchedule;
use App\Services\AttendanceCorrectionService;
use App\Services\SchoolAttendanceScanService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Monitoring & koreksi Absensi Masuk/Pulang Siswa (PRD 02 §6 rute
 * /admin/school-attendances "Rekap absensi masuk/pulang siswa", PRD 01 §6.9
 * ADM-ABS-001 + ADM-ABS-003, §13.7, §16.1, PRD 04 §9.1 `SCHOOL_ATT_CORRECT`).
 *
 * Hak akses (PRD 01 §5.4):
 * - Admin/TU   : BACA + KOREKSI (koreksi wajib alasan min. 10 karakter).
 * - Supervisor : BACA saja — jalur koreksi ditolak Policy + middleware role.
 * - Guru       : TIDAK ADA akses ke area ini (termasuk Guru Piket operator
 *                scan; §13.7 "Guru Piket tidak dapat mengoreksi").
 *
 * Filter (tanggal/status) hanyalah pemfilter data, BUKAN kontrol akses
 * (PRD 04 §2): otorisasi tetap ditegakkan middleware `role` + Policy.
 *
 * Rekap "belum masuk" memakai daftar siswa AKTIF sebagai basis (PRD 01 §16.1
 * "Total Masuk / Total Siswa Aktif"), bukan daftar siswa yang punya record —
 * supaya siswa tanpa scan hari ini tetap terlihat.
 */
class SchoolAttendanceController extends Controller
{
    /** Nama status turunan untuk filter & tampilan (tidak disimpan di DB). */
    public const STATUS_MASUK = 'MASUK';

    public const STATUS_PULANG = 'SUDAH_PULANG';

    public const STATUS_BELUM = 'BELUM_MASUK';

    /** Baris per halaman daftar rekap (nilai tampilan biasa). */
    private const PAGINATE_PER_PAGE = 20;

    public function __construct(
        private readonly SchoolAttendanceScanService $scanner,
        private readonly AttendanceCorrectionService $corrections,
    ) {}

    /**
     * GET /admin/school-attendances — rekap harian + filter.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SchoolAttendance::class);

        // Filter tanggal diterima HANYA bila bentuknya kanonik Y-m-d; nilai
        // aneh tidak diteruskan ke query (tidak ada raw SQL, tetap bindings).
        $date = $this->resolveFilterDate((string) $request->input('date', ''));

        $status = $this->resolveStatusFilter((string) $request->input('status', ''));

        $records = SchoolAttendance::query()
            ->with(['student:id,full_name,nis,status', 'checkInByTeacher:id,full_name', 'checkOutByTeacher:id,full_name'])
            ->when(
                $request->filled('student_id'),
                fn ($query) => $query->where('student_id', (int) $request->input('student_id'))
            )
            // Status kehadiran TIDAK disimpan sebagai kolom — diturunkan dari
            // ada/tidaknya jam masuk & pulang (PRD 02 §10.15). Nilainya sudah
            // diputihkan resolveStatusFilter(), jadi query dibangun dengan
            // kondisi builder biasa (TANPA raw SQL) dan nilai tak dikenal tidak
            // pernah memengaruhi SQL.
            ->when($status === self::STATUS_PULANG, fn ($query) => $query->whereNotNull('check_out_time'))
            ->when($status === self::STATUS_MASUK, function ($query): void {
                // Sudah masuk tetapi belum pulang.
                $query->whereNotNull('check_in_time')->whereNull('check_out_time');
            })
            ->when($status === self::STATUS_BELUM, function ($query): void {
                // Baris hari ini yang jam masuknya kosong (mis. hasil koreksi
                // yang mengosongkan MASUK). Siswa yang belum punya baris sama
                // sekali dihitung pada statistik "belum masuk", bukan di sini.
                $query->whereNull('check_in_time');
            })
            ->whereDate('attendance_date', $date)
            ->orderBy('student_id')
            ->paginate(self::PAGINATE_PER_PAGE)
            ->withQueryString();

        return view('admin.school-attendances.index', [
            'records' => $records,
            'date' => $date,
            'today' => $this->scanner->serverDate(),
            'stats' => $this->stats($date),
            'filters' => [
                'date' => $date,
                'status' => $status,
                'student_id' => $request->filled('student_id') ? (int) $request->input('student_id') : null,
            ],
            'guruPiket' => TeacherDutySchedule::query()
                ->with('teacher:id,full_name,nip')
                ->whereDate('schedule_date', $date)
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * GET /admin/school-attendances/{schoolAttendance}/correct — form koreksi.
     */
    public function edit(SchoolAttendance $schoolAttendance): View
    {
        $this->authorize('correct', $schoolAttendance);

        return view('admin.school-attendances.correct', [
            'attendance' => $schoolAttendance->load([
                'student:id,full_name,nis,status',
                'checkInByTeacher:id,full_name',
                'checkOutByTeacher:id,full_name',
                'correctedByUser:id,username',
            ]),
            'minReason' => AttendanceCorrectionService::MIN_REASON_LENGTH,
        ]);
    }

    /**
     * PATCH /admin/school-attendances/{schoolAttendance} — simpan koreksi.
     *
     * Aksi = "SCHOOL_ATT_CORRECT" (PRD 04 §9.1); actor & waktu diambil service
     * dari sesi login + clock server, bukan dari payload.
     */
    public function update(
        SchoolAttendanceCorrectionRequest $request,
        SchoolAttendance $schoolAttendance,
    ): RedirectResponse {
        $this->authorize('correct', $schoolAttendance);

        $attendance = $this->corrections->correct(
            $schoolAttendance,
            $request->validated(),
            $request->user(),
            $request,
        );

        return redirect()
            ->route('admin.school-attendances.index', ['date' => $attendance->attendance_date?->toDateString()])
            ->with('status', 'Koreksi absensi ' . ($attendance->student?->full_name ?? 'siswa')
                . ' tanggal ' . $attendance->attendance_date?->format('d/m/Y') . ' tersimpan dan tercatat di audit log.');
    }

    // -----------------------------------------------------------------
    // Rekap
    // -----------------------------------------------------------------

    /**
     * Ringkasan harian (PRD 01 §16.1: jumlah masuk / belum masuk / sudah pulang).
     *
     * @return array{aktif: int, tercatat: int, masuk: int, pulang: int, belum: int}
     */
    private function stats(string $date): array
    {
        $rows = SchoolAttendance::query()
            ->whereDate('attendance_date', $date)
            ->get(['check_in_time', 'check_out_time']);

        $aktif = Student::query()->where('status', 'AKTIF')->count();

        return [
            'aktif' => $aktif,
            'tercatat' => $rows->count(),
            'masuk' => $rows->whereNotNull('check_in_time')->count(),
            'pulang' => $rows->whereNotNull('check_out_time')->count(),
            'belum' => max(0, $aktif - $rows->whereNotNull('check_in_time')->count()),
        ];
    }

    /**
     * Tanggal filter yang SAF: harus kanonik Y-m-d dan benar-benar ada di
     * kalender; selain itu jatuh ke tanggal server hari ini.
     */
    private function resolveFilterDate(string $value): string
    {
        if ($value === '') {
            return $this->scanner->serverDate();
        }

        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return $this->scanner->serverDate();
        }

        $valid = $parsed->toDateString() === $value
            && checkdate((int) $parsed->month, (int) $parsed->day, (int) $parsed->year);

        return $valid ? $value : $this->scanner->serverDate();
    }

    /**
     * Nilai filter status dipulihkan HANYA bila termasuk tiga nama yang
     * dikenal; selain itu dianggap "tanpa filter" (string kosong).
     */
    private function resolveStatusFilter(string $value): string
    {
        return in_array($value, [self::STATUS_MASUK, self::STATUS_PULANG, self::STATUS_BELUM], true)
            ? $value
            : '';
    }
}
