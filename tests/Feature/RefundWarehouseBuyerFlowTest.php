<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\Penjualan;
use App\Models\PenjualanTotalAdjustment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundWarehouseBuyerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_refund_penjualan_for_agent_reduces_latest_unpaid_invoice_without_stock_mutation(): void
    {
        $admin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'refund-agent-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $agent = Agent::create([
            'name' => 'Agen Retur',
            'code' => 'AGN-RET-001',
            'termin_days' => 14,
            'credit_limit' => 1000000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'code' => 'ALM-RFD-001',
            'name' => 'ALAMI Retur Agen',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 18000,
            'harga_jual' => 220000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'sku' => 'RET-AGENT-001',
            'subtotal' => 720000,
            'harga_beli' => 18000,
            'qty' => 40,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $penjualan = Penjualan::create([
            'code' => 'PNJ-RET-001',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'buyer_name' => $agent->name,
            'user_id' => $admin->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'total' => 1100000,
        ]);

        $saleItem = $penjualan->items()->create([
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'qty' => 10,
            'qty_input' => 1,
            'unit' => 'Slop',
            'price' => 110000,
            'subtotal' => 1100000,
        ]);

        $saleItem->allocations()->create([
            'stock_id' => $stock->id,
            'qty' => 10,
        ]);

        $response = $this->actingAs($admin)->post(route('refund.store'), [
            'code' => 'RFD-AGENT-001',
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'tanggal' => now()->toDateString(),
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 5,
                    'unit' => 'Pack',
                    'price' => '110.000',
                    'alasan' => 'Kemasan rusak',
                ],
            ],
        ]);

        $refund = Refund::firstOrFail();
        $response->assertRedirect(route('refund.show', $refund));

        $stock->refresh();
        $penjualan->refresh();

        $this->assertSame('agent', $refund->buyer_type);
        $this->assertSame($agent->id, (int) $refund->buyer_id);
        $this->assertSame(40, (int) $stock->qty);
        $this->assertSame(550000, (int) $refund->total);
        $this->assertSame(550000, (int) $penjualan->total);
        $this->assertSame($penjualan->id, (int) $refund->applied_penjualan_id);
        $this->assertNull($refund->kas_id);
        $this->assertSame(0, StockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->count());
        $this->assertDatabaseHas('penjualan_total_adjustments', [
            'penjualan_id' => $penjualan->id,
            'refund_id' => $refund->id,
            'amount' => 550000,
            'total_before' => 1100000,
            'total_after' => 550000,
        ]);
    }

    public function test_refund_penjualan_for_cabang_reduces_owner_stock_and_restores_warehouse_stock(): void
    {
        $admin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'refund-outlet-admin@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $outlet = Outlet::create([
            'name' => 'Cabang Retur',
            'jenis_outlet' => 'branch',
            'alamat' => 'Yogyakarta',
        ]);

        $product = Product::create([
            'code' => 'ALM-RFD-002',
            'name' => 'ALAMI Retur Cabang',
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
            'sku' => 'RET-OUTLET-001',
            'subtotal' => 300000,
            'harga_beli' => 15000,
            'qty' => 20,
            'condition' => 'new',
            'status' => 'available',
        ]);

        OwnerStock::create([
            'owner_id' => $outlet->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'qty' => 10,
            'sku' => $stock->sku,
            'harga_beli' => $stock->harga_beli,
        ]);

        $penjualan = Penjualan::create([
            'code' => 'PNJ-RET-002',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'outlet',
            'buyer_id' => $outlet->id,
            'buyer_name' => $outlet->name,
            'user_id' => $admin->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'total' => 1800000,
        ]);

        $saleItem = $penjualan->items()->create([
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'qty' => 10,
            'qty_input' => 1,
            'unit' => 'Slop',
            'price' => 180000,
            'subtotal' => 1800000,
        ]);

        $saleItem->allocations()->create([
            'stock_id' => $stock->id,
            'qty' => 10,
        ]);

        $response = $this->actingAs($admin)->post(route('refund.store'), [
            'code' => 'RFD-OUTLET-001',
            'buyer_type' => 'outlet',
            'buyer_id' => $outlet->id,
            'tanggal' => now()->toDateString(),
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 4,
                    'unit' => 'Pack',
                    'price' => '180.000',
                    'alasan' => 'Barang tidak laku',
                ],
            ],
        ]);

        $refund = Refund::firstOrFail();
        $response->assertRedirect(route('refund.show', $refund));

        $stock->refresh();
        $ownerStock = OwnerStock::firstOrFail();
        $penjualan->refresh();

        $this->assertSame('outlet', $refund->buyer_type);
        $this->assertSame($outlet->id, (int) $refund->buyer_id);
        $this->assertSame($outlet->id, (int) $refund->outlet_id);
        $this->assertSame($penjualan->id, (int) $refund->applied_penjualan_id);
        $this->assertSame(720000, (int) $refund->total);
        $this->assertSame(1080000, (int) $penjualan->total);
        $this->assertSame(24, (int) $stock->qty);
        $this->assertSame(6, (int) $ownerStock->qty);
        $this->assertSame(1, StockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->count());
        $this->assertSame(1, OwnerStockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->count());
        $this->assertSame(1, PenjualanTotalAdjustment::where('refund_id', $refund->id)->count());
    }
}
