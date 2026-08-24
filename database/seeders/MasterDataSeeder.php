<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CustomerPo;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Reproducible baseline for the client-owned master data.
 *
 * The seeder is deliberately additive: existing rows are never overwritten,
 * so it is safe to run after importing a client backup.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['KRETEK', 'FILTER'])->mapWithKeys(function (string $name): array {
            $category = Category::withTrashed()->firstOrCreate(['name' => $name]);

            if ($category->trashed()) {
                $category->restore();
            }

            return [$name => $category];
        });

        $supplier = Supplier::withTrashed()->firstOrCreate(
            ['kode_supplier' => 'S00001'],
            [
                'name' => 'Produksi',
                'alamat' => 'Pacitan',
                'no_telp' => '0891234532',
                'email' => null,
            ]
        );

        if ($supplier->trashed()) {
            $supplier->restore();
        }

        foreach ([
            [
                'code' => 'A0001',
                'name' => 'ALAMI',
                'category_id' => $categories['KRETEK']->id,
                'satuan' => 'Pack',
                'satuan_besar' => 'SLOP',
                'konversi_qty' => 10,
                'satuan_terbesar' => 'Ball',
                'konversi_qty_terbesar' => 20,
                'harga_beli' => 7000,
                'harga_jual' => null,
            ],
            [
                'code' => 'Quam sunt totam anim',
                'name' => 'Julian Maxwell',
                'category_id' => $categories['FILTER']->id,
                'satuan' => 'Ut nobis voluptates',
                'satuan_besar' => 'Aut culpa autem anim',
                'konversi_qty' => 16,
                'satuan_terbesar' => 'jlds',
                'konversi_qty_terbesar' => 10,
                'harga_beli' => 0,
                'harga_jual' => null,
            ],
            [
                'code' => 'Aspernatur eiusmod p',
                'name' => 'Karyn Campos',
                'category_id' => $categories['FILTER']->id,
                'satuan' => 'Molestias accusantiu',
                'satuan_besar' => 'Vel expedita eos po',
                'konversi_qty' => 390,
                'satuan_terbesar' => 'ball',
                'konversi_qty_terbesar' => 497,
                'harga_beli' => 0,
                'harga_jual' => null,
            ],
        ] as $productAttributes) {
            $product = Product::withTrashed()->firstOrCreate(
                ['code' => $productAttributes['code']],
                array_merge($productAttributes, [
                    'min_stock' => 0,
                    'status_produk' => 'sudah',
                    'is_serialized' => false,
                ])
            );

            if ($product->trashed()) {
                $product->restore();
            }

            $product->suppliers()->syncWithoutDetaching([$supplier->id]);
        }

        foreach ([
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
        ] as $customerPo) {
            CustomerPo::withTrashed()->firstOrCreate(
                ['name' => $customerPo['name']],
                $customerPo
            );
        }
    }
}
