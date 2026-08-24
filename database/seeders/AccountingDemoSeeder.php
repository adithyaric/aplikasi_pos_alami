<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Services\AccountingService;
use Illuminate\Database\Seeder;

/**
 * Small, repeatable accounting fixture for a fresh local/demo installation.
 *
 * The operational seeders create the purchase and sales records. Their
 * journals are rebuilt once more here after every source record exists, so a
 * fresh install is also useful for checking the accounting reports.
 */
class AccountingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoMonth = now()->startOfMonth();
        $openingDate = $demoMonth->copy()->addDay();
        $expenseDate = $demoMonth->copy()->addDays(3);

        $expenseHeader = Account::updateOrCreate(
            ['code' => '6900'],
            [
                'name' => 'Beban Demo Akuntansi',
                'type_code' => 'EXPS',
                'parent_id' => null,
                'is_header' => true,
                'is_active' => true,
            ]
        );
        $expenseAccount = Account::updateOrCreate(
            ['code' => '690001'],
            [
                'name' => 'Beban Transportasi Demo',
                'type_code' => 'EXPS',
                'parent_id' => $expenseHeader->id,
                'is_header' => false,
                'is_active' => true,
            ]
        );

        $equityHeader = Account::updateOrCreate(
            ['code' => '3100'],
            [
                'name' => 'Ekuitas Demo',
                'type_code' => 'EQTY',
                'parent_id' => null,
                'is_header' => true,
                'is_active' => true,
            ]
        );
        $equityAccount = Account::updateOrCreate(
            ['code' => '310001'],
            [
                'name' => 'Modal Demo',
                'type_code' => 'EQTY',
                'parent_id' => $equityHeader->id,
                'is_header' => false,
                'is_active' => true,
            ]
        );

        $cash = Account::where('code', '110101')->firstOrFail();
        $inventory = Account::where('code', '110401')->firstOrFail();
        $accounting = app(AccountingService::class);

        $accounting->createJournal(
            $openingDate,
            'OPENING_BALANCE',
            null,
            'Saldo awal demo: kas dan persediaan',
            [
                ['account_id' => $cash->id, 'debit' => 50_000_000, 'credit' => 0],
                ['account_id' => $inventory->id, 'debit' => 20_000_000, 'credit' => 0],
                ['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 70_000_000],
            ],
            'SEED:OPENING_BALANCE:DEMO',
            true
        );

        $accounting->createJournal(
            $expenseDate,
            'MANUAL',
            null,
            'Jurnal manual demo: transportasi',
            [
                ['account_id' => $expenseAccount->id, 'debit' => 250_000, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 250_000],
            ],
            'SEED:MANUAL_EXPENSE:DEMO',
            true
        );

        // This is deliberately last: all seeded Pembelian and Penjualan now
        // exist, so every operational source receives exactly one current
        // journal family before the reports are opened.
        $accounting->syncAll();
    }
}
