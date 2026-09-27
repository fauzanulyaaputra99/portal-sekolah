<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel teacher_duty_schedules (PRD 02 §10.15b — ADDENDUM §8–§18).
     *
     * Jadwal piket guru yang dikelola sepenuhnya oleh Admin/TU (CRUD + audit).
     * Status "Guru Piket hari ini" SELALU ditentukan dari tabel ini pada
     * tanggal berjalan (tanggal server, Asia/Jakarta) — BUKAN dari role,
     * BUKAN dari kolom users.is_piket, BUKAN dari hardcode hari/nama.
     *
     * Integritas:
     * - UNIQUE (schedule_date, teacher_id) -> uq_duty_schedule_entry:
     *   mencegah duplikasi jadwal guru pada tanggal sama.
     * - INDEX (schedule_date, teacher_id) -> idx_teacher_duty_schedules_date
     *   (§17.2): mendukung validasi otorisasi scanner per request.
     * - ON DELETE RESTRICT pada guru menjaga integritas penjadwalan;
     *   created_by_user_id RESTRICT mengikuti pola uploaded_by_user_id
     *   pada document_versions (penanda akuntabilitas pembuat jadwal).
     */
    public function up(): void
    {
        Schema::create('teacher_duty_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('schedule_date');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('restrict');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['schedule_date', 'teacher_id'], 'uq_duty_schedule_entry');
            $table->index(['schedule_date', 'teacher_id'], 'idx_teacher_duty_schedules_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_duty_schedules');
    }
};
