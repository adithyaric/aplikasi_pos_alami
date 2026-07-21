<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (! Schema::hasColumn('pembelians', 'customer_po')) {
                $table->string('customer_po')->nullable()->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (Schema::hasColumn('pembelians', 'customer_po')) {
                $table->dropColumn('customer_po');
            }
        });
    }
};
