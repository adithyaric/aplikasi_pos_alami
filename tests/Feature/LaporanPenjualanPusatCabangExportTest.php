<?php

namespace Tests\Feature;

use App\Exports\LaporanPenjualanPusatCabangExport;
use App\Models\Outlet;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LaporanPenjualanPusatCabangExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_dynamic_product_tabs_and_summary_references(): void
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
        $branch = Outlet::create([
            'name' => 'Cabang Alpha',
            'jenis_outlet' => 'branch',
        ]);

        $warehouseSale = Penjualan::create([
            'code' => 'WH-001',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_name' => 'Agen Alpha',
            'sale_date' => '2026-09-01',
            'payment_type' => 'credit',
            'payment_status' => 'unpaid',
            'total' => 200,
        ]);
        PenjualanItem::create([
            'penjualan_id' => $warehouseSale->id,
            'product_id' => $productA->id,
            'qty' => 2,
            'qty_input' => 2,
            'unit' => 'PCS',
            'price' => 100,
            'discount' => 0,
            'subtotal' => 200,
        ]);

        $branchSale = Penjualan::create([
            'code' => 'BR-001',
            'sale_channel' => 'branch',
            'outlet_id' => $branch->id,
            'sale_date' => '2026-09-02',
            'payment_type' => 'credit',
            'payment_status' => 'unpaid',
            'total' => 450,
        ]);
        PenjualanItem::create([
            'penjualan_id' => $branchSale->id,
            'product_id' => $productB->id,
            'qty' => 3,
            'qty_input' => 3,
            'unit' => 'PCS',
            'price' => 150,
            'discount' => 0,
            'subtotal' => 450,
        ]);
        PenjualanPayment::create([
            'penjualan_id' => $branchSale->id,
            'payment_date' => '2026-09-02',
            'amount' => 100,
            'status' => 'partial',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'laporan-penjualan-');
        (new LaporanPenjualanPusatCabangExport('2026-09-01', '2026-09-03'))->store($path);

        $workbook = IOFactory::load($path);

        $this->assertSame([
            'Product A',
            'Product B',
            'AGEN',
            'JUMLAH PENJUALAN',
            'PIUTANG',
        ], $workbook->getSheetNames());
        $this->assertSame('=SUM(U4:V4)', $workbook->getSheetByName('Product A')->getCell('W4')->getValue());
        $this->assertSame("='Product A'!I4", $workbook->getSheetByName('JUMLAH PENJUALAN')->getCell('C3')->getValue());
        $this->assertSame("='Product B'!Q5", $workbook->getSheetByName('PIUTANG')->getCell('D3')->getValue());
        $this->assertSame('=E2-F2', $workbook->getSheetByName('PIUTANG')->getCell('G2')->getValue());
        $this->assertSame("='PIUTANG'!E2", $workbook->getSheetByName('AGEN')->getCell('F4')->getValue());

        @unlink($path);
    }
}
