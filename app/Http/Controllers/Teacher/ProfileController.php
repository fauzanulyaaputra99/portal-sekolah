<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Controller profil guru (PRD 01 §7.2 GUR-PRF-001–002).
 *
 * Scope:
 * - GUR-PRF-001: Lihat biodata pribadi + riwayat penugasan mengajar
 * - GUR-PRF-002: Ubah kata sandi mandiri + nomor kontak
 *
 * Keamanan:
 * - Hanya role teacher yang dapat mengakses (middleware role:teacher)
 * - Guru hanya bisa melihat/mengedit profil sendiri (anti-IDOR)
 * - Password lama wajib diverifikasi sebelum ganti password
 */
class ProfileController extends Controller
{
    /**
     * Tampilkan profil guru (GUR-PRF-001).
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Defense-in-depth: pastikan user adalah teacher (PRD 04 §3.2.3)
        if (!$user->isTeacher()) {
            abort(403, 'Akses ditolak. Hanya guru yang dapat mengakses profil.');
        }

        $teacher = $user->teacher;

        if (!$teacher) {
            return redirect()
                ->route('guru.dashboard')
                ->with('error', 'Profil guru belum tersedia.');
        }

        // Load relasi untuk riwayat penugasan
        $teacher->load([
            'teachingAssignments' => function ($query) {
                $query->with(['subject', 'classRoom.gradeLevel', 'academicYear'])
                    ->latest('created_at');
            },
        ]);

        return view('guru.profile.show', [
            'user' => $user,
            'teacher' => $teacher,
        ]);
    }

    /**
     * Form edit profil (nomor kontak) (GUR-PRF-002).
     */
    public function edit(Request $request)
    {
        $user = $request->user();

        // Defense-in-depth: pastikan user adalah teacher (PRD 04 §3.2.3)
        if (!$user->isTeacher()) {
            abort(403, 'Akses ditolak.');
        }

        $teacher = $user->teacher;

        if (!$teacher) {
            return redirect()
                ->route('guru.dashboard')
                ->with('error', 'Profil guru belum tersedia.');
        }

        return view('guru.profile.edit', [
            'user' => $user,
            'teacher' => $teacher,
        ]);
    }

    /**
     * Update nomor kontak guru (GUR-PRF-002).
     */
    public function updateContact(Request $request)
    {
        $user = $request->user();

        // Defense-in-depth: pastikan user adalah teacher (PRD 04 §3.2.3)
        if (!$user->isTeacher()) {
            abort(403, 'Akses ditolak.');
        }

        $teacher = $user->teacher;

        if (!$teacher) {
            return back()->with('error', 'Profil guru tidak ditemukan.');
        }

        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\-\+\(\)]+$/'],
        ], [
            'phone_number.max' => 'Nomor telepon maksimal 20 karakter.',
            'phone_number.regex' => 'Format nomor telepon tidak valid.',
        ]);

        $teacher->update([
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        return redirect()
            ->route('guru.profile.show')
            ->with('success', 'Nomor kontak berhasil diperbarui.');
    }

    /**
     * Form ubah kata sandi (GUR-PRF-002).
     */
    public function editPassword(Request $request)
    {
        // Defense-in-depth: pastikan user adalah teacher (PRD 04 §3.2.3)
        if (!$request->user()->isTeacher()) {
            abort(403, 'Akses ditolak.');
        }

        return view('guru.profile.password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update kata sandi (GUR-PRF-002).
     *
     * Wajib verifikasi password lama sebelum mengganti.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // Defense-in-depth: pastikan user adalah teacher (PRD 04 §3.2.3)
        if (!$user->isTeacher()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.mixed_case' => 'Kata sandi harus mengandung huruf besar dan kecil.',
            'password.numbers' => 'Kata sandi harus mengandung angka.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('guru.profile.show')
            ->with('success', 'Kata sandi berhasil diubah.');
    }
}
