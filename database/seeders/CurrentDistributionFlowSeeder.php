<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\CustomerPo;
use App\Models\Kas;
use App\Models\Outlet;
use App\Models\Pembelian;
use App\Models\PembelianProduct;
use App\Models\PembelianTransaction;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ProductUnitConverter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CurrentDistributionFlowSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CigaretteProductSeeder::class);

        User::updateOrCreate(
            ['email' => 'admin-gudang@alami.test'],
            [
                'name' => 'Admin Gudang ALAMI',
                'username' => 'admin-gudang@alami.test',
                'role' => 'admin-gudang',
                'status' => 'active',
                'alamat' => 'Gudang Utama ALAMI Yogyakarta',
                'no_telp' => '+628111000001',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@alami.test'],
            [
                'name' => 'Owner ALAMI',
                'username' => 'owner@alami.test',
                'role' => 'owner',
                'status' => 'active',
                'alamat' => 'Yogyakarta',
                'no_telp' => '+628111000002',
                'password' => Hash::make('password'),
            ]
        );

        collect([
            [
                'name' => 'Cabang ALAMI AREA JOGJA KOTA',
                'alamat' => 'Jl. Malioboro No. 15, Yogyakarta',
                'desc' => 'Cabang distribusi area Jogja Kota',
                'leader' => [
                    'name' => 'Alfreda',
                    'username' => 'alfreda.branch@alami.test',
                    'email' => 'alfreda.branch@alami.test',
                    'no_telp' => '+628111000101',
                ],
                'sales' => ['Sales Jogja 1', 'Sales Jogja 2', 'Sales Jogja 3'],
            ],
            [
                'name' => 'Cabang ALAMI AREA KULON PROGO',
                'alamat' => 'Jl. Wates KM 4, Kulon Progo',
                'desc' => 'Cabang distribusi area Kulon Progo',
                'leader' => [
                    'name' => 'Rina',
                    'username' => 'rina.branch@alami.test',
                    'email' => 'rina.branch@alami.test',
                    'no_telp' => '+628111000102',
                ],
                'sales' => ['Sales KP 1', 'Sales KP 2', 'Sales KP 3'],
            ],
        ])->map(function (array $branch, int $index) {
            $outlet = Outlet::updateOrCreate(
                ['name' => $branch['name']],
                [
                    'logo' => null,
                    'jenis_outlet' => 'branch',
                    'alamat' => $branch['alamat'],
                    'npwp' => null,
                    'slogan' => 'Distribusi resmi ALAMI',
                    'desc' => $branch['desc'],
                    'footer' => 'Gudang Utama ALAMI',
                ]
            );

            User::updateOrCreate(
                ['email' => $branch['leader']['email']],
                [
                    'name' => $branch['leader']['name'],
                    'username' => $branch['leader']['username'],
                    'role' => 'admin-cabang',
                    'status' => 'active',
                    'alamat' => $branch['alamat'],
                    'no_telp' => $branch['leader']['no_telp'],
                    'password' => Hash::make('password'),
                    'outlet_id' => $outlet->id,
                ]
            );

            Kas::updateOrCreate(
                ['outlet_id' => $outlet->id, 'name' => 'Kas '.$branch['name']],
                ['nominal' => 0]
            );

            foreach ($branch['sales'] as $salesIndex => $salesName) {
                $salesPhone = '+628111200'.str_pad((string) (($index * 3) + $salesIndex + 1), 3, '0', STR_PAD_LEFT);
                $salesEmail = strtolower(str_replace(' ', '-', $salesName)).'@alami.test';
                $salesUser = User::updateOrCreate(
                    ['email' => $salesEmail],
                    [
                        'name' => $salesName,
                        'username' => $salesEmail,
                        'role' => 'sales',
                        'status' => 'active',
                        'alamat' => $branch['alamat'],
                        'no_telp' => $salesPhone,
                        'password' => Hash::make('password'),
                        'outlet_id' => $outlet->id,
                    ]
                );

                Salesman::updateOrCreate(
                    ['code' => sprintf('SLS-%02d-%02d', $index + 1, $salesIndex + 1)],
                    [
                        'name' => $salesName,
                        'alamat' => $branch['alamat'],
                        'no_telp' => $salesPhone,
                        'outlet_id' => $outlet->id,
                        'user_id' => $salesUser->id,
                    ]
                );
            }

            return $outlet;
        });

        collect([
            [
                'name' => 'Toko Sembako Malioboro',
                'alamat' => 'Jl. Sosrowijayan No. 10, Yogyakarta',
                'desc' => 'Customer/toko untuk simulasi penjualan cabang Jogja Kota',
            ],
            [
                'name' => 'Toko Retail Wates',
                'alamat' => 'Jl. Wates No. 21, Kulon Progo',
                'desc' => 'Customer/toko untuk simulasi penjualan cabang Kulon Progo',
            ],
        ])->each(function (array $shop) {
            Outlet::updateOrCreate(
                ['name' => $shop['name']],
                [
                    'logo' => null,
                    'jenis_outlet' => 'toko',
                    'alamat' => $shop['alamat'],
                    'npwp' => null,
                    'slogan' => 'Customer retail ALAMI',
                    'desc' => $shop['desc'],
                    'footer' => 'Terima kasih',
                ]
            );
        });

        collect([
            [
                'name' => 'PT Sumber Makmur',
                'company_name' => 'PT Sumber Makmur Distribusi',
                'address' => 'Jl. Malioboro No. 101, Yogyakarta',
                'phone' => '+622741110101',
                'email' => 'purchasing@sumbermakmur.test',
            ],
            [
                'name' => 'CV Retail Sejahtera',
                'company_name' => 'CV Retail Sejahtera',
                'address' => 'Jl. Solo KM 8, Yogyakarta',
                'phone' => '+622741110102',
                'email' => 'po@retailsejahtera.test',
            ],
            [
                'name' => 'Toko Mitra Distribusi',
                'company_name' => null,
                'address' => 'Jl. Wates KM 5, Kulon Progo',
                'phone' => '+628121110103',
                'email' => null,
            ],
        ])->each(function (array $customerPo) {
            CustomerPo::updateOrCreate(
                ['name' => $customerPo['name']],
                $customerPo
            );
        });

        $agents = [
            ['code' => 'AGN-001', 'name' => 'Superindo', 'alamat' => 'Jl. Magelang, Sleman', 'no_telp' => '+622740001001', 'termin_days' => 14, 'credit_limit' => 15000000],
            ['code' => 'AGN-002', 'name' => 'Alfamart Jogja 1', 'alamat' => 'Jl. Kaliurang KM 5, Yogyakarta', 'no_telp' => '+622740001002', 'termin_days' => 21, 'credit_limit' => 12500000],
            ['code' => 'AGN-003', 'name' => 'Indomaret Gejayan', 'alamat' => 'Jl. Gejayan No. 9, Sleman', 'no_telp' => '+622740001003', 'termin_days' => 21, 'credit_limit' => 12500000],
            ['code' => 'AGN-004', 'name' => 'Pamela', 'alamat' => 'Jl. Solo KM 7, Yogyakarta', 'no_telp' => '+622740001004', 'termin_days' => 30, 'credit_limit' => 10000000],
        ];

        foreach ($agents as $agent) {
            Agent::updateOrCreate(
                ['code' => $agent['code']],
                array_merge($agent, [
                    'desc' => 'Agen distribusi langsung dari gudang utama',
                    'is_active' => true,
                ])
            );
        }

        $canvases = [
            ['code' => 'CVS-001', 'name' => 'Mobil 1 (Pak Handoyo)', 'alamat' => 'Pool Armada Gudang Utama', 'no_telp' => '+628121110001', 'termin_days' => 7, 'credit_limit' => 8000000],
            ['code' => 'CVS-002', 'name' => 'Mobil 2 (Pak Budi)', 'alamat' => 'Pool Armada Gudang Utama', 'no_telp' => '+628121110002', 'termin_days' => 7, 'credit_limit' => 8000000],
        ];

        foreach ($canvases as $canvas) {
            Canvas::updateOrCreate(
                ['code' => $canvas['code']],
                array_merge($canvas, [
                    'desc' => 'Canvas penjualan lapangan menggunakan mobil',
                    'is_active' => true,
                ])
            );
        }

        $supplier = Supplier::where('kode_supplier', 'S00001')->firstOrFail();
        $products = Product::whereIn('code', ['ALM-REG-12', 'ALM-MTH-12', 'ALM-SLM-16', 'ALM-BLD-20'])
            ->orderBy('code')
            ->get()
            ->keyBy('code');
        $converter = app(ProductUnitConverter::class);
        $warehouseOrderCode = $supplier->generateNextPoCode();

        $warehouseOrder = Pembelian::updateOrCreate(
            ['code' => $warehouseOrderCode],
            [
                'code' => $warehouseOrderCode,
                'code_gr' => 'GR-ALAMI-00001',
                'customer_po' => 'PT Sumber Makmur',
                'supplier_id' => $supplier->id,
                'total' => 0,
                'is_published' => true,
                'owner_approval_status' => 'approved',
                'owner_approved_by' => null,
                'owner_approved_at' => now(),
                'owner_approval_note' => 'Seeder baseline stock',
                'receipt_date' => now(),
                'receipt_pic' => 'Gudang Utama',
                'receipt_status' => 'completed',
                'receipt_photo' => null,
            ]
        );

        $warehouseQuantities = [
            'ALM-BLD-20' => ['qty' => 2, 'unit' => 'Ball'],
            'ALM-MTH-12' => ['qty' => 3, 'unit' => 'Ball'],
            'ALM-REG-12' => ['qty' => 4, 'unit' => 'Ball'],
            'ALM-SLM-16' => ['qty' => 2, 'unit' => 'Ball'],
        ];

        $total = 0;

        foreach ($warehouseQuantities as $productCode => $seedQty) {
            $product = $products->get($productCode);
            if (! $product) {
                continue;
            }

            $qtyPack = $converter->normalize($product, $seedQty['qty'], $seedQty['unit']);
            $subtotal = $qtyPack * (int) $product->harga_beli;
            $total += $subtotal;

            PembelianProduct::updateOrCreate(
                ['pembelian_id' => $warehouseOrder->id, 'product_id' => $product->id],
                [
                    'harga_beli' => $product->harga_beli,
                    'qty' => $qtyPack,
                    'qty_diterima' => $qtyPack,
                    'subtotal' => $subtotal,
                    'expired_at' => null,
                    'serial_numbers' => null,
                ]
            );

            Stock::updateOrCreate(
                ['pembelian_id' => $warehouseOrder->id, 'product_id' => $product->id],
                [
                    'sku' => $product->code.'-BATCH-001',
                    'harga_beli' => $product->harga_beli,
                    'qty' => $qtyPack,
                    'subtotal' => $subtotal,
                    'expired_at' => null,
                    'location' => $product->lokasi,
                    'condition' => 'new',
                    'status' => 'available',
                ]
            );

            StockMovement::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'reference_type' => Pembelian::class,
                    'reference_id' => $warehouseOrder->id,
                    'type' => 'in',
                ],
                [
                    'user_id' => User::where('role', 'superadmin')->value('id'),
                    'qty_in' => $qtyPack,
                    'qty_out' => 0,
                    'balance' => $qtyPack,
                    'notes' => 'Seeder stok awal gudang utama untuk distribusi ALAMI',
                ]
            );
        }

        $warehouseOrder->update(['total' => $total]);

        PembelianTransaction::updateOrCreate(
            ['pembelian_id' => $warehouseOrder->id],
            [
                'payment_date' => null,
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'SEED-'.$warehouseOrderCode,
                'amount' => 0,
                'payment_history' => [],
                'status' => 'unpaid',
                'bukti_transfer' => null,
                'notes' => 'Piutang pembelian awal supplier ALAMI',
            ]
        );

        $this->call(WarehousePenjualanSeeder::class);
    }
}
