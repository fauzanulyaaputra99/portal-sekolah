<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel student_school_attendances (PRD 02 §10.15 — ADDENDUM).
     *
     * Absensi Masuk/Pulang SEKOLAH siswa, dicatat melalui scanner barcode
     * kamera HP oleh Guru Piket (operator = guru terjadwal pada
     * teacher_duty_schedules hari berjalan; BUKAN role baru).
     *
     * Aturan integritas yang ikut dibangun di sini:
     * - UNIQUE (student_id, attendance_date): satu catatan kehadiran sekolah
     *   per siswa per hari — pencegahan duplikasi di level DATABASE,
     *   melengkapi validasi application layer (SchoolAttendanceScanService
     *   dibangun pada tahap adjustment berikutnya).
     * - ON DELETE RESTRICT pada student & guru operator: melindungi histori
     *   absensi dari penghapusan data master (PRD 02 §18.1).
     * - CHECK scan_mode_in IN ('MASUK') dan scan_mode_out IN ('PULANG'):
     *   mode scanner EKSPISIT (ADR 18), nilai lain ditolak level DB.
     * - Stempel waktu server (CURRENT_TIMESTAMP), bukan waktu perangkat.
     */
    public function up(): void
    {
        Schema::create('student_school_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('restrict');
            $table->date('attendance_date');
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->foreignId('check_in_by_teacher_id')->nullable()
                ->constrained('teachers')->onDelete('restrict');
            $table->foreignId('check_out_by_teacher_id')->nullable()
                ->constrained('teachers')->onDelete('restrict');
            $table->string('scan_mode_in', 10)->nullable();
            $table->string('scan_mode_out', 10)->nullable();
            $table->boolean('is_corrected')->default(false);
            $table->foreignId('corrected_by_user_id')->nullable()
                ->constrained('users')->onDelete('set null');
            $table->text('correction_reason')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['student_id', 'attendance_date'], 'uq_student_school_att_entry');
            $table->index(['attendance_date', 'student_id'], 'idx_student_school_att_date');
        });

        // CHECK constraints (MySQL 8.0.16+), PRD 02 §10.15 & §8.1.
        DB::statement("ALTER TABLE student_school_attendances
            ADD CONSTRAINT chk_student_school_att_scan_mode_in CHECK (scan_mode_in IS NULL OR scan_mode_in = 'MASUK')");

        DB::statement("ALTER TABLE student_school_attendances
            ADD CONSTRAINT chk_student_school_att_scan_mode_out CHECK (scan_mode_out IS NULL OR scan_mode_out = 'PULANG')");
    }

    public function down(): void
    {
        Schema::dropIfExists('student_school_attendances');
    }
};
