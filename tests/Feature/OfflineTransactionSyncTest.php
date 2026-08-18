<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Category;
use App\Models\CustomerPo;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Outlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineTransactionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_offline_page_is_disabled(): void
    {
        $this->get('/offline')->assertNotFound();
    }

    public function test_authenticated_offline_clients_can_refresh_the_current_csrf_token(): void
    {
        $user = User::factory()->create([
            'role' => 'sales',
            'username' => 'offline-csrf-sales',
            'email' => 'offline-csrf-sales@alami.test',
        ]);

        $response = $this->actingAs($user)
            ->get(route('offline.csrf-token'));

        $response->assertOk()
            ->assertJsonStructure(['token']);
        $this->assertNotSame('', (string) $response->json('token'));
    }

    public function test_sales_create_requires_customer_toko_before_an_offline_sale_can_be_stored(): void
    {
        $branch = Outlet::create([
            'name' => 'Offline Sales Branch',
            'jenis_outlet' => 'branch',
        ]);
        $shop = Outlet::create([
            'name' => 'Offline Sales Shop',
            'jenis_outlet' => 'toko',
        ]);
        $user = User::factory()->create([
            'role' => 'sales',
            'outlet_id' => $branch->id,
            'username' => 'offline-sales-validation',
            'email' => 'offline-sales-validation@alami.test',
        ]);

        $this->actingAs($user)
            ->get(route('penjualan.create'))
            ->assertOk()
            ->assertSee('data-branch-sale="true"', false)
            ->assertSee('Customer/Toko')
            ->assertSee('name="outlet_target_id"', false)
            ->assertSee('required', false);

        $this->actingAs($user)
            ->post(route('penjualan.store'), [
                'sale_date' => now()->toDateString(),
                'buyer_type' => 'toko',
                'payment_type' => 'termin',
                'payment_status' => 'unpaid',
                'items' => [],
            ])
            ->assertSessionHasErrors('outlet_target_id');

        $this->assertDatabaseCount('penjualans', 0);
        $this->assertDatabaseHas('outlets', ['id' => $shop->id, 'jenis_outlet' => 'toko']);
    }

    public function test_replaying_a_generic_admin_form_is_returned_without_running_it_twice(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'offline-generic-admin',
            'email' => 'offline-generic-admin@alami.test',
        ]);
        $payload = [
            'offline_client_id' => 'offline-generic-request-001',
            'name' => 'Offline Generic Customer',
        ];

        $first = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('customer-po.store'), $payload);

        $first->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseCount('customer_pos', 1);

        $second = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('customer-po.store'), $payload);

        $second->assertOk()->assertJson([
            'success' => true,
            'created' => false,
            'data' => ['id' => $first->json('data.id')],
        ]);
        $this->assertDatabaseCount('customer_pos', 1);
        $this->assertInstanceOf(CustomerPo::class, CustomerPo::first());
    }

    public function test_replaying_an_offline_purchase_does_not_create_a_duplicate(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'offline-purchase-admin',
            'email' => 'offline-purchase-admin@alami.test',
        ]);
        $category = Category::create(['name' => 'Offline Purchase Category', 'type' => 'product']);
        $supplier = Supplier::create(['name' => 'Offline Purchase Supplier', 'kode_supplier' => 'OFF-PO']);
        $product = Product::create([
            'code' => 'OFF-PO-001',
            'name' => 'Offline Purchase Product',
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

        $payload = [
            'code' => 'STALE-OFFLINE-CODE',
            'offline_client_id' => 'offline-purchase-request-001',
            'supplier_id' => $supplier->id,
            'total' => '1000',
            'product' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'unit' => 'BOX',
                'harga_beli' => '1000',
                'subtotal' => '1000',
            ]],
        ];

        $first = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('pembelian.store'), $payload);

        $first->assertCreated()->assertJsonPath('success', true);
        $firstCode = $first->json('code');
        $this->assertNotSame('STALE-OFFLINE-CODE', $firstCode);
        $this->assertDatabaseCount('pembelians', 1);

        $second = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('pembelian.store'), $payload);

        $second->assertOk()->assertJson([
            'success' => true,
            'created' => false,
            'id' => $first->json('id'),
        ]);
        $this->assertDatabaseCount('pembelians', 1);
    }

    public function test_replaying_an_offline_sale_does_not_reduce_stock_twice(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'offline-sale-admin',
            'email' => 'offline-sale-admin@alami.test',
        ]);
        $category = Category::create(['name' => 'Offline Sale Category', 'type' => 'product']);
        $agent = Agent::create([
            'name' => 'Offline Sale Agent',
            'code' => 'OFF-AGN',
            'is_active' => true,
        ]);
        $product = Product::create([
            'code' => 'OFF-PNJ-001',
            'name' => 'Offline Sale Product',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 1000,
            'harga_jual' => 1500,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
            'satuan_besar' => 'BOX',
            'konversi_qty' => 1,
        ]);
        $stock = Stock::create([
            'product_id' => $product->id,
            'sku' => 'OFF-PNJ-STOCK',
            'subtotal' => 10000,
            'harga_beli' => 1000,
            'qty' => 10,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $payload = [
            'offline_client_id' => 'offline-sale-request-001',
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'agent',
            'agent_id' => $agent->id,
            'payment_type' => 'termin',
            'items' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'unit' => 'PCS',
                'price' => '1500',
                'discount' => '0',
            ]],
        ];

        $first = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('penjualan.store'), $payload);

        $first->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseCount('penjualans', 1);
        $this->assertSame(9, (int) $stock->fresh()->qty);

        $second = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('penjualan.store'), $payload);

        $second->assertOk()->assertJson([
            'success' => true,
            'created' => false,
            'id' => $first->json('id'),
        ]);
        $this->assertDatabaseCount('penjualans', 1);
        $this->assertSame(9, (int) $stock->fresh()->qty);
    }
}
