<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Pembelian;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DocumentTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PurchaseAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_adjustments_determine_saved_and_exported_total(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $supplier = Supplier::create([
            'name' => 'Adjusted Supplier',
            'kode_supplier' => 'S99992',
            'alamat' => 'Jakarta',
            'no_telp' => '081234567890',
        ]);
        $category = Category::create(['name' => 'Adjusted Products', 'type' => 'product']);
        $product = Product::create([
            'code' => 'ADJ-001',
            'name' => 'Adjusted Product',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 100000,
            'harga_jual' => 120000,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
        ]);
        $payload = [
            'supplier_id' => $supplier->id,
            'total' => '1', // The submitted total must not override the formula.
            'discount_percent' => '5',
            'tax_percent' => '11',
            'shipping_cost' => '175,000',
            'product' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'unit' => 'PCS',
                'harga_beli' => '100,000',
                'subtotal' => '100,000',
            ]],
        ];

        $this->actingAs($user)->get(route('pembelian.create'))
            ->assertOk()
            ->assertSee('name="discount_percent"', false)
            ->assertSee('name="tax_percent"', false)
            ->assertSee('name="shipping_cost"', false);

        $this->actingAs($user)->post(route('pembelian.store'), $payload)
            ->assertRedirect(route('pembelian.index'));
        $purchase = Pembelian::firstOrFail();
        $this->assertSame('281000', $purchase->total);
        $this->assertEquals(5, $purchase->discount_percent);
        $this->assertEquals(11, $purchase->tax_percent);
        $this->assertEquals(175000, $purchase->shipping_cost);
        $this->actingAs($user)->get(route('pembelian.edit', $purchase))
            ->assertOk()
            ->assertSee('name="discount_percent"', false)
            ->assertSee('name="tax_percent"', false)
            ->assertSee('name="shipping_cost"', false);

        Storage::fake('public');
        $path = 'templates/documents/suppliers/'.$supplier->id.'/adjusted-po.xlsx';
        $supplier->update(['po_template' => $path]);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '{{sale.subtotal}}');
        $sheet->setCellValue('A2', '{{sale.potongan}}');
        $sheet->setCellValue('A3', '{{sale.tax}}');
        $sheet->setCellValue('A4', '{{sale.shipping_cost}}');
        $sheet->setCellValue('A5', '{{sale.total}}');
        $temporary = tempnam(sys_get_temp_dir(), 'adjusted-po-');
        (new Xlsx($spreadsheet))->save($temporary);
        Storage::disk('public')->put($path, file_get_contents($temporary));
        @unlink($temporary);

        $output = app(DocumentTemplateRenderer::class)->renderPurchaseXlsx($purchase->fresh());
        $result = IOFactory::load($output)->getActiveSheet();
        $this->assertSame('Rp 100.000', $result->getCell('A1')->getValue());
        $this->assertSame(5, $result->getCell('A2')->getValue());
        $this->assertSame(11, $result->getCell('A3')->getValue());
        $this->assertSame('Rp.175.000', $result->getCell('A4')->getValue());
        $this->assertSame('Rp 281.000', $result->getCell('A5')->getValue());
        @unlink($output);

        $payload['discount_percent'] = '10';
        $payload['tax_percent'] = '0';
        $payload['shipping_cost'] = '0';
        $this->actingAs($user)->put(route('pembelian.update', $purchase), $payload)
            ->assertRedirect(route('pembelian.index'));
        $this->assertSame('90000', $purchase->fresh()->total);
    }
}
