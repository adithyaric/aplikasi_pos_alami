<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\PenjualanPayment;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehousePenjualanFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_sale_create_page_renders(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-sales-page-admin',
            'email' => 'warehouse-sales-page-admin@alami.test',
        ]);

        $response = $this->actingAs($user)->get(route('penjualan.create'));

        $response->assertOk();
        $response->assertSee('Tambah Penjualan');
        $response->assertSee('Cek Barang');
        $response->assertSee('type="hidden" id="payment_type" name="payment_type" value="termin"', false);
        $response->assertSee('type="hidden" id="payment_status" name="payment_status" value="unpaid"', false);
        $response->assertDontSee('Tipe Pembayaran');
        $response->assertDontSee('Status Pembayaran');
    }

    public function test_warehouse_sale_edit_page_renders(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-sales-edit-page-admin',
            'email' => 'warehouse-sales-edit-page-admin@alami.test',
        ]);

        $penjualan = Penjualan::create([
            'code' => 'PNJ00099',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_id' => 1,
            'buyer_name' => 'Agen Dummy',
            'user_id' => $user->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'cash',
            'payment_status' => 'paid',
            'discount' => 0,
            'total' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('penjualan.edit', $penjualan));

        $response->assertOk();
        $response->assertSee('Edit Penjualan');
        $response->assertSee('Cek Barang');
        $response->assertSee('type="hidden" id="payment_type" name="payment_type" value="cash"', false);
        $response->assertSee('type="hidden" id="payment_status" name="payment_status" value="paid"', false);
        $response->assertDontSee('Tipe Pembayaran');
        $response->assertDontSee('Status Pembayaran');
    }

    public function test_warehouse_sale_defaults_payment_type_and_status_when_not_submitted(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-sales-default-payment-admin',
            'email' => 'warehouse-sales-default-payment-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $agent = Agent::create([
            'name' => 'Agen Default Payment',
            'code' => 'AGN-DEFAULT-001',
            'termin_days' => 14,
            'credit_limit' => 5000000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'code' => 'ALM-SALE-DEFAULT-001',
            'name' => 'ALAMI Default Payment Test',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 220000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'sku' => 'SALE-DEFAULT-BATCH-001',
            'subtotal' => 900000,
            'harga_beli' => 18000,
            'qty' => 50,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'agent',
            'agent_id' => $agent->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'Slop',
                    'price' => '220000',
                ],
            ],
        ]);

        $penjualan = Penjualan::firstOrFail();

        $response->assertRedirect(route('penjualan.show', $penjualan));
        $this->assertSame('termin', $penjualan->payment_type);
        $this->assertSame('unpaid', $penjualan->payment_status);
    }

    public function test_warehouse_sale_to_agent_reduces_stock_and_stores_base_unit_qty(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-sales-admin',
            'email' => 'warehouse-sales-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $agent = Agent::create([
            'name' => 'Agen Test',
            'code' => 'AGN-001',
            'termin_days' => 14,
            'credit_limit' => 5000000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'code' => 'ALM-SALE-001',
            'name' => 'ALAMI Sale Test',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 220000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 25,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'sku' => 'SALE-BATCH-001',
            'subtotal' => 900000,
            'harga_beli' => 18000,
            'qty' => 50,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'agent',
            'agent_id' => $agent->id,
            'payment_type' => 'termin',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'Slop',
                    'price' => '220000',
                ],
            ],
        ]);

        $penjualan = Penjualan::firstOrFail();

        $response->assertRedirect(route('penjualan.show', $penjualan));

        $stock->refresh();

        $this->assertSame('warehouse', $penjualan->sale_channel);
        $this->assertSame('agent', $penjualan->buyer_type);
        $this->assertSame($agent->id, $penjualan->buyer_id);
        $this->assertSame(4400000, (int) $penjualan->total);
        $this->assertSame(30, (int) $stock->qty);

        $this->assertDatabaseHas('penjualan_items', [
            'penjualan_id' => $penjualan->id,
            'product_id' => $product->id,
            'qty' => 20,
            'qty_input' => 2,
            'unit' => 'Slop',
            'price' => 220000,
            'subtotal' => 4400000,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => Penjualan::class,
            'reference_id' => $penjualan->id,
            'type' => 'out',
            'qty_out' => 20,
        ]);
    }

    public function test_warehouse_sale_last_price_endpoint_returns_latest_buyer_price(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-sales-last-price-admin',
            'email' => 'warehouse-sales-last-price-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $agent = Agent::create([
            'name' => 'Agen Histori Harga',
            'code' => 'AGN-HST-001',
            'termin_days' => 14,
            'credit_limit' => 5000000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'code' => 'ALM-HST-001',
            'name' => 'ALAMI Histori Harga',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 220000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        $penjualan = Penjualan::create([
            'code' => 'PNJ-HST-001',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'buyer_name' => $agent->name,
            'user_id' => $user->id,
            'sale_date' => now()->subDay()->toDateString(),
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'total' => 250000,
        ]);

        $penjualan->items()->create([
            'product_id' => $product->id,
            'qty' => 10,
            'qty_input' => 1,
            'unit' => 'Slop',
            'price' => 250000,
            'subtotal' => 250000,
        ]);

        $response = $this->actingAs($user)->get(route('penjualan.last-price', [
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'product_id' => $product->id,
        ]));

        $response->assertOk()
            ->assertJson([
                'price' => 250000,
            ]);
    }

    public function test_warehouse_sale_to_cabang_creates_owner_stock(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-branch-sales-admin',
            'email' => 'warehouse-branch-sales-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $outlet = Outlet::create([
            'name' => 'Cabang Test',
            'jenis_outlet' => 'branch',
            'alamat' => 'Yogyakarta',
        ]);

        $product = Product::create([
            'code' => 'ALM-SALE-002',
            'name' => 'ALAMI Cabang Test',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 15000,
            'harga_jual' => 180000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'sku' => 'SALE-BATCH-002',
            'subtotal' => 450000,
            'harga_beli' => 15000,
            'qty' => 30,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'outlet',
            'outlet_target_id' => $outlet->id,
            'payment_type' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'Slop',
                    'price' => '180000',
                ],
            ],
        ]);

        $penjualan = Penjualan::firstOrFail();

        $response->assertRedirect(route('penjualan.show', $penjualan));

        $stock->refresh();
        $ownerStock = OwnerStock::firstOrFail();

        $this->assertSame('outlet', $penjualan->buyer_type);
        $this->assertSame(1800000, (int) $penjualan->total);
        $this->assertSame(20, (int) $stock->qty);
        $this->assertSame($outlet->id, (int) $ownerStock->owner_id);
        $this->assertSame($product->id, (int) $ownerStock->product_id);
        $this->assertSame($stock->id, (int) $ownerStock->stock_id);
        $this->assertSame(10, (int) $ownerStock->qty);
        $this->assertSame('SALE-BATCH-002', $ownerStock->sku);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => Penjualan::class,
            'reference_id' => $penjualan->id,
            'type' => 'out',
            'qty_out' => 10,
        ]);
    }

    public function test_warehouse_sale_can_be_updated_and_reconciles_stock(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-sales-update-admin',
            'email' => 'warehouse-sales-update-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $agent = Agent::create([
            'name' => 'Agen Update',
            'code' => 'AGN-UPD-001',
            'termin_days' => 14,
            'credit_limit' => 5000000,
            'is_active' => true,
        ]);

        $outlet = Outlet::create([
            'name' => 'Cabang Update',
            'jenis_outlet' => 'branch',
            'alamat' => 'Yogyakarta',
        ]);

        $productA = Product::create([
            'code' => 'ALM-UPD-001',
            'name' => 'ALAMI Update A',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 220000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        $productB = Product::create([
            'code' => 'ALM-UPD-002',
            'name' => 'ALAMI Update B',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 15000,
            'harga_jual' => 180000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        $stockA = Stock::create([
            'product_id' => $productA->id,
            'sku' => 'UPD-BATCH-001',
            'subtotal' => 1080000,
            'harga_beli' => 18000,
            'qty' => 60,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $stockB = Stock::create([
            'product_id' => $productB->id,
            'sku' => 'UPD-BATCH-002',
            'subtotal' => 600000,
            'harga_beli' => 15000,
            'qty' => 40,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $createResponse = $this->actingAs($user)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'agent',
            'agent_id' => $agent->id,
            'payment_type' => 'termin',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'qty' => 2,
                    'unit' => 'Slop',
                    'price' => '220000',
                ],
            ],
        ]);

        $penjualan = Penjualan::firstOrFail();
        $createResponse->assertRedirect(route('penjualan.show', $penjualan));

        $updateResponse = $this->actingAs($user)->put(route('penjualan.update', $penjualan), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'outlet',
            'outlet_target_id' => $outlet->id,
            'payment_type' => 'cash',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'qty' => 1,
                    'unit' => 'Slop',
                    'price' => '220000',
                ],
                [
                    'product_id' => $productB->id,
                    'qty' => 2,
                    'unit' => 'Slop',
                    'price' => '180000',
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('penjualan.show', $penjualan));

        $penjualan->refresh();
        $stockA->refresh();
        $stockB->refresh();

        $this->assertSame('outlet', $penjualan->buyer_type);
        $this->assertSame($outlet->id, $penjualan->buyer_id);
        $this->assertSame(50, (int) $stockA->qty);
        $this->assertSame(20, (int) $stockB->qty);
        $this->assertSame(5800000, (int) $penjualan->total);

        $this->assertDatabaseCount('penjualan_items', 2);
        $this->assertDatabaseHas('owner_stocks', [
            'owner_id' => $outlet->id,
            'product_id' => $productA->id,
            'stock_id' => $stockA->id,
            'qty' => 10,
        ]);
        $this->assertDatabaseHas('owner_stocks', [
            'owner_id' => $outlet->id,
            'product_id' => $productB->id,
            'stock_id' => $stockB->id,
            'qty' => 20,
        ]);
        $this->assertSame(2, StockMovement::where('reference_type', Penjualan::class)
            ->where('reference_id', $penjualan->id)
            ->count());
        $this->assertDatabaseCount('penjualan_item_allocations', 2);
    }

    public function test_warehouse_sale_payment_history_can_be_recorded(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'warehouse-sales-payment-admin',
            'email' => 'warehouse-sales-payment-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $agent = Agent::create([
            'name' => 'Agen Pembayaran',
            'code' => 'AGN-PAY-001',
            'termin_days' => 14,
            'credit_limit' => 5000000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'code' => 'ALM-PAY-001',
            'name' => 'ALAMI Payment Test',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 220000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'sku' => 'PAY-BATCH-001',
            'subtotal' => 900000,
            'harga_beli' => 18000,
            'qty' => 50,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $createResponse = $this->actingAs($user)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'agent',
            'agent_id' => $agent->id,
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'Slop',
                    'price' => '220000',
                ],
            ],
        ]);

        $penjualan = Penjualan::firstOrFail();
        $createResponse->assertRedirect(route('penjualan.show', $penjualan));

        $response = $this->actingAs($user)->put(route('penjualan.pembayaran.update', $penjualan), [
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'PAY-'.$penjualan->code,
            'amount' => 200000,
            'notes' => 'Pembayaran pertama',
        ]);

        $response->assertRedirect(route('penjualan.pembayaran.edit', $penjualan));

        $penjualan->refresh();
        $payment = PenjualanPayment::firstOrFail();

        $this->assertSame('partial', $penjualan->payment_status);
        $this->assertSame('partial', $payment->status);
        $this->assertSame(200000.0, (float) $payment->amount);
        $this->assertCount(1, $payment->payment_history ?? []);
        $this->assertSame('bank_transfer', $payment->payment_history[0]['payment_method']);
    }
}
