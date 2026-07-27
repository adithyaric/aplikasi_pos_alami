<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CurrentDistributionFlowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentDistributionFlowSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_distribution_flow_seeder_creates_expected_master_data(): void
    {
        $this->seed(CurrentDistributionFlowSeeder::class);

        $this->assertSame(4, Agent::count());
        $this->assertSame(2, Canvas::count());
        $this->assertSame(2, Outlet::branches()->count());
        $this->assertSame(2, Outlet::shops()->count());
        $this->assertSame(6, Salesman::whereNotNull('outlet_id')->count());
        $this->assertSame(6, Salesman::whereNotNull('user_id')->count());
        $this->assertSame(2, User::where('role', 'admin-cabang')->count());
        $this->assertSame(6, User::where('role', 'sales')->count());
        $this->assertTrue(User::where('email', 'admin-gudang@alami.test')->where('role', 'admin-gudang')->exists());
        $this->assertTrue(User::where('email', 'owner@alami.test')->where('role', 'owner')->exists());
        $this->assertTrue(Supplier::where('name', 'Pabrik ALAMI')->exists());
        $this->assertGreaterThanOrEqual(4, Product::where('satuan', 'Pack')
            ->where('satuan_besar', 'Slop')
            ->where('satuan_terbesar', 'Ball')
            ->count());
        $this->assertSame(3, Penjualan::warehouseSales()->count());
        $this->assertTrue(Penjualan::warehouseSales()->where('buyer_type', 'agent')->exists());
        $this->assertTrue(Penjualan::warehouseSales()->where('buyer_type', 'canvas')->exists());
        $this->assertTrue(Penjualan::warehouseSales()->where('buyer_type', 'outlet')->exists());
        $this->assertSame(1, Penjualan::branchSales()->count());
        $this->assertGreaterThan(0, OwnerStock::count());
    }
}
