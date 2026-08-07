<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('penjualan_items', 'discount')) {
                $table->bigInteger('discount')->default(0)->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_items', function (Blueprint $table) {
            if (Schema::hasColumn('penjualan_items', 'discount')) {
                $table->dropColumn('discount');
            }
        });
    }
};
