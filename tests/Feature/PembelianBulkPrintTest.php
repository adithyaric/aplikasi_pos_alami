<?php

namespace Tests\Feature;

use App\Exports\PembelianBulkExport;
use App\Models\Category;
use App\Models\Pembelian;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Tests\TestCase;

class PembelianBulkPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_filter_shows_and_downloads_bulk_purchase_print(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'role' => 'admin-gudang',
            'username' => 'bulk-po-admin',
            'email' => 'bulk-po-admin@alami.test',
        ]);
        $supplier = Supplier::create([
            'name' => 'Bulk Supplier',
            'kode_supplier' => 'S-BULK',
        ]);
        Pembelian::create([
            'code' => 'PO-BULK-001',
            'supplier_id' => $supplier->id,
            'total' => 100,
        ]);
        Pembelian::create([
            'code' => 'PO-BULK-002',
            'supplier_id' => $supplier->id,
            'total' => 200,
        ]);

        $templatePath = 'templates/documents/suppliers/'.$supplier->id.'/bulk.xlsx';
        $supplier->update(['po_template' => $templatePath]);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', '{{sale.number}}');
        $temporaryPath = tempnam(sys_get_temp_dir(), 'alami-bulk-po-');
        (new Xlsx($spreadsheet))->save($temporaryPath);
        Storage::disk('public')->put($templatePath, file_get_contents($temporaryPath));
        @unlink($temporaryPath);

        $page = $this->actingAs($user)->get(route('pembelian.index', [
            'supplier_id' => $supplier->id,
        ]));
        $page->assertOk();
        $page->assertSee('Cetak Semua PO Supplier');

        $download = $this->actingAs($user)->get(route('laporan.pembelian.bulk', [
            'supplier_id' => $supplier->id,
        ]));
        $download->assertOk();
        $this->assertStringContainsString(
            'Dokumen_PO-Bulk-Bulk-Supplier.xlsx',
            (string) $download->headers->get('content-disposition'),
        );
    }

    public function test_fixed_bulk_export_stacks_invoices_and_borders_item_rows_without_supplier_template(): void
    {
        Storage::fake('public');
        $supplier = Supplier::create([
            'name' => 'Fixed Bulk Supplier',
            'kode_supplier' => 'S-FIXED',
            'po_template' => 'templates/documents/suppliers/does-not-exist.docx',
        ]);
        $category = Category::create([
            'name' => 'Bulk Export Category',
            'type' => 'product',
        ]);
        $product = Product::create([
            'code' => 'FIX-001',
            'name' => 'Fixed Bulk Product',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 125,
            'harga_jual' => 150,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
        ]);
        $first = Pembelian::create([
            'code' => 'PO-FIXED-001',
            'supplier_id' => $supplier->id,
            'total' => 125,
        ]);
        $first->pembelianProducts()->create([
            'product_id' => $product->id,
            'harga_beli' => 125,
            'qty' => 1,
            'subtotal' => 125,
        ]);
        $second = Pembelian::create([
            'code' => 'PO-FIXED-002',
            'supplier_id' => $supplier->id,
            'total' => 250,
        ]);
        $second->pembelianProducts()->create([
            'product_id' => $product->id,
            'harga_beli' => 125,
            'qty' => 2,
            'subtotal' => 250,
        ]);

        $raw = Excel::raw(
            new PembelianBulkExport(
                Pembelian::whereIn('id', [$first->id, $second->id])->orderBy('id')->get(),
                ['name' => 'Configured Company', 'address' => 'Configured Address', 'telp' => '0812'],
            ),
            ExcelWriter::XLSX,
        );
        $temporaryPath = tempnam(sys_get_temp_dir(), 'alami-fixed-bulk-').'.xlsx';
        file_put_contents($temporaryPath, $raw);
        $sheet = IOFactory::load($temporaryPath)->getActiveSheet();

        $this->assertSame('INVOICE 1', $sheet->getCell('B1')->getValue());
        $this->assertSame('PO-FIXED-001', $sheet->getCell('I3')->getValue());
        $this->assertSame('Fixed Bulk Product', $sheet->getCell('E11')->getValue());
        $this->assertSame('INVOICE 2', $sheet->getCell('B27')->getValue());
        $this->assertSame('2 PCS', $sheet->getCell('F37')->getValue());
        $this->assertNotEmpty($sheet->getRowBreaks());
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            $sheet->getStyle('B11:J11')->getBorders()->getTop()->getBorderStyle(),
        );

        @unlink($temporaryPath);
    }
}
