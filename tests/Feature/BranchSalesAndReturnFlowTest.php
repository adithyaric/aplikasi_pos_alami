<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\Penjualan;
use App\Models\PenjualanPayment;
use App\Models\PenjualanTotalAdjustment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Salesman;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSalesAndReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_sells_from_branch_stock_and_customer_return_adds_branch_stock(): void
    {
        $branch = Outlet::create([
            'name' => 'Cabang Sales Test',
            'jenis_outlet' => 'branch',
        ]);

        $shop = Outlet::create([
            'name' => 'Toko Sales Test',
            'jenis_outlet' => 'toko',
        ]);

        $salesUser = User::factory()->create([
            'role' => 'sales',
            'outlet_id' => $branch->id,
            'email' => 'sales-branch-flow@alami.test',
        ]);

        $salesman = Salesman::create([
            'name' => 'Sales Branch Flow',
            'alamat' => 'Jakarta',
            'no_telp' => '0800000001',
            'outlet_id' => $branch->id,
            'user_id' => $salesUser->id,
        ]);

        $category = Category::create([
            'name' => 'Rokok',
            'type' => 'product',
        ]);

        $product = Product::create([
            'code' => 'ALM-BR-001',
            'name' => 'ALAMI Branch Product',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 7000,
            'harga_jual' => 10000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
        ]);

        OwnerStock::create([
            'owner_id' => $branch->id,
            'product_id' => $product->id,
            'stock_id' => null,
            'qty' => 10,
            'sku' => 'BR-STOCK-001',
            'harga_beli' => 7000,
        ]);

        $saleResponse = $this->actingAs($salesUser)->post(route('penjualan.store'), [
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
                    'price' => '10.000',
                ],
            ],
        ]);

        $sale = Penjualan::firstOrFail();
        $saleResponse->assertRedirect(route('penjualan.show', $sale));

        $this->assertSame('branch', $sale->sale_channel);
        $this->assertSame('toko', $sale->buyer_type);
        $this->assertSame($branch->id, (int) $sale->outlet_id);
        $this->assertSame($salesman->id, (int) $sale->salesman_id);
        $this->assertStringStartsWith('INV-CBG-', $sale->code);
        $this->assertSame(8, (int) OwnerStock::where('owner_id', $branch->id)->where('product_id', $product->id)->sum('qty'));
        $this->assertDatabaseHas('owner_stock_movements', [
            'owner_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'out',
            'qty_out' => 2,
        ]);

        $returnResponse = $this->actingAs($salesUser)->post(route('refund.store'), [
            'code' => 'RTR-BR-001',
            'tanggal' => now()->toDateString(),
            'buyer_type' => 'toko',
            'buyer_id' => $shop->id,
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'Pack',
                    'price' => '10.000',
                    'alasan' => 'Retur toko',
                ],
            ],
        ]);

        $refund = Refund::firstOrFail();
        $sale->refresh();
        $returnResponse->assertRedirect(route('refund.show', $refund));

        $this->assertSame('branch_customer_return', $refund->return_scope);
        $this->assertSame(Refund::STATUS_APPROVED, $refund->status);
        $this->assertSame($sale->id, (int) $refund->applied_penjualan_id);
        $this->assertSame(10000, (int) $sale->total);
        $this->assertSame(9, (int) OwnerStock::where('owner_id', $branch->id)->where('product_id', $product->id)->sum('qty'));
        $this->assertSame(1, OwnerStockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->where('type', 'return_in')->count());
    }

    public function test_branch_admin_can_view_branch_sales_index_but_cannot_create_branch_sale(): void
    {
        $branch = Outlet::create([
            'name' => 'Cabang Admin Test',
            'jenis_outlet' => 'branch',
        ]);

        $shop = Outlet::create([
            'name' => 'Toko Admin Test',
            'jenis_outlet' => 'toko',
        ]);

        $adminCabang = User::factory()->create([
            'role' => 'admin-cabang',
            'outlet_id' => $branch->id,
            'email' => 'admin-cabang-penjualan@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok Admin Test',
            'type' => 'product',
        ]);

        $product = Product::create([
            'code' => 'ALM-BR-ADM-001',
            'name' => 'ALAMI Branch Admin Product',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 7000,
            'harga_jual' => 10000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
        ]);

        OwnerStock::create([
            'owner_id' => $branch->id,
            'product_id' => $product->id,
            'stock_id' => null,
            'qty' => 10,
            'sku' => 'BR-STOCK-ADM-001',
            'harga_beli' => 7000,
        ]);

        $this->actingAs($adminCabang)
            ->get(route('penjualan.branch-index'))
            ->assertOk();

        $this->actingAs($adminCabang)
            ->get(route('penjualan.create'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($adminCabang)
            ->post(route('penjualan.store'), [
                'sale_date' => now()->toDateString(),
                'buyer_type' => 'toko',
                'outlet_target_id' => $shop->id,
                'payment_type' => 'termin',
                'payment_status' => 'unpaid',
                'discount' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => 1,
                        'unit' => 'Pack',
                        'price' => '10.000',
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_branch_sale_can_be_viewed_and_paid_in_installments(): void
    {
        $branch = Outlet::create([
            'name' => 'Cabang Payment Test',
            'jenis_outlet' => 'branch',
        ]);

        $shop = Outlet::create([
            'name' => 'Toko Payment Test',
            'jenis_outlet' => 'toko',
        ]);

        $salesUser = User::factory()->create([
            'role' => 'sales',
            'outlet_id' => $branch->id,
            'email' => 'sales-branch-payment@alami.test',
        ]);

        Salesman::create([
            'name' => 'Sales Branch Payment',
            'alamat' => 'Jakarta',
            'no_telp' => '0800000002',
            'outlet_id' => $branch->id,
            'user_id' => $salesUser->id,
        ]);

        $category = Category::create([
            'name' => 'Rokok Payment Test',
            'type' => 'product',
        ]);

        $product = Product::create([
            'code' => 'ALM-BR-PAY-001',
            'name' => 'ALAMI Branch Payment Product',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 7000,
            'harga_jual' => 10000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
        ]);

        OwnerStock::create([
            'owner_id' => $branch->id,
            'product_id' => $product->id,
            'stock_id' => null,
            'qty' => 10,
            'sku' => 'BR-STOCK-PAY-001',
            'harga_beli' => 7000,
        ]);

        $this->actingAs($salesUser)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'toko',
            'outlet_target_id' => $shop->id,
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'items' => [[
                'product_id' => $product->id,
                'qty' => 2,
                'unit' => 'Pack',
                'price' => '10.000',
            ]],
        ]);

        $sale = Penjualan::firstOrFail();

        $this->actingAs($salesUser)
            ->get(route('penjualan.show', $sale))
            ->assertOk()
            ->assertSee('Pembayaran');

        $this->actingAs($salesUser)
            ->get(route('penjualan.pembayaran.edit', $sale))
            ->assertOk()
            ->assertSee('Input Pembayaran');

        $this->actingAs($salesUser)->put(route('penjualan.pembayaran.update', $sale), [
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'PAY-'.$sale->code.'-1',
            'amount' => 10000,
            'notes' => 'Cicilan pertama',
        ])->assertRedirect(route('penjualan.pembayaran.edit', $sale));

        $sale->refresh();
        $this->assertSame('partial', $sale->payment_status);
        $this->assertSame(10000.0, (float) $sale->paymentTransaction->amount);

        $this->actingAs($salesUser)->put(route('penjualan.pembayaran.update', $sale), [
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'payment_method' => 'cash',
            'payment_reference' => 'PAY-'.$sale->code.'-2',
            'amount' => 10000,
            'notes' => 'Cicilan kedua',
        ])->assertRedirect(route('penjualan.pembayaran.edit', $sale));

        $sale->refresh();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertCount(2, PenjualanPayment::firstOrFail()->payment_history ?? []);
    }

    public function test_admin_cabang_requests_branch_return_to_warehouse_and_superadmin_confirms_it(): void
    {
        $branch = Outlet::create([
            'name' => 'Cabang Retur Gudang',
            'jenis_outlet' => 'branch',
        ]);

        $adminCabang = User::factory()->create([
            'role' => 'admin-cabang',
            'outlet_id' => $branch->id,
            'email' => 'admin-cabang-retur-gudang@alami.test',
        ]);

        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-retur-gudang@alami.test',
        ]);

        $category = Category::create([
            'name' => 'Rokok Retur Gudang',
            'type' => 'product',
        ]);

        $product = Product::create([
            'code' => 'ALM-BR-RG-001',
            'name' => 'ALAMI Branch Return Product',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 7000,
            'harga_jual' => 10000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
        ]);

        $warehouseStock = Stock::create([
            'product_id' => $product->id,
            'sku' => 'WH-RG-001',
            'subtotal' => 70000,
            'harga_beli' => 7000,
            'qty' => 10,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $ownerStock = OwnerStock::create([
            'owner_id' => $branch->id,
            'product_id' => $product->id,
            'stock_id' => $warehouseStock->id,
            'qty' => 6,
            'sku' => 'BR-RG-001',
            'harga_beli' => 7000,
        ]);

        $warehouseSale = Penjualan::create([
            'code' => 'INV-WH-RG-001',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'outlet',
            'buyer_id' => $branch->id,
            'buyer_name' => $branch->name,
            'user_id' => $superadmin->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'discount' => 0,
            'total' => 60000,
        ]);

        $requestResponse = $this->actingAs($adminCabang)->post(route('refund.store'), [
            'code' => 'RTR-BR-WH-001',
            'tanggal' => now()->toDateString(),
            'return_scope' => 'warehouse_branch_return',
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit' => 'Pack',
                    'price' => '10.000',
                    'alasan' => 'Kirim balik ke gudang',
                ],
            ],
        ]);

        $refund = Refund::where('code', 'RTR-BR-WH-001')->firstOrFail();
        $requestResponse->assertRedirect(route('refund.show', $refund));

        $ownerStock->refresh();
        $warehouseStock->refresh();
        $warehouseSale->refresh();

        $this->assertSame('warehouse_branch_return', $refund->return_scope);
        $this->assertSame(Refund::STATUS_PENDING, $refund->status);
        $this->assertSame($warehouseSale->id, (int) $refund->applied_penjualan_id);
        $this->assertSame(6, (int) $ownerStock->qty);
        $this->assertSame(10, (int) $warehouseStock->qty);
        $this->assertSame(60000, (int) $warehouseSale->total);
        $this->assertSame(0, PenjualanTotalAdjustment::where('refund_id', $refund->id)->count());
        $this->assertSame(0, OwnerStockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->count());
        $this->assertSame(0, StockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->count());

        $approveResponse = $this->actingAs($superadmin)->post(route('refund.approve', $refund));

        $approveResponse->assertRedirect(route('refund.show', $refund));

        $refund->refresh();
        $ownerStock->refresh();
        $warehouseStock->refresh();
        $warehouseSale->refresh();

        $this->assertSame(Refund::STATUS_APPROVED, $refund->status);
        $this->assertSame($superadmin->id, (int) $refund->approved_by);
        $this->assertSame(4, (int) $ownerStock->qty);
        $this->assertSame(12, (int) $warehouseStock->qty);
        $this->assertSame(40000, (int) $warehouseSale->total);
        $this->assertSame(1, PenjualanTotalAdjustment::where('refund_id', $refund->id)->count());
        $this->assertSame(1, OwnerStockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->where('type', 'return_out')->count());
        $this->assertSame(1, StockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->where('type', 'in')->count());
    }

    public function test_sales_user_can_create_branch_customer_shop_via_modal_route(): void
    {
        $branch = Outlet::create([
            'name' => 'Cabang Modal Toko',
            'jenis_outlet' => 'branch',
        ]);

        $salesUser = User::factory()->create([
            'role' => 'sales',
            'outlet_id' => $branch->id,
            'email' => 'sales-modal-shop@alami.test',
        ]);

        Salesman::create([
            'name' => 'Sales Modal Toko',
            'alamat' => 'Jakarta',
            'no_telp' => '0800000009',
            'outlet_id' => $branch->id,
            'user_id' => $salesUser->id,
        ]);

        $response = $this->actingAs($salesUser)->post(route('outlet.store-shop'), [
            'name' => 'Toko Modal Baru',
            'alamat' => 'Jl. Modal Baru',
            'desc' => 'Customer popup branch sale',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'Toko Modal Baru',
                    'alamat' => 'Jl. Modal Baru',
                    'desc' => 'Customer popup branch sale',
                ],
            ]);

        $this->assertDatabaseHas('outlets', [
            'name' => 'Toko Modal Baru',
            'jenis_outlet' => 'toko',
            'alamat' => 'Jl. Modal Baru',
        ]);
    }
}
