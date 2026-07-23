<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refunds')) {
            return;
        }

        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'buyer_type')) {
                $table->string('buyer_type')->nullable()->after('outlet_id');
            }
            if (! Schema::hasColumn('refunds', 'buyer_id')) {
                $table->unsignedBigInteger('buyer_id')->nullable()->after('buyer_type');
            }
            if (! Schema::hasColumn('refunds', 'buyer_name')) {
                $table->string('buyer_name')->nullable()->after('buyer_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('refunds')) {
            return;
        }

        Schema::table('refunds', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['buyer_type', 'buyer_id', 'buyer_name'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
