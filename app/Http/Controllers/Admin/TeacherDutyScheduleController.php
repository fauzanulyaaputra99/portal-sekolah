<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DutyScheduleRequest;
use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Services\TeacherDutyScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * CRUD Jadwal Guru Piket untuk area Admin/TU (PRD 02 §6 rute
 * /admin/duty-schedules, PRD 01 §6.11 ADM-PIK-001/002, ADDENDUM §9).
 *
 * Pembagian tanggung jawab:
 * - Controller: validasi request, otorisasi Gate/Policy, panggil service,
 *   redirect + pesan UI. TIDAK ada business logic transaksi di sini.
 * - Service: transaksi database, validasi otoritatif guru, audit trail.
 *
 * Lapisan keamanan (PRD 04 §3.2):
 * 1. Middleware group `auth` + `role:admin` (routes/web.php) — menolak
 *    guru/supervisor pada SEMUA rute ini dengan HTTP 403, termasuk akses URL
 *    langsung. Blade/menu bukan kontrol keamanan.
 * 2. Gate/policy per objek pada create/edit/update/destroy — anti-IDOR;
 *    objek dimuat lewat route model binding dari database, bukan dari klien.
 * 3. CSRF oleh middleware `web` + @csrf pada seluruh form.
 *
 * TIDAK ADA: role "piket", users.is_piket, nama guru hardcoded, nama hari
 * hardcoded. Seluruhnya dilarang ADDENDUM §8/§10 dan PRD 01 §13.8.
 */
class TeacherDutyScheduleController extends Controller
{
    /** Jumlah baris per halaman pada daftar jadwal (nilai tampilan biasa). */
    private const PAGINATE_PER_PAGE = 15;

    public function __construct(
        private readonly TeacherDutyScheduleService $service,
    ) {}

    /**
     * Daftar jadwal piket (index) + penanda "tanggal berjalan" server.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TeacherDutySchedule::class);

        $schedules = TeacherDutySchedule::query()
            ->with(['teacher', 'createdByUser'])
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate('schedule_date', (string) $request->input('date'))
            )
            ->when(
                $request->filled('teacher_id'),
                fn ($query) => $query->where('teacher_id', (int) $request->input('teacher_id'))
            )
            ->orderByDesc('schedule_date')
            ->orderBy('id')
            ->paginate(self::PAGINATE_PER_PAGE)
            ->withQueryString();

        return view('admin.duty-schedules.index', [
            'schedules' => $schedules,
            'teachers' => $this->teacherOptions(),
            'today' => $this->service->currentDutyDate(),
            'filters' => [
                'date' => (string) $request->input('date', ''),
                'teacher_id' => $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null,
            ],
        ]);
    }

    /**
     * Form tambah jadwal.
     */
    public function create(): View
    {
        $this->authorize('create', TeacherDutySchedule::class);

        return view('admin.duty-schedules.create', [
            'teachers' => $this->teacherOptions(),
            'today' => $this->service->currentDutyDate(),
        ]);
    }

    /**
     * Simpan jadwal baru.
     */
    public function store(DutyScheduleRequest $request): RedirectResponse
    {
        $this->authorize('create', TeacherDutySchedule::class);

        $schedule = $this->service->create($request->validated(), $request->user(), $request);

        return redirect()
            ->route('admin.duty-schedules.index')
            ->with('status', 'Jadwal piket '
                . ($schedule->teacher?->full_name ?? 'Guru')
                . ' pada ' . $schedule->schedule_date->format('d-m-Y') . ' berhasil dibuat.');
    }

    /**
     * Form ubah jadwal (ganti guru / ganti tanggal).
     */
    public function edit(TeacherDutySchedule $dutySchedule): View
    {
        $this->authorize('update', $dutySchedule);

        return view('admin.duty-schedules.edit', [
            'schedule' => $dutySchedule->loadMissing('teacher'),
            'teachers' => $this->teacherOptions(),
            'today' => $this->service->currentDutyDate(),
        ]);
    }

    /**
     * Simpan perubahan jadwal.
     */
    public function update(DutyScheduleRequest $request, TeacherDutySchedule $dutySchedule): RedirectResponse
    {
        $this->authorize('update', $dutySchedule);

        $schedule = $this->service->update($dutySchedule, $request->validated(), $request->user(), $request);

        return redirect()
            ->route('admin.duty-schedules.index')
            ->with('status', 'Jadwal piket berhasil diperbarui menjadi tanggal '
                . $schedule->schedule_date->format('d-m-Y') . ' — '
                . ($schedule->teacher?->full_name ?? 'Guru') . '.');
    }

    /**
     * Hapus jadwal.
     */
    public function destroy(Request $request, TeacherDutySchedule $dutySchedule): RedirectResponse
    {
        $this->authorize('delete', $dutySchedule);

        $date = $dutySchedule->schedule_date->format('d-m-Y');
        $this->service->delete($dutySchedule, $request->user(), $request);

        return redirect()
            ->route('admin.duty-schedules.index')
            ->with('status', "Jadwal piket tanggal {$date} berhasil dihapus.");
    }

    /**
     * Opsi Guru yang diambil DARI DATABASE (profil guru dengan akun aktif),
     * dijamin tidak ada daftar guru hardcoded (PRD 04 §8).
     *
     * Sengaja TIDAK ada filter nama hari/nama guru: jadwal piket adalah data,
     * bukan aturan kode (ADDENDUM §10).
     *
     * @return Collection<int, Teacher>
     */
    private function teacherOptions(): Collection
    {
        return Teacher::query()
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nip']);
    }
}
