<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            if (! Schema::hasColumn('penjualans', 'shipping_cost')) {
                $table->unsignedBigInteger('shipping_cost')->default(0)->after('total');
            }

            if (! Schema::hasColumn('penjualans', 'old_debt_override')) {
                $table->unsignedBigInteger('old_debt_override')->nullable()->after('shipping_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $columns = [];

            foreach (['old_debt_override', 'shipping_cost'] as $column) {
                if (Schema::hasColumn('penjualans', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
