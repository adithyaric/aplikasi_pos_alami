<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCustomerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_customer_catalog_can_create_and_update_agent(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'email' => 'sales-customer-catalog@alami.test']);

        $this->actingAs($admin)->post(route('customer-penjualan.store'), [
            'type' => 'agent',
            'name' => 'Agen Catalog Test',
            'code' => 'AGN-CATALOG',
            'alamat' => 'Jl. Catalog',
            'no_telp' => '08123456789',
            'is_active' => 1,
        ])->assertRedirect(route('customer-penjualan.index'));

        $agent = Agent::where('name', 'Agen Catalog Test')->firstOrFail();
        $this->assertSame('AGN-CATALOG', $agent->code);

        $this->actingAs($admin)->put(route('customer-penjualan.update', ['agent', $agent->id]), [
            'type' => 'agent',
            'name' => 'Agen Catalog Updated',
            'code' => 'AGN-CATALOG-2',
            'is_active' => 1,
        ])->assertRedirect(route('customer-penjualan.index'));

        $this->assertDatabaseHas('agents', ['id' => $agent->id, 'name' => 'Agen Catalog Updated']);
    }

    public function test_sales_customer_catalog_lists_toko_and_filters_by_type(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'email' => 'sales-customer-toko@alami.test']);
        $shop = Outlet::create(['name' => 'Toko Catalog Test', 'jenis_outlet' => 'toko', 'alamat' => 'Jl. Toko']);
        Outlet::create(['name' => 'Cabang Catalog Test', 'jenis_outlet' => 'branch']);

        $response = $this->actingAs($admin)->get(route('customer-penjualan.index', ['type' => 'toko']));

        $response->assertOk();
        $response->assertSee('Toko Catalog Test');
        $response->assertDontSee('Cabang Catalog Test');
        $response->assertSee(route('customer-penjualan.create'));
        $this->assertSame('toko', $shop->jenis_outlet);
    }
}
