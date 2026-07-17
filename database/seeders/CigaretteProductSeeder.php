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
                'deadline_days' => [1, 4],
                'deadline_interval_weeks' => 1,
                'deadline_reference_date' => now()->startOfWeek(),
            ]
        );

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

            $product->suppliers()->syncWithoutDetaching([$supplier->id]);
        }
    }
}
