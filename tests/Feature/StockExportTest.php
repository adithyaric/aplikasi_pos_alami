<?php

namespace Tests\Feature;

use App\Exports\StockExport;
use App\Models\Pembelian;
use App\Models\PembelianProduct;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class StockExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekap_stok_harian_uses_date_range_customer_po_rows_and_unit_columns(): void
    {
        $productA = Product::create([
            'code' => 'ALM-A',
            'name' => 'Product A',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 20,
            'harga_beli' => 100,
        ]);
        $productB = Product::create([
            'code' => 'ALM-B',
            'name' => 'Product B',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 20,
            'harga_beli' => 100,
        ]);
        $outsideRangeProduct = Product::create([
            'code' => 'ALM-C',
            'name' => 'Outside Range Product',
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 20,
            'harga_beli' => 100,
        ]);

        $purchaseOne = Pembelian::create([
            'code' => 'PO-001',
            'customer_po' => 'JEPARI',
            'total' => 0,
        ]);
        $purchaseOne->forceFill([
            'created_at' => '2026-08-03 08:00:00',
            'updated_at' => '2026-08-03 08:00:00',
        ])->save();

        $purchaseTwo = Pembelian::create([
            'code' => 'PO-002',
            'customer_po' => 'JOGJA',
            'total' => 0,
        ]);
        $purchaseTwo->forceFill([
            'created_at' => '2026-08-20 08:00:00',
            'updated_at' => '2026-08-20 08:00:00',
        ])->save();

        $purchaseOutsideRange = Pembelian::create([
            'code' => 'PO-003',
            'customer_po' => 'OUTSIDE RANGE',
            'total' => 0,
        ]);
        $purchaseOutsideRange->forceFill([
            'created_at' => '2026-09-01 08:00:00',
            'updated_at' => '2026-09-01 08:00:00',
        ])->save();

        PembelianProduct::create([
            'pembelian_id' => $purchaseOne->id,
            'product_id' => $productA->id,
            'harga_beli' => 100,
            'qty' => 3200,
            'subtotal' => 320000,
        ]);
        PembelianProduct::create([
            'pembelian_id' => $purchaseTwo->id,
            'product_id' => $productA->id,
            'harga_beli' => 100,
            'qty' => 100,
            'subtotal' => 10000,
        ]);
        PembelianProduct::create([
            'pembelian_id' => $purchaseTwo->id,
            'product_id' => $productB->id,
            'harga_beli' => 100,
            'qty' => 50,
            'subtotal' => 5000,
        ]);
        PembelianProduct::create([
            'pembelian_id' => $purchaseOutsideRange->id,
            'product_id' => $outsideRangeProduct->id,
            'harga_beli' => 100,
            'qty' => 999,
            'subtotal' => 99900,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'rekap-stok-test-');
        (new StockExport('2026-08-01', '2026-08-31'))->store($path);

        $sheet = IOFactory::load($path)->getSheetByName('Rekap Stok');

        $this->assertSame('Product A', $sheet->getCell('C5')->getValue());
        $this->assertSame('Pack', $sheet->getCell('C6')->getValue());
        $this->assertSame('Slop', $sheet->getCell('D6')->getValue());
        $this->assertSame('Ball', $sheet->getCell('E6')->getValue());
        $this->assertSame('Product B', $sheet->getCell('F5')->getValue());
        $this->assertSame('Periode: 01/08/2026 s/d 31/08/2026', $sheet->getCell('A4')->getValue());

        $this->assertSame('JEPARI', $sheet->getCell('B7')->getValue());
        $this->assertSame(3200, $sheet->getCell('C7')->getValue());
        $this->assertSame(320, $sheet->getCell('D7')->getValue());
        $this->assertSame(16, $sheet->getCell('E7')->getValue());
        $this->assertSame('JOGJA', $sheet->getCell('B8')->getValue());
        $this->assertSame(100, $sheet->getCell('C8')->getValue());
        $this->assertSame(10, $sheet->getCell('D8')->getValue());
        $this->assertSame(0.5, $sheet->getCell('E8')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('A9')->getValue());
        $this->assertSame('=SUM(C7:C8)', $sheet->getCell('C9')->getValue());
        $this->assertSame('H', $sheet->getHighestColumn());
        $this->assertNull($sheet->getCell('B10')->getValue());
        $this->assertNull($sheet->getCell('B11')->getValue());
        $this->assertNull($sheet->getCell('B12')->getValue());
        $this->assertSame('01 Agustus 2026 SABTU s/d 31 Agustus 2026 SENIN', $sheet->getCell('B13')->getValue());
        $this->assertSame('Stok Awal', $sheet->getCell('A17')->getValue());
        $this->assertSame('Product A', $sheet->getCell('B17')->getValue());
        $this->assertNull($sheet->getCell('C17')->getValue());
        $this->assertSame('Produksi', $sheet->getCell('A19')->getValue());
        $this->assertSame('=C9', $sheet->getCell('C19')->getValue());
        $this->assertSame('=D9', $sheet->getCell('D19')->getValue());
        $this->assertSame('=E9', $sheet->getCell('E19')->getValue());
        $this->assertSame(3300, $sheet->getCell('C19')->getCalculatedValue());
        $this->assertSame(330, $sheet->getCell('D19')->getCalculatedValue());
        $this->assertSame(16.5, $sheet->getCell('E19')->getCalculatedValue());
        $this->assertSame('Product B', $sheet->getCell('B20')->getValue());
        $this->assertSame(28, $sheet->getHighestRow());

        $this->assertNotSame('Outside Range Product', $sheet->getCell('I5')->getValue());

        @unlink($path);
    }
}
