<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CigaretteProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $supplierAttributes = [
            [
                'kode_supplier' => 'S00001',
                'name' => 'PR Tunas Mandiri',
                'alamat' => 'Jl. Industri Tembakau No. 88, Yogyakarta',
                'no_telp' => '+622741110001',
                'po_number_prefix' => Supplier::DEFAULT_PO_NUMBER_FORMAT,
                'po_number_padding' => 5,
                'template' => 'xlsx',
            ],
            [
                'kode_supplier' => 'S00002',
                'name' => 'Margantara Jaya Corp',
                'alamat' => 'Jl. Margantara Jaya No. 12, Yogyakarta',
                'no_telp' => '+622716660002',
                'po_number_prefix' => Supplier::DEFAULT_PO_NUMBER_FORMAT,
                'po_number_padding' => 5,
                'template' => 'docx',
            ],
        ];

        // The operational setup has exactly these two seeded suppliers. Retire
        // the two suppliers from the previous four-supplier demo setup without
        // touching suppliers created by an administrator.
        Supplier::whereIn('kode_supplier', ['S00003', 'S00004'])->get()->each->delete();

        $suppliers = collect($supplierAttributes)->map(function (array $attributes) {
            $templateType = $attributes['template'];
            unset($attributes['template']);

            $supplier = Supplier::withTrashed()->firstOrNew([
                'kode_supplier' => $attributes['kode_supplier'],
            ]);
            $supplier->fill(array_merge($attributes, [
                'deadline_days' => [1, 4],
                'deadline_interval_weeks' => 1,
                'deadline_reference_date' => now()->startOfWeek(),
            ]));
            if ($supplier->trashed()) {
                $supplier->restore();
            }
            $supplier->save();

            $source = base_path('template_alami_pembelian.'.$templateType);
            $path = 'templates/documents/suppliers/'.$supplier->id.'/template-po-'.$supplier->id.'.'.$templateType;
            if (is_file($source)) {
                Storage::disk('public')->put($path, file_get_contents($source));
                $supplier->update(['po_template' => $path]);
            }

            return $supplier->fresh();
        });

        $supplier = $suppliers->firstWhere('kode_supplier', 'S00001');

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

            $product->suppliers()->sync(
                $suppliers->pluck('id')->all()
            );
        }
    }
}
