<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus tabel teacher_attendances.
     *
     * Fitur absensi mandiri guru DIHAPUS TOTAL dari Portal Sekolah
     * (PRD FINAL — ADDENDUM §20 & §26, ADR 01/02/03 berstatus OBSOLETE).
     *
     * Keamanan data: tabel terverifikasi 0 baris, 0 FK anak yang mereferensi,
     * 0 jejak audit. Migration lama pembuat tabel TIDAK diubah (riwayat).
     * Schema::dropIfExists dipakai agar aman dijalankan pada database existing
     * maupun database fresh.
     */
    public function up(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }

    /**
     * Restore struktur tabel (SKEMA SAJA — data lama tidak dikembalikan;
     * data memang 0 baris saat penghapusan).
     */
    public function down(): void
    {
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('restrict');
            $table->date('attendance_date');
            $table->timestamp('check_in_time')->nullable();
            $table->string('status', 20);
            $table->string('ip_address', 45)->nullable();
            $table->text('device_info')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_corrected')->default(false);
            $table->foreignId('corrected_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('correction_reason')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'attendance_date'], 'uq_teacher_attendances_entry');
            $table->index(['attendance_date', 'status'], 'idx_teacher_attendances_date_status');
        });
    }
};
