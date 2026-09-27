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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('restrict');
            $table->string('nip', 30)->nullable()->unique();
            $table->string('full_name', 100);
            $table->string('gender', 1); // L / P
            $table->string('phone_number', 20)->nullable();
            $table->string('employment_status', 30)->default('GTY'); // PNS, PPPK, GTY, GTT, HONORER
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
