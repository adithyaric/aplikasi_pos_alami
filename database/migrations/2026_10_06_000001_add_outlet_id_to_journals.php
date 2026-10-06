<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
        });

        // Label journals created before this column existed, including payments.
        DB::table('penjualans')->where('sale_channel', 'branch')->whereNotNull('outlet_id')
            ->select('id', 'outlet_id')->orderBy('id')->chunkById(500, function ($sales) {
                foreach ($sales as $sale) {
                    DB::table('journals')
                        ->whereIn('ref_type', ['SALES', 'SALES_PAYMENT'])
                        ->where('ref_id', $sale->id)
                        ->update(['outlet_id' => $sale->outlet_id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('journals', fn (Blueprint $table) => $table->dropConstrainedForeignId('outlet_id'));
    }
};
