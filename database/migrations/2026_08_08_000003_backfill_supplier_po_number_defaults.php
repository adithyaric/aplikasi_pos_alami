<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('suppliers', 'po_number_prefix')) {
            DB::table('suppliers')
                ->where(function ($query) {
                    $query->whereNull('po_number_prefix')
                        ->orWhere('po_number_prefix', '');
                })
                ->update([
                    'po_number_prefix' => 'PO-{SUPPLIER_CODE}-{YYYY}{MM}-{SEQ}',
                ]);
        }

        if (Schema::hasColumn('suppliers', 'po_number_padding')) {
            DB::table('suppliers')
                ->where(function ($query) {
                    $query->whereNull('po_number_padding')
                        ->orWhere('po_number_padding', 0);
                })
                ->update([
                    'po_number_padding' => 5,
                ]);
        }
    }

    public function down(): void
    {
        // Existing supplier settings must not be erased on rollback.
    }
};
