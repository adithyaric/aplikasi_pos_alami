<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Salesman;
use App\Models\User;
use App\Services\BranchPenjualanManager;
use App\Services\SalesReturnManager;
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
        $shop = Outlet::shops()->orderBy('id')->first();

        if (! $agent || ! $canvas || ! $branch || ! $shop || $products->count() < 4) {
            return;
        }

        $monthYear = now()->format('m.y');
        $sales = [
            [
                'code' => '0001.'.$monthYear,
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
                'code' => '0002.'.$monthYear,
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
                'code' => '0003.'.$monthYear,
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
            [
                'code' => '0004.'.$monthYear,
                'buyer_type' => 'agent',
                'buyer_id' => Agent::where('code', 'AGN-002')->value('id'),
                'sale_date' => now()->subDays(9)->toDateString(),
                'payment_type' => 'termin',
                'payment_status' => 'unpaid',
                'due_date' => now()->addDays(5)->toDateString(),
                'discount' => 0,
                'notes' => 'Seeder penjualan tambahan produk supplier S00001.',
                'items' => [
                    [
                        'product_id' => $products['ALM-REG-12']->id,
                        'qty' => 1,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-REG-12']->harga_jual,
                    ],
                ],
            ],
            [
                'code' => '0005.'.$monthYear,
                'buyer_type' => 'canvas',
                'buyer_id' => Canvas::where('code', 'CVS-002')->value('id'),
                'sale_date' => now()->subDays(7)->toDateString(),
                'payment_type' => 'termin',
                'payment_status' => 'unpaid',
                'due_date' => now()->addDays(4)->toDateString(),
                'discount' => 0,
                'notes' => 'Seeder penjualan tambahan produk supplier S00002.',
                'items' => [
                    [
                        'product_id' => $products['ALM-SLM-16']->id,
                        'qty' => 1,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-SLM-16']->harga_jual,
                    ],
                ],
            ],
            [
                'code' => '0006.'.$monthYear,
                'buyer_type' => 'toko',
                'buyer_id' => $shop->id,
                'sale_date' => now()->subDays(5)->toDateString(),
                'payment_type' => 'cash',
                'payment_status' => 'paid',
                'due_date' => null,
                'discount' => 0,
                'notes' => 'Seeder penjualan toko tambahan produk supplier S00002.',
                'items' => [
                    [
                        'product_id' => $products['ALM-BLD-20']->id,
                        'qty' => 1,
                        'unit' => 'Slop',
                        'price' => (int) $products['ALM-BLD-20']->harga_jual,
                    ],
                ],
            ],
        ];

        $manager = app(WarehousePenjualanManager::class);

        foreach ($sales as $sale) {
            if (Penjualan::withTrashed()->where('code', $sale['code'])->exists()) {
                continue;
            }

            $manager->create($sale, (int) $operator->id);
        }

        $this->seedAdditionalSalesReturns($products, (int) $operator->id);

        $this->seedBranchSale($branch, $products);
    }

    private function seedAdditionalSalesReturns($products, int $operatorId): void
    {
        $monthYear = now()->format('m.y');
        $returnDefinitions = [
            [
                'sale_code' => '0004.'.$monthYear,
                'return_code' => 'RTR-SEED-SALE-S00001-001',
                'buyer_type' => 'agent',
                'buyer_id' => Agent::where('code', 'AGN-002')->value('id'),
                'product_code' => 'ALM-REG-12',
            ],
            [
                'sale_code' => '0005.'.$monthYear,
                'return_code' => 'RTR-SEED-SALE-S00002-001',
                'buyer_type' => 'canvas',
                'buyer_id' => Canvas::where('code', 'CVS-002')->value('id'),
                'product_code' => 'ALM-SLM-16',
            ],
        ];

        foreach ($returnDefinitions as $definition) {
            if (Refund::withTrashed()->where('code', $definition['return_code'])->exists()) {
                continue;
            }

            $sale = Penjualan::where('code', $definition['sale_code'])
                ->where('buyer_type', $definition['buyer_type'])
                ->where('buyer_id', $definition['buyer_id'])
                ->first();
            $product = $products->get($definition['product_code']);
            if (! $sale || ! $product) {
                continue;
            }

            app(SalesReturnManager::class)->create([
                'code' => $definition['return_code'],
                'tanggal' => now()->subDays(2)->toDateString(),
                'return_scope' => SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE,
                'buyer_type' => $definition['buyer_type'],
                'buyer_id' => (int) $definition['buyer_id'],
                'source_outlet_id' => null,
                'salesman_id' => null,
                'requires_superadmin_approval' => false,
                'product' => [
                    [
                        'product_id' => $product->id,
                        'qty' => 1,
                        'unit' => 'Pack',
                        'price' => (int) $product->harga_jual,
                        'alasan' => 'Retur penjualan demo supplier '.($definition['buyer_type'] === 'agent' ? 'S00001' : 'S00002'),
                    ],
                ],
            ], $operatorId);
        }
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

        $nextNumber = 1;
        Penjualan::withTrashed()
            ->where('sale_channel', 'branch')
            ->pluck('code')
            ->each(function (string $code) use (&$nextNumber): void {
                if (preg_match('/^CBG\.(\d{4})\.\d{2}\.\d{2}$/', $code, $matches)
                    || preg_match('/^(\d{4})\.\d{2}\.\d{2}$/', $code, $matches)
                    || preg_match('/^INV-CBG-(\d+)$/', $code, $matches)) {
                    $nextNumber = max($nextNumber, (int) $matches[1] + 1);
                }
            });
        $saleCode = sprintf('CBG.%04d.%s', $nextNumber, now()->format('m.y'));

        if (! $product || ! $shop || ! $salesman?->user || Penjualan::where('sale_channel', 'branch')->whereDate('sale_date', now()->toDateString())->exists()) {
            return;
        }

        $branchBalance = OwnerStock::where('owner_id', $branch->id)
            ->where('product_id', $product->id)
            ->sum('qty');

        if ($branchBalance < 2) {
            return;
        }

        app(BranchPenjualanManager::class)->create([
            'code' => $saleCode,
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
