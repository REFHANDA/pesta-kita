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
        // Tahap D3a: rollback dibuat defensif/idempoten. Sebagian kolom dapat
        // sudah dihapus oleh migrasi berikutnya, sehingga tiap drop dicek.
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('company_bank_accounts') && Schema::hasColumn('company_bank_accounts', 'sort_order')) {
            Schema::table('company_bank_accounts', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }

        // SQLite tidak dapat menghapus kolom yang masih dirujuk foreign key
        // (batasan mesin, bukan data). Rollback kolom legacy dilewati di SQLite;
        // MySQL tetap menjalankan drop penuh.
        if (Schema::hasTable('order_objections') && DB::getDriverName() !== 'sqlite') {
            $columns = array_values(array_filter(
                ['category', 'evidence_media_id', 'is_emergency', 'decision_reason', 'reviewed_by'],
                fn (string $column): bool => Schema::hasColumn('order_objections', $column),
            ));

            if ($columns !== []) {
                Schema::table('order_objections', function (Blueprint $table) use ($columns) {
                    if (DB::getDriverName() !== 'sqlite') {
                        foreach (['evidence_media_id', 'reviewed_by'] as $foreign) {
                            if (in_array($foreign, $columns, true)) {
                                $table->dropForeign([$foreign]);
                            }
                        }
                    }
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('order_items') && DB::getDriverName() !== 'sqlite') {
            if (! Schema::hasColumn('order_items', 'final_price')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->unsignedBigInteger('final_price')->nullable();
                });
            }

            if (Schema::hasColumn('order_items', 'provider_price')) {
                DB::table('order_items')->whereNotNull('provider_price')->update([
                    'final_price' => DB::raw('provider_price'),
                ]);
            }

            $columns = array_values(array_filter(
                [
                    'usage_date', 'quantity', 'provider_price', 'commission', 'admin_note',
                    'reviewed_by', 'reviewed_at', 'review_due_at', 'approved_at',
                    'replaced_by_order_item_id', 'hold_key',
                ],
                fn (string $column): bool => Schema::hasColumn('order_items', $column),
            ));

            if ($columns !== []) {
                Schema::table('order_items', function (Blueprint $table) use ($columns) {
                    if (DB::getDriverName() !== 'sqlite') {
                        foreach (['reviewed_by', 'replaced_by_order_item_id'] as $foreign) {
                            if (in_array($foreign, $columns, true)) {
                                $table->dropForeign([$foreign]);
                            }
                        }
                    }
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('events')) {
            DB::table('events')->where('status', 'dievaluasi')->update(['status' => 'dinilai']);

            if (Schema::hasColumn('events', 'budget')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->dropColumn('budget');
                });
            }
        }

        Schema::enableForeignKeyConstraints();
    }
};
