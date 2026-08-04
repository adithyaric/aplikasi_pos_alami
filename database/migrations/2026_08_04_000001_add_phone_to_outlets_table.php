<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('outlets', 'no_telp')) {
            Schema::table('outlets', function (Blueprint $table) {
                $table->string('no_telp')->nullable()->after('alamat');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('outlets', 'no_telp')) {
            Schema::table('outlets', function (Blueprint $table) {
                $table->dropColumn('no_telp');
            });
        }
    }
};
