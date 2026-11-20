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
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quote_request_id');
            $table->dropColumn(['proposed_price_snapshot']);
        });

        Schema::dropIfExists('quote_decisions');
        Schema::dropIfExists('quote_proposals');
        Schema::dropIfExists('quote_requests');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Alur penawaran sengaja dihapus permanen sesuai dokumen rancangan EMS.
    }
};
