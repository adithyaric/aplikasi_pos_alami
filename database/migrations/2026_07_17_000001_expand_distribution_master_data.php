<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agents')) {
            Schema::table('agents', function (Blueprint $table) {
                if (! Schema::hasColumn('agents', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }
                if (! Schema::hasColumn('agents', 'alamat')) {
                    $table->text('alamat')->nullable()->after('desc');
                }
                if (! Schema::hasColumn('agents', 'no_telp')) {
                    $table->string('no_telp')->nullable()->after('alamat');
                }
                if (! Schema::hasColumn('agents', 'termin_days')) {
                    $table->unsignedInteger('termin_days')->default(0)->after('no_telp');
                }
                if (! Schema::hasColumn('agents', 'credit_limit')) {
                    $table->decimal('credit_limit', 15, 2)->default(0)->after('termin_days');
                }
                if (! Schema::hasColumn('agents', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('credit_limit');
                }
            });
        }

        if (Schema::hasTable('canvases')) {
            Schema::table('canvases', function (Blueprint $table) {
                if (! Schema::hasColumn('canvases', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }
                if (! Schema::hasColumn('canvases', 'alamat')) {
                    $table->text('alamat')->nullable()->after('desc');
                }
                if (! Schema::hasColumn('canvases', 'no_telp')) {
                    $table->string('no_telp')->nullable()->after('alamat');
                }
                if (! Schema::hasColumn('canvases', 'termin_days')) {
                    $table->unsignedInteger('termin_days')->default(0)->after('no_telp');
                }
                if (! Schema::hasColumn('canvases', 'credit_limit')) {
                    $table->decimal('credit_limit', 15, 2)->default(0)->after('termin_days');
                }
                if (! Schema::hasColumn('canvases', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('credit_limit');
                }
            });
        }

        if (Schema::hasTable('salesmans')) {
            Schema::table('salesmans', function (Blueprint $table) {
                if (! Schema::hasColumn('salesmans', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }
                if (! Schema::hasColumn('salesmans', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->after('no_telp')->constrained('outlets')->nullOnDelete();
                }
                if (! Schema::hasColumn('salesmans', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('outlet_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'satuan_terbesar')) {
                    $table->string('satuan_terbesar')->nullable()->after('konversi_qty');
                }
                if (! Schema::hasColumn('products', 'konversi_qty_terbesar')) {
                    $table->decimal('konversi_qty_terbesar', 10, 2)->nullable()->after('satuan_terbesar');
                }
            });
        }

        if (Schema::hasTable('refund_pembelians')) {
            Schema::table('refund_pembelians', function (Blueprint $table) {
                if (! Schema::hasColumn('refund_pembelians', 'return_mode')) {
                    $table->string('return_mode')->default('cash_refund')->after('type');
                }
            });
        }

        if (Schema::hasTable('outlets') && Schema::hasTable('branches')) {
            $branches = DB::table('branches')->select('name', 'desc', 'created_at', 'updated_at')->get();

            foreach ($branches as $branch) {
                $existing = DB::table('outlets')->where('name', $branch->name)->first();

                if ($existing) {
                    DB::table('outlets')
                        ->where('id', $existing->id)
                        ->update([
                            'jenis_outlet' => $existing->jenis_outlet ?: 'branch',
                            'desc' => $existing->desc ?: $branch->desc,
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                DB::table('outlets')->insert([
                    'logo' => null,
                    'name' => $branch->name,
                    'jenis_outlet' => 'branch',
                    'alamat' => null,
                    'npwp' => null,
                    'slogan' => null,
                    'desc' => $branch->desc,
                    'footer' => null,
                    'created_at' => $branch->created_at ?? now(),
                    'updated_at' => $branch->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('refund_pembelians') && Schema::hasColumn('refund_pembelians', 'return_mode')) {
            Schema::table('refund_pembelians', function (Blueprint $table) {
                $table->dropColumn('return_mode');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $dropColumns = [];

                if (Schema::hasColumn('products', 'satuan_terbesar')) {
                    $dropColumns[] = 'satuan_terbesar';
                }
                if (Schema::hasColumn('products', 'konversi_qty_terbesar')) {
                    $dropColumns[] = 'konversi_qty_terbesar';
                }

                if ($dropColumns !== []) {
                    $table->dropColumn($dropColumns);
                }
            });
        }

        if (Schema::hasTable('salesmans')) {
            Schema::table('salesmans', function (Blueprint $table) {
                if (Schema::hasColumn('salesmans', 'user_id')) {
                    $table->dropConstrainedForeignId('user_id');
                }
                if (Schema::hasColumn('salesmans', 'outlet_id')) {
                    $table->dropConstrainedForeignId('outlet_id');
                }
                if (Schema::hasColumn('salesmans', 'code')) {
                    $table->dropColumn('code');
                }
            });
        }

        foreach (['agents', 'canvases'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $dropColumns = [];

                foreach (['code', 'alamat', 'no_telp', 'termin_days', 'credit_limit', 'is_active'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $dropColumns[] = $column;
                    }
                }

                if ($dropColumns !== []) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }
};
