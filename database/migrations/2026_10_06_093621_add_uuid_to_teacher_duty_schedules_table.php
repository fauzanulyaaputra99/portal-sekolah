<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_duty_schedules', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->unique()->after('id');
        });

        // Backfill UUID untuk data existing akan dilakukan via seeder/script terpisah
        // atau di-handle oleh Model boot() saat update berikutnya.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_duty_schedules', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
