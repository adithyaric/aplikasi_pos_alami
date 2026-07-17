<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\Supplier;
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
        $this->assertSame(6, Salesman::whereNotNull('outlet_id')->count());
        $this->assertTrue(Supplier::where('name', 'Pabrik ALAMI')->exists());
        $this->assertGreaterThanOrEqual(4, Product::where('satuan', 'Pack')
            ->where('satuan_besar', 'Slop')
            ->where('satuan_terbesar', 'Ball')
            ->count());
    }
}
