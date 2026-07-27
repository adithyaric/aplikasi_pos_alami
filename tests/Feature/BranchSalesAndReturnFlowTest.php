<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Salesman;
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
        $this->assertSame($sale->id, (int) $refund->applied_penjualan_id);
        $this->assertSame(10000, (int) $sale->total);
        $this->assertSame(9, (int) OwnerStock::where('owner_id', $branch->id)->where('product_id', $product->id)->sum('qty'));
        $this->assertSame(1, OwnerStockMovement::where('reference_type', Refund::class)->where('reference_id', $refund->id)->where('type', 'return_in')->count());
    }
}
