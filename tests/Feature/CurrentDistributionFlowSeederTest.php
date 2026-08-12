<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\CustomerPo;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Pembelian;
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
        $this->assertTrue(Supplier::where('name', 'PR Tunas Mandiri')->exists());
        $this->assertTrue(Supplier::where('name', 'Margantara Jaya Corp')->exists());
        $supplier = Supplier::where('kode_supplier', 'S00001')->firstOrFail();
        $this->assertSame(
            Supplier::DEFAULT_PO_NUMBER_FORMAT,
            $supplier->po_number_prefix,
        );
        $this->assertSame(2, Supplier::count());
        $this->assertSame(
            'PO-S00002-'.now()->format('Ym').'-00002',
            Supplier::where('kode_supplier', 'S00002')->firstOrFail()->generateNextPoCode(),
        );
        $this->assertSame(3, Pembelian::count());
        $this->assertSame(3, CustomerPo::count());
        $this->assertTrue(CustomerPo::where('name', 'PT Sumber Makmur')->exists());
        $this->assertTrue(Pembelian::where('supplier_id', $supplier->id)
            ->where('code', 'PO-S00001-'.now()->format('Ym').'-00001')
            ->exists());
        $this->assertGreaterThanOrEqual(4, Product::where('satuan', 'Pack')
            ->where('satuan_besar', 'Slop')
            ->where('satuan_terbesar', 'Ball')
            ->count());
        $this->assertSame(6, Penjualan::warehouseSales()->count());
        $this->assertTrue(Penjualan::warehouseSales()->where('buyer_type', 'agent')->exists());
        $this->assertTrue(Penjualan::warehouseSales()->where('buyer_type', 'canvas')->exists());
        $this->assertTrue(Penjualan::warehouseSales()->where('buyer_type', 'outlet')->exists());
        $this->assertSame(1, Penjualan::branchSales()->count());
        $this->assertTrue(Penjualan::branchSales()
            ->get()
            ->contains(fn (Penjualan $sale): bool => (bool) preg_match('/^CBG\.\d{4}\.\d{2}\.\d{2}$/', $sale->code)));
        $this->assertGreaterThan(0, OwnerStock::count());
    }

    public function test_current_distribution_flow_seeder_is_idempotent(): void
    {
        $this->seed(CurrentDistributionFlowSeeder::class);
        $counts = [
            'pembelian' => Pembelian::count(),
            'penjualan' => Penjualan::count(),
            'refund_pembelian' => \App\Models\RefundPembelian::count(),
            'refund' => \App\Models\Refund::count(),
        ];

        $this->seed(CurrentDistributionFlowSeeder::class);

        $this->assertSame($counts['pembelian'], Pembelian::count());
        $this->assertSame($counts['penjualan'], Penjualan::count());
        $this->assertSame($counts['refund_pembelian'], \App\Models\RefundPembelian::count());
        $this->assertSame($counts['refund'], \App\Models\Refund::count());
    }
}
