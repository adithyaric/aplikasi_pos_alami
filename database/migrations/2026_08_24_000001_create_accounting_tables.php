<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('type_code', 10);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_header')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type_code', 'is_active']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number', 50)->unique();
            $table->date('transaction_date');
            $table->string('ref_type', 50)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('source_key', 120)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->timestamps();

            $table->index(['transaction_date', 'ref_type', 'ref_id']);
        });

        Schema::create('journal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->timestamps();

            $table->index('account_id');
        });

        $this->seedCoreAccounts();

        // The supplied CoA CSV is part of the application bundle. Import it
        // during a fresh installation when available, while retaining the
        // minimal core accounts above as a safe fallback.
        if (is_file(base_path('decaa_akun-perkiraan.csv'))) {
            app(\Database\Seeders\AccountingSeeder::class)->run();
        }
    }

    private function seedCoreAccounts(): void
    {
        $accounts = [
            ['code' => '1101', 'name' => 'Kas & Bank', 'type_code' => 'BANK', 'parent_code' => null, 'is_header' => true],
            ['code' => '110101', 'name' => 'Kas Kasir', 'type_code' => 'BANK', 'parent_code' => '1101', 'is_header' => false],
            ['code' => '110102', 'name' => 'Kas Bank BRI Aura', 'type_code' => 'BANK', 'parent_code' => '1101', 'is_header' => false],
            ['code' => '110103', 'name' => 'Kas Bank BRI Mellyn', 'type_code' => 'BANK', 'parent_code' => '1101', 'is_header' => false],
            ['code' => '110104', 'name' => 'Kas Bank BRI Esti', 'type_code' => 'BANK', 'parent_code' => '1101', 'is_header' => false],
            ['code' => '110105', 'name' => 'Kas Kecil', 'type_code' => 'BANK', 'parent_code' => '1101', 'is_header' => false],
            ['code' => '1103', 'name' => 'Piutang Usaha', 'type_code' => 'AREC', 'parent_code' => null, 'is_header' => true],
            ['code' => '110301', 'name' => 'Piutang Usaha IDR', 'type_code' => 'AREC', 'parent_code' => '1103', 'is_header' => false],
            ['code' => '1104', 'name' => 'Persediaan', 'type_code' => 'INTR', 'parent_code' => null, 'is_header' => true],
            ['code' => '110401', 'name' => 'Persediaan', 'type_code' => 'INTR', 'parent_code' => '1104', 'is_header' => false],
            ['code' => '2101', 'name' => 'Utang Usaha', 'type_code' => 'APAY', 'parent_code' => null, 'is_header' => true],
            ['code' => '210101', 'name' => 'Utang Usaha IDR', 'type_code' => 'APAY', 'parent_code' => '2101', 'is_header' => false],
            ['code' => '3000', 'name' => 'Modal', 'type_code' => 'EQTY', 'parent_code' => null, 'is_header' => true],
            ['code' => '300001', 'name' => 'Equitas Saldo Awal', 'type_code' => 'EQTY', 'parent_code' => '3000', 'is_header' => false],
            ['code' => '300002', 'name' => 'Laba Ditahan', 'type_code' => 'EQTY', 'parent_code' => '3000', 'is_header' => false],
            ['code' => '4000', 'name' => 'Pendapatan Operasional', 'type_code' => 'REVE', 'parent_code' => null, 'is_header' => true],
            ['code' => '400001', 'name' => 'Penjualan All Item', 'type_code' => 'REVE', 'parent_code' => '4000', 'is_header' => false],
            ['code' => '5101', 'name' => 'Beban Pokok Penjualan', 'type_code' => 'COGS', 'parent_code' => null, 'is_header' => false],
            ['code' => '6000', 'name' => 'Beban Operasional', 'type_code' => 'EXPS', 'parent_code' => null, 'is_header' => true],
            ['code' => '600020', 'name' => 'Beban Operasional Lainnya', 'type_code' => 'EXPS', 'parent_code' => '6000', 'is_header' => false],
            ['code' => '7200', 'name' => 'Beban Diluar Usaha', 'type_code' => 'OEXP', 'parent_code' => null, 'is_header' => true],
            ['code' => '720007', 'name' => 'Beban Diluar Usaha Lainnya', 'type_code' => 'OEXP', 'parent_code' => '7200', 'is_header' => false],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->insert([
                'code' => $account['code'],
                'name' => $account['name'],
                'type_code' => $account['type_code'],
                'parent_id' => null,
                'is_header' => $account['is_header'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($accounts as $account) {
            if ($account['parent_code']) {
                DB::table('accounts')
                    ->where('code', $account['code'])
                    ->update(['parent_id' => DB::table('accounts')->where('code', $account['parent_code'])->value('id')]);
            }
        }

        $settings = [
            'DEFAULT_ACC_SALES' => ['400001', 'Akun pendapatan penjualan default'],
            'DEFAULT_ACC_COGS' => ['5101', 'Akun beban pokok penjualan default'],
            'DEFAULT_ACC_INVENTORY' => ['110401', 'Akun persediaan default'],
            'DEFAULT_ACC_AR' => ['110301', 'Akun piutang usaha default'],
            'DEFAULT_ACC_AP' => ['210101', 'Akun utang usaha default'],
            'DEFAULT_ACC_OPENING_EQ' => ['300001', 'Akun ekuitas saldo awal default'],
            'DEFAULT_ACC_CASH' => ['110101', 'Akun kas default untuk pembayaran'],
            'DEFAULT_ACC_EXPENSE' => ['600020', 'Akun beban default'],
        ];

        foreach ($settings as $key => [$code, $description]) {
            DB::table('system_settings')->insert([
                'key' => $key,
                'account_id' => DB::table('accounts')->where('code', $code)->value('id'),
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_details');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('accounts');
    }
};
