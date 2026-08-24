<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('decaa_akun-perkiraan.csv');
        if (! is_file($path)) {
            return;
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return;
        }

        $rows = [];
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $code = trim((string) ($row[2] ?? ''));
            if ($code === '') {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'name' => trim((string) ($row[3] ?? $code)),
                'type_code' => trim((string) ($row[1] ?? '')) ?: 'OCAS',
                'parent_code' => trim((string) ($row[4] ?? '')) ?: null,
            ];
        }
        fclose($handle);

        $parentCodes = collect($rows)->pluck('parent_code')->filter()->unique()->all();
        foreach ($rows as $row) {
            Account::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type_code' => $row['type_code'],
                    'is_header' => in_array($row['code'], $parentCodes, true),
                    'is_active' => true,
                ]
            );
        }

        foreach ($rows as $row) {
            Account::where('code', $row['code'])->update([
                'parent_id' => $row['parent_code'] ? Account::where('code', $row['parent_code'])->value('id') : null,
            ]);
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
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'account_id' => Account::where('code', $code)->value('id'),
                    'description' => $description,
                ]
            );
        }
    }
}
