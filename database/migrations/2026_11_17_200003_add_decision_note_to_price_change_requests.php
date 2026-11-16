<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-3b: catatan keputusan Admin pada pengajuan harga (paralel
 * verification_note B14). Wajib saat menolak; disimpan agar penyedia
 * dapat melihat alasan keputusan pada riwayat pengajuannya.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_change_requests', function (Blueprint $table) {
            $table->text('decision_note')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_change_requests', function (Blueprint $table) {
            $table->dropColumn('decision_note');
        });
    }
};
