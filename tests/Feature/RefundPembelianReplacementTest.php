<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Pembelian;
use App\Models\Product;
use App\Models\RefundPembelian;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundPembelianReplacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacement_return_is_recorded_without_reducing_stock(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'refund-admin',
            'email' => 'refund-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $supplier = Supplier::create([
            'name' => 'Pabrik Test',
            'kode_supplier' => 'S99992',
            'alamat' => 'Yogyakarta',
            'no_telp' => '+622740009992',
        ]);

        $product = Product::create([
            'code' => 'ALM-TST-RET-001',
            'name' => 'ALAMI Retur Test',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 22000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        $pembelian = Pembelian::create([
            'code' => 'PO-TST-RET-0001',
            'supplier_id' => $supplier->id,
            'total' => 720000,
            'is_published' => true,
            'owner_approval_status' => 'approved',
        ]);

        $stock = Stock::create([
            'pembelian_id' => $pembelian->id,
            'product_id' => $product->id,
            'sku' => 'RET-BATCH-001',
            'subtotal' => 720000,
            'harga_beli' => 18000,
            'qty' => 40,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)->post(route('refundPembelian.store'), [
            'code' => 'RTR00001',
            'tanggal' => now()->toDateString(),
            'type' => 'gudang_ke_supplier',
            'return_mode' => 'replacement',
            'supplier_id' => $supplier->id,
            'selected_rows' => ['0'],
            'product' => [
                [
                    'product_id' => $product->id,
                    'stock_id' => $stock->id,
                    'qty' => 20,
                    'harga' => 18000,
                    'alasan' => 'Kemasan penyok',
                ],
            ],
        ]);

        $response->assertRedirect(route('refundPembelian.index'));

        $stock->refresh();
        $refund = RefundPembelian::firstOrFail();

        $this->assertSame(40, $stock->qty);
        $this->assertSame('replacement', $refund->return_mode);
        $this->assertSame('complete', $refund->status);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => RefundPembelian::class,
            'reference_id' => $refund->id,
            'type' => 'adjustment',
            'qty_in' => 0,
            'qty_out' => 0,
        ]);
    }
}
