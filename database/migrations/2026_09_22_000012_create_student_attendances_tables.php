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
        Schema::create('student_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->onDelete('restrict');
            $table->date('session_date');
            $table->unsignedSmallInteger('meeting_order')->default(1);
            $table->string('status', 10)->default('OPEN'); // OPEN, CLOSED
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('opened_by_teacher_id')->constrained('teachers')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['teaching_assignment_id', 'session_date', 'meeting_order'], 'uq_student_att_session_slot');
            $table->index(['teaching_assignment_id', 'session_date'], 'idx_student_att_sessions_lookup');
        });

        Schema::create('student_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('student_attendance_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('restrict');
            $table->string('status', 10)->default('HADIR'); // HADIR, IZIN, SAKIT, ALPA
            $table->string('notes', 255)->nullable();
            $table->boolean('is_corrected')->default(false);
            $table->foreignId('corrected_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('correction_reason')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id'], 'uq_student_att_records_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendance_records');
        Schema::dropIfExists('student_attendance_sessions');
    }
};
