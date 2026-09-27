<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom students.barcode_code (PRD 02 §10.6 — ADDENDUM).
     *
     * Identifier barcode visual (1D/2D) yang tercetak pada kartu fisik siswa.
     * BUKAN RFID/NFC/chip. Sumber pencocokan utama scanner absensi
     * masuk/pulang sekolah.
     *
     * Catatan: PRD menetapkan kolom NOT NULL, namun karena tabel `students`
     * sudah berisi data existing (siswa demo tanpa kartu), kolom dibuat
     * NULLABLE dengan UNIQUE (MySQL mengizinkan banyak NULL pada unique index)
     * demi compatibility — sesuai keputusan Adjustment A. Tidak ada nilai
     * default/format yang dipaksakan di level database.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('barcode_code', 50)
                ->nullable()
                ->unique('students_barcode_code_unique')
                ->after('nisn');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_barcode_code_unique');
            $table->dropColumn('barcode_code');
        });
    }
};
