<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Menambahkan kolom `uuid` (CHAR 36) ke tabel student_attendance_sessions
 * sebagai route key publik yang aman — menggantikan auto-increment ID di URL.
 *
 * Dasar: PRD 04 §3.2.3 (anti-IDOR), PRD 07 §11.2 checklist keamanan.
 * PK tetap BIGINT UNSIGNED AUTO_INCREMENT sesuai ADR 15.
 * Kolom uuid bersifat UNIQUE + INDEX untuk lookup cepat via route model binding.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendance_sessions', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->unique()->after('id');
        });

        // Backfill UUID untuk data yang sudah ada
        DB::table('student_attendance_sessions')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunk(100, function ($sessions) {
                foreach ($sessions as $session) {
                    DB::table('student_attendance_sessions')
                        ->where('id', $session->id)
                        ->update(['uuid' => Str::uuid()->toString()]);
                }
            });

        // Setelah backfill, jadikan NOT NULL
        Schema::table('student_attendance_sessions', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_attendance_sessions', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
