<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap G1: jejak keputusan Admin atas rekening pencairan penyedia.
 * Additive: kolom lama tidak diubah. Index gabungan mempercepat
 * pencarian rekening per profil dan status (antrean verifikasi rekening).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('provider_bank_accounts', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('verification_status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');

            $table->index(['owner_type', 'owner_id', 'verification_status'], 'provider_bank_accounts_owner_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_bank_accounts', function (Blueprint $table) {
            $table->dropIndex('provider_bank_accounts_owner_status_index');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'rejection_reason']);
        });
    }
};
