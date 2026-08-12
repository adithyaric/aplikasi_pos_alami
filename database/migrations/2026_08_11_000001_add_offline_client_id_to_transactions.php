<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->string('offline_client_id', 100)->nullable()->unique()->after('code');
        });

        Schema::table('penjualans', function (Blueprint $table) {
            $table->string('offline_client_id', 100)->nullable()->unique()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropUnique(['offline_client_id']);
            $table->dropColumn('offline_client_id');
        });

        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropUnique(['offline_client_id']);
            $table->dropColumn('offline_client_id');
        });
    }
};
