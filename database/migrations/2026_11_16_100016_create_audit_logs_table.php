<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: jejak audit aksi penting (aktor, subjek, status lama/baru, alasan).
 * actor_id nullable untuk aksi sistem. Tidak menyimpan bukti file atau
 * nomor rekening utuh (BACKEND_DATABASE_DESIGN bagian audit_logs).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
