<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('owner_stocks')) {
            Schema::table('owner_stocks', function (Blueprint $table) {
                if (! Schema::hasColumn('owner_stocks', 'sku')) {
                    $table->string('sku')->nullable()->after('stock_id');
                }
                if (! Schema::hasColumn('owner_stocks', 'harga_beli')) {
                    $table->decimal('harga_beli', 15, 2)->nullable()->after('expired_at');
                }
            });

            if (Schema::hasColumn('owner_stocks', 'batch_number') && Schema::hasColumn('owner_stocks', 'sku')) {
                DB::table('owner_stocks')
                    ->whereNull('sku')
                    ->update(['sku' => DB::raw('batch_number')]);
            }

            if (Schema::hasColumn('owner_stocks', 'hpp') && Schema::hasColumn('owner_stocks', 'harga_beli')) {
                DB::table('owner_stocks')
                    ->whereNull('harga_beli')
                    ->update(['harga_beli' => DB::raw('hpp')]);
            }
        }

        Schema::create('owner_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('owner_stock_id')->nullable()->constrained('owner_stocks')->nullOnDelete();
            $table->foreignId('stock_id')->nullable()->constrained('stocks')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('qty_in', 15, 2)->default(0);
            $table->decimal('qty_out', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'product_id']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('owner_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('owner_stock_id')->nullable()->constrained('owner_stocks')->nullOnDelete();
            $table->date('adjustment_date');
            $table->decimal('system_qty', 15, 2)->default(0);
            $table->decimal('physical_qty', 15, 2)->default(0);
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('Selesai');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('penjualan_item_allocations', function (Blueprint $table) {
            if (! Schema::hasColumn('penjualan_item_allocations', 'owner_stock_id')) {
                $table->foreignId('owner_stock_id')->nullable()->after('stock_id')->constrained('owner_stocks')->nullOnDelete();
            }
        });

        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'return_scope')) {
                $table->string('return_scope')->nullable()->after('outlet_id');
            }
            if (! Schema::hasColumn('refunds', 'sale_channel')) {
                $table->string('sale_channel')->nullable()->after('return_scope');
            }
            if (! Schema::hasColumn('refunds', 'applied_penjualan_id')) {
                $table->foreignId('applied_penjualan_id')->nullable()->after('penjualan_id')->constrained('penjualans')->nullOnDelete();
            }
            if (! Schema::hasColumn('refunds', 'source_outlet_id')) {
                $table->foreignId('source_outlet_id')->nullable()->after('outlet_id')->constrained('outlets')->nullOnDelete();
            }
            if (! Schema::hasColumn('refunds', 'salesman_id')) {
                $table->unsignedBigInteger('salesman_id')->nullable()->after('source_outlet_id');
            }
            if (! Schema::hasColumn('refunds', 'invoice_total_before')) {
                $table->decimal('invoice_total_before', 15, 2)->nullable()->after('total');
            }
            if (! Schema::hasColumn('refunds', 'invoice_total_after')) {
                $table->decimal('invoice_total_after', 15, 2)->nullable()->after('invoice_total_before');
            }
            if (! Schema::hasColumn('refunds', 'notes')) {
                $table->text('notes')->nullable()->after('invoice_total_after');
            }
        });

        Schema::table('refund_items', function (Blueprint $table) {
            if (! Schema::hasColumn('refund_items', 'qty_input')) {
                $table->decimal('qty_input', 15, 2)->nullable()->after('qty');
            }
            if (! Schema::hasColumn('refund_items', 'unit')) {
                $table->string('unit')->nullable()->after('qty_input');
            }
            if (! Schema::hasColumn('refund_items', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('unit');
            }
            if (! Schema::hasColumn('refund_items', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('price');
            }
            if (! Schema::hasColumn('refund_items', 'stock_visibility')) {
                $table->string('stock_visibility')->default('hidden')->after('subtotal');
            }
            if (! Schema::hasColumn('refund_items', 'source_owner_stock_id')) {
                $table->foreignId('source_owner_stock_id')->nullable()->after('stock_visibility')->constrained('owner_stocks')->nullOnDelete();
            }
        });

        Schema::create('penjualan_total_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualans')->cascadeOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained('refunds')->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('total_before', 15, 2)->default(0);
            $table->decimal('total_after', 15, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_total_adjustments');

        if (Schema::hasTable('refund_items')) {
            Schema::table('refund_items', function (Blueprint $table) {
                foreach (['source_owner_stock_id', 'stock_visibility', 'subtotal', 'price', 'unit', 'qty_input'] as $column) {
                    if (Schema::hasColumn('refund_items', $column)) {
                        if ($column === 'source_owner_stock_id') {
                            $table->dropConstrainedForeignId($column);
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                }
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                foreach ([
                    'notes',
                    'invoice_total_after',
                    'invoice_total_before',
                    'salesman_id',
                    'source_outlet_id',
                    'applied_penjualan_id',
                    'sale_channel',
                    'return_scope',
                ] as $column) {
                    if (! Schema::hasColumn('refunds', $column)) {
                        continue;
                    }

                    if (in_array($column, ['source_outlet_id', 'applied_penjualan_id'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('penjualan_item_allocations') && Schema::hasColumn('penjualan_item_allocations', 'owner_stock_id')) {
            Schema::table('penjualan_item_allocations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('owner_stock_id');
            });
        }

        Schema::dropIfExists('owner_stock_adjustments');
        Schema::dropIfExists('owner_stock_movements');
    }
};
