<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-3 (B14): keputusan verifikasi mitra mencatat alasan/catatan terkini,
 * reviewer terakhir, dan waktu pengajuan ulang. Riwayat lengkap tetap di
 * audit_logs; kolom ini menyimpan status terkini untuk UI penyedia/Admin.
 * Additive dan nullable; tidak mengubah alur T2 (status akun terpisah).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['vendor_profiles', 'venue_profiles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->text('verification_note')->nullable()->after('verified_at');
                $table->foreignId('reviewed_by')->nullable()->after('verification_note')
                    ->constrained('users')->nullOnDelete();
                $table->timestamp('verification_submitted_at')->nullable()->after('reviewed_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['vendor_profiles', 'venue_profiles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('reviewed_by');
                $table->dropColumn(['verification_note', 'verification_submitted_at']);
            });
        }
    }
};
