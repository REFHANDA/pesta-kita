<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update events
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('budget')->nullable()->after('guest_count');
        });

        DB::table('events')->where('status', 'dinilai')->update(['status' => 'dievaluasi']);

        // 2. Update order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->date('usage_date')->nullable()->after('event_id');
            $table->unsignedInteger('quantity')->default(1)->after('usage_date');
            $table->unsignedBigInteger('provider_price')->nullable()->after('catalog_price_snapshot');
            $table->unsignedBigInteger('commission')->default(0)->after('provider_price');
            $table->text('admin_note')->nullable()->after('rejection_reason');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('review_due_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('replaced_by_order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->string('hold_key')->nullable()->unique();
        });

        DB::table('order_items')->whereNotNull('final_price')->update([
            'provider_price' => DB::raw('final_price'),
        ]);

        DB::table('order_items')->where('status', 'menunggu_admin')->update([
            'status' => 'menunggu_review',
        ]);

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('final_price');
        });

        // 3. Update order_objections
        Schema::table('order_objections', function (Blueprint $table) {
            $table->string('category')->nullable()->after('provider_user_id');
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_emergency')->default(false)->after('evidence_media_id');
            $table->text('decision_reason')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // 4. Update company_bank_accounts
        Schema::table('company_bank_accounts', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('order_objections', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['evidence_media_id']);
                $table->dropForeign(['reviewed_by']);
            }
            $table->dropColumn([
                'category',
                'evidence_media_id',
                'is_emergency',
                'decision_reason',
                'reviewed_by',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('final_price')->nullable();
        });

        DB::table('order_items')->whereNotNull('provider_price')->update([
            'final_price' => DB::raw('provider_price'),
        ]);

        Schema::table('order_items', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['reviewed_by']);
                $table->dropForeign(['replaced_by_order_item_id']);
            }
            $table->dropColumn([
                'usage_date',
                'quantity',
                'provider_price',
                'commission',
                'admin_note',
                'reviewed_by',
                'reviewed_at',
                'review_due_at',
                'approved_at',
                'replaced_by_order_item_id',
                'hold_key',
            ]);
        });

        DB::table('events')->where('status', 'dievaluasi')->update(['status' => 'dinilai']);

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('budget');
        });
    }
};
