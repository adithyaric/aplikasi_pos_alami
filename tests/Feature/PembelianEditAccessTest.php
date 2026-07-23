<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\PembelianProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembelianEditAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowed_admin_roles_can_edit_pembelian_without_owner_approval_or_publish_block(): void
    {
        $supplier = Supplier::create([
            'name' => 'Supplier Editable PO',
            'kode_supplier' => 'S-EDIT',
        ]);

        foreach (['superadmin', 'admin-gudang', 'owner', 'staff-outlet'] as $index => $role) {
            $user = User::factory()->create([
                'role' => $role,
                'username' => 'pembelian-editor-'.$role,
                'email' => 'pembelian-editor-'.$role.'@alami.test',
            ]);

            $pembelian = Pembelian::create([
                'code' => 'PO-EDIT-00'.($index + 1),
                'supplier_id' => $supplier->id,
                'total' => 0,
                'is_published' => $index % 2 === 0,
                'owner_approval_status' => 'pending',
            ]);

            $response = $this->actingAs($user)->get(route('pembelian.edit', $pembelian));

            $response->assertOk();
            $response->assertSee('Edit PO');
            $response->assertDontSee('Admin gudang hanya bisa edit setelah ACC');
            $response->assertDontSee('sudah published');
        }
    }

    public function test_published_pembelian_update_reads_product_rows_from_request_input(): void
    {
        $user = User::factory()->create([
            'role' => 'admin-gudang',
            'username' => 'published-pembelian-editor',
            'email' => 'published-pembelian-editor@alami.test',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Kategori Published Edit',
            'type' => 'product',
        ]);

        $supplier = Supplier::create([
            'name' => 'Supplier Published Edit',
            'kode_supplier' => 'S-PUB-EDIT',
        ]);

        $product = Product::create([
            'code' => 'PUB-EDIT-001',
            'name' => 'Produk Published Edit',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 1000,
            'harga_jual' => 1500,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
            'satuan_besar' => 'BOX',
            'konversi_qty' => 1,
        ]);

        $supplier->products()->attach($product->id);

        $pembelian = Pembelian::create([
            'code' => 'PO-PUB-EDIT-001',
            'supplier_id' => $supplier->id,
            'total' => 1000,
            'is_published' => true,
            'owner_approval_status' => 'pending',
        ]);

        PembelianProduct::create([
            'pembelian_id' => $pembelian->id,
            'product_id' => $product->id,
            'harga_beli' => 1000,
            'qty' => 1,
            'subtotal' => 1000,
        ]);

        Stock::create([
            'pembelian_id' => $pembelian->id,
            'product_id' => $product->id,
            'harga_beli' => 1000,
            'qty' => 1,
            'subtotal' => 1000,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)->put(route('pembelian.update', $pembelian), [
            'code' => 'PO-PUB-EDIT-001',
            'supplier_id' => $supplier->id,
            'total' => '2,000',
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'BOX',
                    'harga_beli' => '1,000',
                    'subtotal' => '2,000',
                ],
            ],
        ]);

        $response->assertRedirect(route('pembelian.index'));
        $this->assertDatabaseHas('pembelian_products', [
            'pembelian_id' => $pembelian->id,
            'product_id' => $product->id,
            'qty' => 2,
            'subtotal' => 2000,
        ]);
        $this->assertDatabaseHas('stocks', [
            'pembelian_id' => $pembelian->id,
            'product_id' => $product->id,
            'qty' => 2,
            'subtotal' => 2000,
        ]);
    }
}
