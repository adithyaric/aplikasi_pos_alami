<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_pos', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_pos', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('customer_pos', 'address')) {
                $table->text('address')->nullable()->after('company_name');
            }

            if (! Schema::hasColumn('customer_pos', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }

            if (! Schema::hasColumn('customer_pos', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_pos', function (Blueprint $table) {
            $columns = [];

            foreach (['company_name', 'address', 'phone', 'email'] as $column) {
                if (Schema::hasColumn('customer_pos', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
