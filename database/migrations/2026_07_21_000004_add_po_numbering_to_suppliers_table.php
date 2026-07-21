<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'po_number_prefix')) {
                $table->string('po_number_prefix')->nullable()->after('pic_supplier');
            }

            if (! Schema::hasColumn('suppliers', 'po_number_padding')) {
                $table->unsignedTinyInteger('po_number_padding')->default(5)->after('po_number_prefix');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'po_number_padding')) {
                $table->dropColumn('po_number_padding');
            }

            if (Schema::hasColumn('suppliers', 'po_number_prefix')) {
                $table->dropColumn('po_number_prefix');
            }
        });
    }
};
