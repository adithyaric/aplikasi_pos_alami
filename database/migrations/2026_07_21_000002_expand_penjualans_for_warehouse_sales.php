<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            if (! Schema::hasColumn('penjualans', 'sale_channel')) {
                $table->string('sale_channel')->default('retail')->after('code');
            }
            if (! Schema::hasColumn('penjualans', 'buyer_type')) {
                $table->string('buyer_type')->nullable()->after('sale_channel');
            }
            if (! Schema::hasColumn('penjualans', 'buyer_id')) {
                $table->unsignedBigInteger('buyer_id')->nullable()->after('buyer_type');
                $table->index(['buyer_type', 'buyer_id']);
            }
            if (! Schema::hasColumn('penjualans', 'buyer_name')) {
                $table->string('buyer_name')->nullable()->after('buyer_id');
            }
            if (! Schema::hasColumn('penjualans', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('kasir_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('penjualans', 'sale_date')) {
                $table->date('sale_date')->nullable()->after('buyer_name');
            }
            if (! Schema::hasColumn('penjualans', 'payment_type')) {
                $table->string('payment_type')->default('cash')->after('sale_date');
            }
            if (! Schema::hasColumn('penjualans', 'payment_status')) {
                $table->string('payment_status')->default('paid')->after('payment_type');
            }
            if (! Schema::hasColumn('penjualans', 'due_date')) {
                $table->date('due_date')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('penjualans', 'notes')) {
                $table->text('notes')->nullable()->after('due_date');
            }
        });

        DB::table('penjualans')
            ->whereNull('sale_date')
            ->update([
                'sale_date' => DB::raw('DATE(created_at)'),
            ]);

        Schema::table('penjualan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('penjualan_items', 'qty_input')) {
                $table->decimal('qty_input', 15, 2)->nullable()->after('qty');
            }
            if (! Schema::hasColumn('penjualan_items', 'unit')) {
                $table->string('unit')->nullable()->after('qty_input');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_items', function (Blueprint $table) {
            if (Schema::hasColumn('penjualan_items', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('penjualan_items', 'qty_input')) {
                $table->dropColumn('qty_input');
            }
        });

        Schema::table('penjualans', function (Blueprint $table) {
            if (Schema::hasColumn('penjualans', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('penjualans', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('penjualans', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('penjualans', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
            if (Schema::hasColumn('penjualans', 'sale_date')) {
                $table->dropColumn('sale_date');
            }
            if (Schema::hasColumn('penjualans', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('penjualans', 'buyer_name')) {
                $table->dropColumn('buyer_name');
            }
            if (Schema::hasColumn('penjualans', 'buyer_id')) {
                $table->dropIndex(['buyer_type', 'buyer_id']);
                $table->dropColumn('buyer_id');
            }
            if (Schema::hasColumn('penjualans', 'buyer_type')) {
                $table->dropColumn('buyer_type');
            }
            if (Schema::hasColumn('penjualans', 'sale_channel')) {
                $table->dropColumn('sale_channel');
            }
        });
    }
};
