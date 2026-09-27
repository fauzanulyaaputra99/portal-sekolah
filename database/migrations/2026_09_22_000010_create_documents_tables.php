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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('restrict');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('restrict');
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->string('category', 20); // RPP, ATP, PROTA, PROSEM, LAINNYA
            $table->string('title', 150);
            $table->string('status', 20)->default('DRAFT'); // DRAFT, DIKIRIM, DIREVIEW, PERLU_REVISI, DISETUJUI
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'academic_year_id', 'status'], 'idx_documents_teacher_status');
            $table->index('status', 'idx_documents_review_queue');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('gdrive_file_id', 100);
            $table->string('original_filename', 255);
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('mime_type', 100);
            $table->string('file_hash_sha256', 64);
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('restrict');
            $table->text('uploader_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['document_id', 'version_number'], 'uq_document_versions_num');
            $table->index(['document_id', 'version_number'], 'idx_document_versions_doc_id');
        });

        // Add foreign key constraint for current_version_id after document_versions table exists
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('document_versions')->onDelete('set null');
        });

        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained('document_versions')->onDelete('cascade');
            $table->foreignId('reviewer_user_id')->constrained('users')->onDelete('restrict');
            $table->string('action', 20); // SETUJUI, MINTA_REVISI
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained('document_versions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->text('comment_text');
            $table->unsignedInteger('page_number')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_comments');
        Schema::dropIfExists('document_reviews');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
