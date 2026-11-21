<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: keputusan final Admin atas permintaan penawaran. Unique
 * quote_request_id menjaga satu keputusan final per permintaan (proposal
 * integritas teknis C.1). approved_amount hanya terisi jika disetujui;
 * alasan wajib saat menolak ditegakkan Form Request pada tahap alur.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quote_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->unique()->constrained();
            $table->foreignId('admin_id')->constrained('users');
            $table->string('decision');
            $table->unsignedBigInteger('approved_amount')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_decisions');
    }
};
