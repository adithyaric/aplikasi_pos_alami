<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCustomerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_customer_catalog_can_create_and_update_toko(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'email' => 'sales-customer-catalog@alami.test']);

        $this->actingAs($admin)->post(route('customer-penjualan.store'), [
            'type' => 'toko',
            'name' => 'Toko Catalog Test',
            'alamat' => 'Jl. Catalog',
            'no_telp' => '08123456789',
            'is_active' => 1,
        ])->assertRedirect(route('customer-penjualan.index'));

        $shop = Outlet::where('name', 'Toko Catalog Test')->firstOrFail();

        $this->actingAs($admin)->put(route('customer-penjualan.update', ['toko', $shop->id]), [
            'type' => 'toko',
            'name' => 'Toko Catalog Updated',
            'is_active' => 1,
        ])->assertRedirect(route('customer-penjualan.index'));

        $this->assertDatabaseHas('outlets', [
            'id' => $shop->id,
            'name' => 'Toko Catalog Updated',
            'jenis_outlet' => 'toko',
        ]);
    }

    public function test_sales_customer_catalog_only_lists_toko(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'email' => 'sales-customer-toko@alami.test']);
        $shop = Outlet::create(['name' => 'Toko Catalog Test', 'jenis_outlet' => 'toko', 'alamat' => 'Jl. Toko']);
        Outlet::create(['name' => 'Cabang Catalog Test', 'jenis_outlet' => 'branch']);

        $response = $this->actingAs($admin)->get(route('customer-penjualan.index'));

        $response->assertOk();
        $response->assertSee('Toko Catalog Test');
        $response->assertDontSee('Cabang Catalog Test');
        $response->assertSee(route('customer-penjualan.create'));
        $this->assertSame('toko', $shop->jenis_outlet);
    }

    public function test_sales_customer_catalog_rejects_non_toko_types(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'email' => 'sales-customer-types@alami.test']);

        $response = $this->actingAs($admin)->from(route('customer-penjualan.create'))->post(route('customer-penjualan.store'), [
            'type' => 'agent',
            'name' => 'Agen Should Not Be Customer Penjualan',
        ]);

        $response->assertRedirect(route('customer-penjualan.create'));
        $this->assertDatabaseMissing('agents', ['name' => 'Agen Should Not Be Customer Penjualan']);
    }
}
