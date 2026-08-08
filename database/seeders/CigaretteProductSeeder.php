<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class CigaretteProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $supplier = Supplier::updateOrCreate(
            ['kode_supplier' => 'S00001'],
            [
                'name' => 'Pabrik ALAMI',
                'alamat' => 'Jl. Industri Tembakau No. 88, Yogyakarta',
                'no_telp' => '+622741110001',
                'po_number_prefix' => Supplier::DEFAULT_PO_NUMBER_FORMAT,
                'po_number_padding' => 5,
                'deadline_days' => [1, 4],
                'deadline_interval_weeks' => 1,
                'deadline_reference_date' => now()->startOfWeek(),
            ]
        );

        $additionalSuppliers = collect([
            [
                'kode_supplier' => 'S00002',
                'name' => 'PT Nusantara Tobacco',
                'alamat' => 'Jl. Raya Industri No. 12, Surakarta',
                'no_telp' => '+622716660002',
                'po_number_prefix' => 'PO-{YYYY}{MM}-{SUPPLIER_CODE}-{SEQ}',
                'po_number_padding' => 5,
            ],
            [
                'kode_supplier' => 'S00003',
                'name' => 'CV Mitra Kretek',
                'alamat' => 'Jl. Tembakau Sejahtera No. 7, Kudus',
                'no_telp' => '+622916660003',
                'po_number_prefix' => 'PO-{SEQ}-{SUPPLIER_CODE}-{YYYY}{MM}',
                'po_number_padding' => 4,
            ],
            [
                'kode_supplier' => 'S00004',
                'name' => 'PT Sumber Rasa Indonesia',
                'alamat' => 'Jl. Pabrik Makmur No. 45, Malang',
                'no_telp' => '+623416660004',
                'po_number_prefix' => 'PO-{SUPPLIER_CODE}-{YYYY}-{MM}-{SEQ}',
                'po_number_padding' => 6,
            ],
        ])->map(function (array $attributes) {
            return Supplier::updateOrCreate(
                ['kode_supplier' => $attributes['kode_supplier']],
                array_merge($attributes, [
                    'deadline_days' => [2, 5],
                    'deadline_interval_weeks' => 2,
                    'deadline_reference_date' => now()->startOfWeek(),
                ])
            );
        });

        $products = [
            [
                'code' => 'ALM-REG-12',
                'name' => 'ALAMI Kretek Original',
                'desc' => 'Rokok kretek reguler untuk distribusi gudang dan cabang.',
                'harga_beli' => 18000,
                'harga_jual' => 22000,
                'min_stock' => 150,
                'lokasi' => 'Gudang Utama Rak A1',
            ],
            [
                'code' => 'ALM-MTH-12',
                'name' => 'ALAMI Menthol',
                'desc' => 'Varian menthol untuk agen, canvas, dan cabang.',
                'harga_beli' => 19000,
                'harga_jual' => 23500,
                'min_stock' => 120,
                'lokasi' => 'Gudang Utama Rak A2',
            ],
            [
                'code' => 'ALM-SLM-16',
                'name' => 'ALAMI Slim',
                'desc' => 'Varian slim untuk jaringan retail modern.',
                'harga_beli' => 21000,
                'harga_jual' => 25500,
                'min_stock' => 100,
                'lokasi' => 'Gudang Utama Rak B1',
            ],
            [
                'code' => 'ALM-BLD-20',
                'name' => 'ALAMI Bold',
                'desc' => 'Varian bold untuk distribusi area kota dan kabupaten.',
                'harga_beli' => 23000,
                'harga_jual' => 27500,
                'min_stock' => 80,
                'lokasi' => 'Gudang Utama Rak B2',
            ],
        ];

        foreach ($products as $attributes) {
            $product = Product::updateOrCreate(
                ['code' => $attributes['code']],
                array_merge($attributes, [
                    'category_id' => $category->id,
                    'pic' => null,
                    'warna' => null,
                    'ukuran' => null,
                    'is_serialized' => false,
                    'status_produk' => 'sudah',
                    'status_produk_note' => null,
                    'satuan' => 'Pack',
                    'satuan_besar' => 'Slop',
                    'konversi_qty' => 10,
                    'satuan_terbesar' => 'Ball',
                    'konversi_qty_terbesar' => 25,
                ])
            );

            $product->suppliers()->syncWithoutDetaching(
                collect([$supplier])->merge($additionalSuppliers)->pluck('id')->all()
            );
        }
    }
}
