<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekening tujuan refund klien dan waktu transfer refund (Tahap B3).
     */
    public function up(): void
    {
        Schema::table('cancellations', function (Blueprint $table) {
            $table->string('refund_bank_name')->nullable()->after('decision_reason');
            $table->string('refund_account_number')->nullable()->after('refund_bank_name');
            $table->string('refund_account_name')->nullable()->after('refund_account_number');
            $table->timestamp('refund_paid_at')->nullable()->after('refund_account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cancellations', function (Blueprint $table) {
            $table->dropColumn(['refund_bank_name', 'refund_account_number', 'refund_account_name', 'refund_paid_at']);
        });
    }
};
