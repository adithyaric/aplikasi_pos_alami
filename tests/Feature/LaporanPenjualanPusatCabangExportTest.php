<?php

namespace Tests\Feature;

use App\Exports\LaporanPenjualanPusatCabangExport;
use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanPayment;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LaporanPenjualanPusatCabangExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pusat_agent_filter_exports_only_the_selected_agent_and_keeps_agent_sheet(): void
    {
        $productA = Product::create([
            'code' => 'A-001',
            'name' => 'Product A',
            'satuan' => 'PCS',
            'satuan_besar' => 'BOX',
            'konversi_qty' => 12,
            'satuan_terbesar' => 'KARTON',
            'konversi_qty_terbesar' => 4,
            'harga_jual' => 100,
        ]);
        $productB = Product::create([
            'code' => 'B-001',
            'name' => 'Product B',
            'satuan' => 'PCS',
            'harga_jual' => 150,
        ]);
        $agent = Agent::create([
            'name' => 'Agen Alpha',
            'is_active' => true,
        ]);
        $canvas = Canvas::create([
            'name' => 'Canvas Alpha',
            'is_active' => true,
        ]);

        $agentSale = Penjualan::create([
            'code' => 'WH-001',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'buyer_name' => $agent->name,
            'sale_date' => '2026-09-01',
            'payment_type' => 'credit',
            'payment_status' => 'unpaid',
            'total' => 200,
        ]);
        PenjualanItem::create([
            'penjualan_id' => $agentSale->id,
            'product_id' => $productA->id,
            'qty' => 2,
            'qty_input' => 2,
            'unit' => 'PCS',
            'price' => 100,
            'discount' => 0,
            'subtotal' => 200,
        ]);

        $canvasSale = Penjualan::create([
            'code' => 'WH-002',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'canvas',
            'buyer_id' => $canvas->id,
            'buyer_name' => $canvas->name,
            'sale_date' => '2026-09-02',
            'payment_type' => 'credit',
            'payment_status' => 'unpaid',
            'total' => 450,
        ]);
        PenjualanItem::create([
            'penjualan_id' => $canvasSale->id,
            'product_id' => $productB->id,
            'qty' => 3,
            'qty_input' => 3,
            'unit' => 'PCS',
            'price' => 150,
            'discount' => 0,
            'subtotal' => 450,
        ]);
        PenjualanPayment::create([
            'penjualan_id' => $canvasSale->id,
            'payment_date' => '2026-09-02',
            'amount' => 100,
            'status' => 'partial',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'laporan-penjualan-');
        (new LaporanPenjualanPusatCabangExport(
            '2026-09-01',
            '2026-09-03',
            LaporanPenjualanPusatCabangExport::SCOPE_PUSAT,
            null,
            null,
            'agen',
            $agent->id,
        ))->store($path);

        $workbook = IOFactory::load($path);

        $this->assertSame([
            'Product A',
            'Product B',
            'AGEN',
            'JUMLAH PENJUALAN',
            'PIUTANG',
        ], $workbook->getSheetNames());
        $this->assertSame(
            'PENJUALAN PUSAT - AGEN - AGEN ALPHA - PRODUCT A',
            $workbook->getSheetByName('Product A')->getCell('A1')->getValue()
        );
        $this->assertSame('Agen Alpha', $workbook->getSheetByName('Product A')->getCell('U2')->getValue());
        $this->assertSame('=SUM(U4:U4)', $workbook->getSheetByName('Product A')->getCell('V4')->getValue());
        $this->assertSame("='Product A'!I4", $workbook->getSheetByName('JUMLAH PENJUALAN')->getCell('C3')->getValue());
        $this->assertSame("='Product B'!Q4", $workbook->getSheetByName('PIUTANG')->getCell('D2')->getValue());
        $this->assertSame('=E2-F2', $workbook->getSheetByName('PIUTANG')->getCell('G2')->getValue());
        $this->assertSame("='PIUTANG'!E2", $workbook->getSheetByName('AGEN')->getCell('F4')->getValue());

        @unlink($path);

        $response = $this->actingAs(User::factory()->create(['role' => 'superadmin']))->get(route('laporan.penjualan.pusat-cabang', [
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-03',
            'scope' => 'pusat',
            'filter_type' => 'agen',
        ]));
        $this->assertStringContainsString(
            'laporan_penjualan_pusat_semua-agen.xlsx',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_cabang_filter_uses_the_selected_branch_salesman_and_omits_agent_sheet(): void
    {
        $product = Product::create([
            'code' => 'A-001',
            'name' => 'Product A',
            'satuan' => 'PCS',
            'harga_jual' => 100,
        ]);
        $branch = Outlet::create([
            'name' => 'Cabang Alpha',
            'jenis_outlet' => 'branch',
        ]);
        $salesman = Salesman::create([
            'name' => 'Sales Alpha',
            'outlet_id' => $branch->id,
        ]);
        $sale = Penjualan::create([
            'code' => 'BR-001',
            'sale_channel' => 'branch',
            'buyer_type' => 'toko',
            'buyer_id' => $branch->id,
            'buyer_name' => 'Toko Alpha',
            'outlet_id' => $branch->id,
            'salesman_id' => $salesman->id,
            'sale_date' => '2026-09-01',
            'payment_type' => 'credit',
            'payment_status' => 'unpaid',
            'total' => 200,
        ]);
        PenjualanItem::create([
            'penjualan_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 2,
            'qty_input' => 2,
            'unit' => 'PCS',
            'price' => 100,
            'discount' => 0,
            'subtotal' => 200,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'laporan-penjualan-');
        (new LaporanPenjualanPusatCabangExport(
            '2026-09-01',
            '2026-09-03',
            LaporanPenjualanPusatCabangExport::SCOPE_CABANG,
            $branch->id,
            null,
            null,
            null,
            $salesman->id,
        ))->store($path);

        $workbook = IOFactory::load($path);

        $this->assertSame([
            'Product A',
            'JUMLAH PENJUALAN',
            'PIUTANG',
        ], $workbook->getSheetNames());
        $this->assertSame(
            'PENJUALAN CABANG - CABANG ALPHA - SALES: SALES ALPHA - PRODUCT A',
            $workbook->getSheetByName('Product A')->getCell('A1')->getValue()
        );
        $this->assertSame('Sales Alpha', $workbook->getSheetByName('Product A')->getCell('U2')->getValue());
        $this->assertSame('=SUM(U4:U4)', $workbook->getSheetByName('Product A')->getCell('V4')->getValue());
        $this->assertNull($workbook->getSheetByName('AGEN'));

        @unlink($path);

        $response = $this->actingAs(User::factory()->create(['role' => 'superadmin']))->get(route('laporan.penjualan.pusat-cabang', [
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-03',
            'scope' => 'cabang',
            'outlet_id' => $branch->id,
        ]));
        $this->assertStringContainsString(
            'laporan_penjualan_cabang_cabang-alpha_semua-sales.xlsx',
            (string) $response->headers->get('Content-Disposition')
        );
    }
}
