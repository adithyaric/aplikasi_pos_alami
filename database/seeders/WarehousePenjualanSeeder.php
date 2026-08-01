<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\User;
use App\Services\BranchPenjualanManager;
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
                'payment_status' => 'unpaid',
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
            if (Penjualan::where('code', $sale['code'])->exists()) {
                continue;
            }

            $manager->create($sale, (int) $operator->id);
        }

        $this->seedBranchSale($branch, $products);
    }

    private function seedBranchSale(Outlet $branch, $products): void
    {
        $product = $products->get('ALM-BLD-20');
        $shop = Outlet::shops()->orderBy('id')->first();
        $salesman = Salesman::where('outlet_id', $branch->id)
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('id')
            ->first();

        if (! $product || ! $shop || ! $salesman?->user || Penjualan::where('code', 'INV-CBG-00001')->exists()) {
            return;
        }

        $branchBalance = OwnerStock::where('owner_id', $branch->id)
            ->where('product_id', $product->id)
            ->sum('qty');

        if ($branchBalance < 2) {
            return;
        }

        app(BranchPenjualanManager::class)->create([
            'code' => 'INV-CBG-00001',
            'buyer_id' => $shop->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'notes' => 'Seeder penjualan sales cabang ke toko untuk cek retur penjualan cabang.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'Pack',
                    'price' => (int) $product->harga_jual,
                ],
            ],
        ], (int) $salesman->user_id, (int) $branch->id, (int) $salesman->id);
    }
}
