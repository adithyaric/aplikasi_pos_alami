<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembelianUnitConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pembelian_submitted_in_slop_is_stored_in_pack(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-admin',
            'email' => 'warehouse-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $supplier = Supplier::create([
            'name' => 'Pabrik Test',
            'kode_supplier' => 'S99991',
            'alamat' => 'Yogyakarta',
            'no_telp' => '+622740009991',
        ]);

        $product = Product::create([
            'code' => 'ALM-TST-001',
            'name' => 'ALAMI Test',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 22000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 25,
        ]);

        $supplier->products()->attach($product->id);

        $response = $this->actingAs($user)->post(route('pembelian.store'), [
            'code' => 'PO-TST-0001',
            'supplier_id' => $supplier->id,
            'total' => '360000',
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'Slop',
                    'harga_beli' => '18000',
                    'subtotal' => '360000',
                ],
            ],
        ]);

        $response->assertRedirect(route('pembelian.index'));

        $this->assertDatabaseHas('pembelian_products', [
            'product_id' => $product->id,
            'qty' => 20,
            'subtotal' => 360000,
        ]);

        $this->assertDatabaseHas('stock_pembelians', [
            'product_id' => $product->id,
            'qty' => 20,
        ]);
    }
}
