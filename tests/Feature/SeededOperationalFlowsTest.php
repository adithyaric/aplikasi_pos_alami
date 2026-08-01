<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\PenjualanTotalAdjustment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundPembelian;
use App\Models\Salesman;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CurrentDistributionFlowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeededOperationalFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_role_access_matches_operational_boundaries(): void
    {
        $this->seed(CurrentDistributionFlowSeeder::class);

        $adminGudang = User::where('email', 'admin-gudang@alami.test')->firstOrFail();
        $adminCabang = User::where('email', 'alfreda.branch@alami.test')->firstOrFail();
        $sales = User::where('email', 'sales-jogja-1@alami.test')->firstOrFail();

        $this->actingAs($adminGudang)
            ->get(route('pembelian.create'))
            ->assertOk();

        $this->actingAs($adminCabang)
            ->get(route('pembelian.create'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($sales)
            ->get(route('pembelian.create'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($adminCabang)
            ->get(route('refund.create', ['return_scope' => 'warehouse_branch_return']))
            ->assertOk();

        $this->actingAs($adminCabang)
            ->get(route('refundPembelian.create', ['type' => 'outlet_ke_gudang']))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($adminCabang)
            ->get(route('penjualan.branch-index'))
            ->assertOk();

        $this->actingAs($adminCabang)
            ->get(route('penjualan.create'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($sales)
            ->get(route('refundPembelian.create', ['type' => 'outlet_ke_gudang']))
            ->assertRedirect(route('dashboard'));
    }

    public function test_seeded_admin_can_create_pembelian_receive_supplier_return_sale_and_sales_return(): void
    {
        $this->seed(CurrentDistributionFlowSeeder::class);

        $adminGudang = User::where('email', 'admin-gudang@alami.test')->firstOrFail();
        $supplier = Supplier::where('kode_supplier', 'S00001')->firstOrFail();
        $product = Product::where('code', 'ALM-MTH-12')->firstOrFail();
        $agent = Agent::where('code', 'AGN-002')->firstOrFail();
        $purchaseSubtotal = (int) $product->harga_beli * 10;

        $purchaseResponse = $this->actingAs($adminGudang)->post(route('pembelian.store'), [
            'code' => 'PO-DEMO-FLOW-001',
            'customer_po' => 'CUSTOMER-PO-DEMO-001',
            'supplier_id' => $supplier->id,
            'total' => $purchaseSubtotal,
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'Slop',
                    'harga_beli' => (int) $product->harga_beli,
                    'subtotal' => $purchaseSubtotal,
                ],
            ],
        ]);

        $purchaseResponse->assertRedirect(route('pembelian.index'));

        $pembelian = \App\Models\Pembelian::where('code', 'PO-DEMO-FLOW-001')->firstOrFail();

        $receiveResponse = $this->actingAs($adminGudang)->post(route('pembelian.store-penerimaan', $pembelian), [
            'code_gr' => 'GR-DEMO-FLOW-001',
            'receipt_date' => now()->toDateString(),
            'receipt_pic' => 'Admin Gudang Demo',
            'receipt_status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty_diterima' => 1,
                    'unit' => 'Slop',
                ],
            ],
        ]);

        $receiveResponse->assertRedirect(route('pembelian.penerimaan', $pembelian));
        $pembelian->refresh();
        $this->assertTrue((bool) $pembelian->is_published);

        $receivedStock = Stock::where('pembelian_id', $pembelian->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $this->assertSame(10, (int) $receivedStock->qty);

        $purchaseReturnResponse = $this->actingAs($adminGudang)->post(route('refundPembelian.store'), [
            'code' => 'RTR-DEMO-PO-001',
            'tanggal' => now()->toDateString(),
            'type' => 'gudang_ke_supplier',
            'return_mode' => 'cash_refund',
            'supplier_id' => $supplier->id,
            'selected_rows' => ['0'],
            'product' => [
                [
                    'product_id' => $product->id,
                    'stock_id' => $receivedStock->id,
                    'qty' => 1,
                    'harga' => (int) $product->harga_beli,
                    'alasan' => 'Barang cacat demo',
                ],
            ],
        ]);

        $purchaseReturnResponse->assertRedirect(route('refundPembelian.index'));

        $refundPembelian = RefundPembelian::where('code', 'RTR-DEMO-PO-001')->firstOrFail();
        $receivedStock->refresh();
        $this->assertSame('retur', $refundPembelian->status);
        $this->assertSame(9, (int) $receivedStock->qty);

        $itemIds = $refundPembelian->refundPembelianItems()->pluck('id')->implode(',');
        $receiveReturnResponse = $this->actingAs($adminGudang)->post(route('refundPembelian.terima', $refundPembelian), [
            'items' => [
                $itemIds => [
                    'resolution' => 'barang',
                ],
            ],
        ]);

        $receiveReturnResponse->assertRedirect(route('refundPembelian.show', $refundPembelian));
        $refundPembelian->refresh();
        $receivedStock->refresh();
        $this->assertSame('complete', $refundPembelian->status);
        $this->assertSame(10, (int) $receivedStock->qty);

        $saleResponse = $this->actingAs($adminGudang)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'agent',
            'agent_id' => $agent->id,
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'Pack',
                    'price' => (int) $product->harga_jual,
                ],
            ],
        ]);

        $sale = Penjualan::warehouseSales()
            ->where('buyer_type', 'agent')
            ->where('buyer_id', $agent->id)
            ->latest('id')
            ->firstOrFail();
        $saleResponse->assertRedirect(route('penjualan.show', $sale));

        $returnResponse = $this->actingAs($adminGudang)->post(route('refund.store'), [
            'code' => 'RTR-DEMO-SALES-001',
            'tanggal' => now()->toDateString(),
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'Pack',
                    'price' => (int) $product->harga_jual,
                    'alasan' => 'Retur penjualan demo',
                ],
            ],
        ]);

        $refund = Refund::where('code', 'RTR-DEMO-SALES-001')->firstOrFail();
        $sale->refresh();
        $returnResponse->assertRedirect(route('refund.show', $refund));
        $this->assertSame($sale->id, (int) $refund->applied_penjualan_id);
        $this->assertSame((int) $product->harga_jual, (int) $sale->total);
        $this->assertSame(1, PenjualanTotalAdjustment::where('refund_id', $refund->id)->count());
    }

    public function test_seeded_branch_admin_and_sales_can_run_branch_stock_sale_and_return_flows(): void
    {
        $this->seed(CurrentDistributionFlowSeeder::class);

        $adminCabang = User::where('email', 'alfreda.branch@alami.test')->firstOrFail();
        $sales = User::where('email', 'sales-jogja-1@alami.test')->firstOrFail();
        $superadmin = User::where('email', 'superadmin@mailinator.com')->firstOrFail();
        $salesman = Salesman::where('user_id', $sales->id)->firstOrFail();
        $branch = Outlet::findOrFail($adminCabang->outlet_id);
        $shop = Outlet::shops()->orderBy('id')->firstOrFail();
        $product = Product::where('code', 'ALM-REG-12')->firstOrFail();
        $ownerStock = OwnerStock::where('owner_id', $branch->id)
            ->where('product_id', $product->id)
            ->whereNotNull('stock_id')
            ->where('qty', '>', 4)
            ->firstOrFail();
        $warehouseStock = Stock::findOrFail($ownerStock->stock_id);
        $branchWarehouseInvoice = Penjualan::warehouseSales()
            ->where('buyer_type', 'outlet')
            ->where('buyer_id', $branch->id)
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            })
            ->latest('id')
            ->firstOrFail();
        $branchQtyBeforeReturnToWarehouse = (int) $ownerStock->qty;
        $warehouseQtyBeforeReturnToWarehouse = (int) $warehouseStock->qty;
        $branchInvoiceTotalBeforeReturn = (int) $branchWarehouseInvoice->total;

        $branchReturnResponse = $this->actingAs($adminCabang)->post(route('refund.store'), [
            'code' => 'RTR-CABANG-DEMO-REQ-001',
            'tanggal' => now()->toDateString(),
            'return_scope' => 'warehouse_branch_return',
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'Pack',
                    'price' => (int) $product->harga_jual,
                    'alasan' => 'Retur stok cabang demo',
                ],
            ],
        ]);

        $branchReturn = Refund::where('code', 'RTR-CABANG-DEMO-REQ-001')->firstOrFail();
        $branchReturnResponse->assertRedirect(route('refund.show', $branchReturn));
        $ownerStock->refresh();
        $warehouseStock->refresh();
        $branchWarehouseInvoice->refresh();

        $this->assertSame(Refund::STATUS_PENDING, $branchReturn->status);
        $this->assertSame('warehouse_branch_return', $branchReturn->return_scope);
        $this->assertSame($branch->id, (int) $branchReturn->buyer_id);
        $this->assertSame($branchQtyBeforeReturnToWarehouse, (int) $ownerStock->qty);
        $this->assertSame($warehouseQtyBeforeReturnToWarehouse, (int) $warehouseStock->qty);
        $this->assertSame($branchInvoiceTotalBeforeReturn, (int) $branchWarehouseInvoice->total);

        $approveReturnResponse = $this->actingAs($superadmin)->post(route('refund.approve', $branchReturn));

        $approveReturnResponse->assertRedirect(route('refund.show', $branchReturn));
        $branchReturn->refresh();
        $ownerStock->refresh();
        $warehouseStock->refresh();
        $branchWarehouseInvoice->refresh();

        $this->assertSame(Refund::STATUS_APPROVED, $branchReturn->status);
        $this->assertSame($branchQtyBeforeReturnToWarehouse - 1, (int) $ownerStock->qty);
        $this->assertSame($warehouseQtyBeforeReturnToWarehouse + 1, (int) $warehouseStock->qty);
        $this->assertSame($branchInvoiceTotalBeforeReturn - (int) $product->harga_jual, (int) $branchWarehouseInvoice->total);

        $branchQtyBeforeSale = (int) OwnerStock::where('owner_id', $branch->id)
            ->where('product_id', $product->id)
            ->sum('qty');

        $saleResponse = $this->actingAs($sales)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'toko',
            'outlet_target_id' => $shop->id,
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'Pack',
                    'price' => (int) $product->harga_jual,
                ],
            ],
        ]);

        $sale = Penjualan::branchSales()
            ->where('outlet_id', $branch->id)
            ->where('salesman_id', $salesman->id)
            ->latest('id')
            ->firstOrFail();
        $saleResponse->assertRedirect(route('penjualan.show', $sale));
        $this->assertSame($branchQtyBeforeSale - 2, (int) OwnerStock::where('owner_id', $branch->id)
            ->where('product_id', $product->id)
            ->sum('qty'));

        $returnResponse = $this->actingAs($sales)->post(route('refund.store'), [
            'code' => 'RTR-CUSTOMER-DEMO-001',
            'tanggal' => now()->toDateString(),
            'buyer_type' => 'toko',
            'buyer_id' => $shop->id,
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'Pack',
                    'price' => (int) $product->harga_jual,
                    'alasan' => 'Retur toko demo',
                ],
            ],
        ]);

        $refund = Refund::where('code', 'RTR-CUSTOMER-DEMO-001')->firstOrFail();
        $sale->refresh();
        $returnResponse->assertRedirect(route('refund.show', $refund));

        $this->assertSame('branch_customer_return', $refund->return_scope);
        $this->assertSame(Refund::STATUS_APPROVED, $refund->status);
        $this->assertSame($sale->id, (int) $refund->applied_penjualan_id);
        $this->assertSame((int) $product->harga_jual, (int) $sale->total);
        $this->assertSame($branchQtyBeforeSale - 1, (int) OwnerStock::where('owner_id', $branch->id)
            ->where('product_id', $product->id)
            ->sum('qty'));
    }
}
