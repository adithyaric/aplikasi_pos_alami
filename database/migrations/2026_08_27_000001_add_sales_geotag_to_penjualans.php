<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('old_debt_override');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('location_accuracy', 10, 2)->nullable()->after('longitude');
            $table->dateTime('location_captured_at')->nullable()->after('location_accuracy');
        });
    }

    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_accuracy',
                'location_captured_at',
            ]);
        });
    }
};
