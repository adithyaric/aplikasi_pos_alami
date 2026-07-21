<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Services\WarehousePenjualanManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WarehousePenjualanSeeder extends Seeder
{
    public function run(): void
    {
        $operator = User::firstOrCreate(
            ['email' => 'superadmin@mailinator.com'],
            [
                'name' => 'superadmin',
                'username' => 'superadmin@mailinator.com',
                'role' => 'superadmin',
                'status' => 'active',
                'alamat' => 'Yogyakarta',
                'no_telp' => '+620000000003',
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
            ]
        );

        $products = Product::whereIn('code', ['ALM-REG-12', 'ALM-MTH-12', 'ALM-SLM-16', 'ALM-BLD-20'])
            ->get()
            ->keyBy('code');

        $agent = Agent::where('code', 'AGN-001')->first();
        $canvas = Canvas::where('code', 'CVS-001')->first();
        $branch = Outlet::branches()->orderBy('id')->first();

        if (! $agent || ! $canvas || ! $branch || $products->count() < 4) {
            return;
        }

        $sales = [
            [
                'code' => 'PNJ00001',
                'buyer_type' => 'agent',
                'buyer_id' => $agent->id,
                'sale_date' => now()->subDays(3)->toDateString(),
                'payment_type' => 'termin',
                'payment_status' => 'unpaid',
                'due_date' => now()->addDays(11)->toDateString(),
                'discount' => 15000,
                'notes' => 'Seeder penjualan agen untuk cek flow termin.',
                'items' => [
                    [
                        'product_id' => $products['ALM-REG-12']->id,
                        'qty' => 3,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-REG-12']->harga_jual,
                    ],
                    [
                        'product_id' => $products['ALM-MTH-12']->id,
                        'qty' => 2,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-MTH-12']->harga_jual,
                    ],
                ],
            ],
            [
                'code' => 'PNJ00002',
                'buyer_type' => 'canvas',
                'buyer_id' => $canvas->id,
                'sale_date' => now()->subDays(2)->toDateString(),
                'payment_type' => 'cash',
                'payment_status' => 'paid',
                'due_date' => null,
                'discount' => 0,
                'notes' => 'Seeder penjualan canvas untuk cek flow cash.',
                'items' => [
                    [
                        'product_id' => $products['ALM-SLM-16']->id,
                        'qty' => 2,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-SLM-16']->harga_jual,
                    ],
                ],
            ],
            [
                'code' => 'PNJ00003',
                'buyer_type' => 'outlet',
                'buyer_id' => $branch->id,
                'sale_date' => now()->subDay()->toDateString(),
                'payment_type' => 'termin',
                'payment_status' => 'partial',
                'due_date' => now()->addDays(6)->toDateString(),
                'discount' => 10000,
                'notes' => 'Seeder penjualan cabang untuk cek owner stock.',
                'items' => [
                    [
                        'product_id' => $products['ALM-BLD-20']->id,
                        'qty' => 1,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-BLD-20']->harga_jual,
                    ],
                    [
                        'product_id' => $products['ALM-REG-12']->id,
                        'qty' => 2,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-REG-12']->harga_jual,
                    ],
                ],
            ],
        ];

        $manager = app(WarehousePenjualanManager::class);

        foreach ($sales as $sale) {
            if (\App\Models\Penjualan::where('code', $sale['code'])->exists()) {
                continue;
            }

            $manager->create($sale, (int) $operator->id);
        }
    }
}
