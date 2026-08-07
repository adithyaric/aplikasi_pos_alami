<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\DocumentTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class DocumentTemplateRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokenized_sales_xlsx_uses_only_template_variables_and_repeats_item_rows(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings.json', json_encode([
            'name' => 'Configured Company',
            'sales_invoice_template_xlsx' => 'templates/documents/test-sales.xlsx',
        ]));

        $sale = $this->createSaleWithItems();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'INVOICE');
        $sheet->setCellValue('A2', '{{company.name}}');
        $sheet->setCellValue('B2', '{{sale.number}}');
        $sheet->setCellValue('A3', '{{sale.date}}');
        $sheet->setCellValue('A4', '{{sale.items.no}}');
        $sheet->setCellValue('B4', '{{sale.items.code}}');
        $sheet->setCellValue('C4', '{{sale.items.name}}');
        $sheet->setCellValue('D4', '{{sale.items.qty}}');
        $sheet->setCellValue('E4', '{{sale.items.unit}}');
        $sheet->setCellValue('F4', '{{sale.items.price}}');
        $sheet->setCellValue('G4', '{{sale.items.subtotal}}');
        $sheet->setCellValue('J3', 'KEEP THIS TEMPLATE TEXT');
        $this->storeSpreadsheet($spreadsheet, 'templates/documents/test-sales.xlsx');

        $output = app(DocumentTemplateRenderer::class)->renderSalesInvoiceXlsx($sale);
        $result = IOFactory::load($output)->getActiveSheet();

        $this->assertSame('Configured Company', $result->getCell('A2')->getValue());
        $this->assertSame($sale->code, $result->getCell('B2')->getValue());
        $this->assertSame(
            $sale->sale_date->locale('id')->translatedFormat('d F Y'),
            $result->getCell('A3')->getValue(),
        );
        $this->assertSame(1, $result->getCell('A4')->getValue());
        $this->assertSame('TM-001', $result->getCell('B4')->getValue());
        $this->assertSame('TM-002', $result->getCell('B5')->getValue());
        $this->assertSame('KEEP THIS TEMPLATE TEXT', $result->getCell('J3')->getValue());

        @unlink($output);
    }

    public function test_tokenized_purchase_xlsx_is_not_overwritten_by_legacy_positions(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings.json', json_encode([
            'name' => 'Configured Company',
            'purchase_template_xlsx' => 'templates/documents/test-purchase.xlsx',
        ]));

        $pembelian = $this->createPurchaseWithItems();
        $firstProduct = $pembelian->pembelianProducts()->first()->product;
        $firstProduct->update([
            'satuan' => 'Pack',
            'satuan_besar' => 'Slop',
            'konversi_qty' => 10,
            'satuan_terbesar' => 'Ball',
            'konversi_qty_terbesar' => 25,
        ]);
        $pembelian->pembelianProducts()->first()->update(['qty' => 265]);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'PURCHASE ORDER');
        $sheet->setCellValue('A2', '{{company.name}}');
        $sheet->setCellValue('B2', '{{purchase.number}}');
        $sheet->setCellValue('A17', '{{purchase.items.no}}');
        $sheet->setCellValue('B17', '{{purchase.items.code}}');
        $sheet->setCellValue('C17', '{{purchase.items.name}}');
        $sheet->setCellValue('D17', '{{purchase.items.qty}}');
        $sheet->setCellValue('E17', '{{purchase.items.unit}}');
        $sheet->setCellValue('F17', '{{purchase.items.price}}');
        $sheet->setCellValue('G17', '{{purchase.items.subtotal}}');
        $sheet->setCellValue('H17', '{{purchase.items.qty_besar}}');
        $sheet->setCellValue('I17', '{{purchase.items.qty_terbesar}}');
        $sheet->setCellValue('J6', 'KEEP THIS TEMPLATE TEXT');
        $this->storeSpreadsheet($spreadsheet, 'templates/documents/test-purchase.xlsx');

        $output = app(DocumentTemplateRenderer::class)->renderPurchaseXlsx($pembelian);
        $result = IOFactory::load($output)->getActiveSheet();

        $this->assertSame('Configured Company', $result->getCell('A2')->getValue());
        $this->assertSame($pembelian->code, $result->getCell('B2')->getValue());
        $this->assertSame('PR-001', $result->getCell('B17')->getValue());
        $this->assertSame('PR-002', $result->getCell('B18')->getValue());
        $this->assertSame(265, $result->getCell('D17')->getValue());
        $this->assertSame(26, $result->getCell('H17')->getValue());
        $this->assertSame(1, $result->getCell('I17')->getValue());
        $this->assertSame('KEEP THIS TEMPLATE TEXT', $result->getCell('J6')->getValue());

        @unlink($output);
    }

    public function test_tokenized_purchase_docx_is_not_overwritten_by_legacy_paragraph_data(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings.json', json_encode([
            'name' => 'Configured Company',
            'purchase_template_docx' => 'templates/documents/test-purchase.docx',
        ]));

        $pembelian = $this->createPurchaseWithItems();
        $source = base_path('template_alami_pembelian.docx');
        $temporaryTemplate = tempnam(sys_get_temp_dir(), 'alami-test-docx-');
        file_put_contents($temporaryTemplate, file_get_contents($source));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($temporaryTemplate) === true);
        $xml = $zip->getFromName('word/document.xml');
        $splitNumber = '<w:t>Nomor: {{purchase.number}}    Lampiran: -</w:t>';
        $this->assertStringContainsString($splitNumber, $xml);
        $xml = str_replace(
            $splitNumber,
            '<w:t>Nomor: {{purchase.</w:t></w:r><w:r><w:t>number}}    Lampiran: -</w:t>',
            $xml,
        );
        $oldParagraph = 'Sehubungan dengan kebutuhan pembelian kepada {{supplier.name}}, kami memohon pengiriman barang sesuai rincian berikut.';
        $this->assertStringContainsString($oldParagraph, $xml);
        $zip->addFromString('word/document.xml', str_replace($oldParagraph, '{{company.name}}', $xml));
        $zip->close();
        Storage::disk('public')->put('templates/documents/test-purchase.docx', file_get_contents($temporaryTemplate));
        @unlink($temporaryTemplate);

        $output = app(DocumentTemplateRenderer::class)->renderPurchaseDocx($pembelian);
        $resultZip = new ZipArchive();
        $this->assertTrue($resultZip->open($output) === true);
        $resultXml = $resultZip->getFromName('word/document.xml');
        $resultZip->close();

        $this->assertStringContainsString('Configured Company', $resultXml);
        $this->assertStringContainsString('Purchase Product One', $resultXml);
        $this->assertStringContainsString('Purchase Product Two', $resultXml);
        $this->assertStringNotContainsString('Sehubungan dengan kebutuhan pembelian kepada', $resultXml);

        @unlink($output);
    }

    private function createSaleWithItems(): Penjualan
    {
        $category = Category::create(['name' => 'Renderer Test Category', 'type' => 'product']);
        $agent = Agent::create([
            'name' => 'Renderer Buyer',
            'code' => 'AGN-RENDERER',
            'termin_days' => 14,
            'credit_limit' => 5000000,
            'is_active' => true,
        ]);
        $products = collect([
            ['code' => 'TM-001', 'name' => 'Renderer Product One'],
            ['code' => 'TM-002', 'name' => 'Renderer Product Two'],
        ])->map(fn (array $data) => Product::create([
            ...$data,
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 100,
            'harga_jual' => 150,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
        ]));

        $sale = Penjualan::create([
            'code' => 'SALE-RENDERER',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'buyer_name' => $agent->name,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'total' => 300,
        ]);

        foreach ($products as $product) {
            $sale->items()->create([
                'product_id' => $product->id,
                'qty' => 1,
                'qty_input' => 1,
                'unit' => 'PCS',
                'price' => 150,
                'discount' => 0,
                'subtotal' => 150,
            ]);
        }

        return $sale;
    }

    private function createPurchaseWithItems(): Pembelian
    {
        $category = Category::create(['name' => 'Purchase Renderer Category', 'type' => 'product']);
        $supplier = Supplier::create([
            'name' => 'Renderer Supplier',
            'kode_supplier' => 'SUP-RENDERER',
            'alamat' => 'Supplier Address',
            'no_telp' => '08123456789',
        ]);
        $products = collect([
            ['code' => 'PR-001', 'name' => 'Purchase Product One'],
            ['code' => 'PR-002', 'name' => 'Purchase Product Two'],
        ])->map(fn (array $data) => Product::create([
            ...$data,
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 100,
            'harga_jual' => 150,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
        ]));

        $purchase = Pembelian::create([
            'code' => 'PO-RENDERER',
            'supplier_id' => $supplier->id,
            'total' => 200,
        ]);

        foreach ($products as $product) {
            $purchase->pembelianProducts()->create([
                'product_id' => $product->id,
                'harga_beli' => 100,
                'qty' => 1,
                'subtotal' => 100,
            ]);
        }

        return $purchase;
    }

    private function storeSpreadsheet(Spreadsheet $spreadsheet, string $path): void
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'alami-test-xlsx-');
        (new Xlsx($spreadsheet))->save($temporaryPath);
        Storage::disk('public')->put($path, file_get_contents($temporaryPath));
        @unlink($temporaryPath);
    }
}
