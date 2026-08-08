<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierPoTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_manage_one_po_template_in_either_supported_format(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'supplier-template-admin',
            'email' => 'supplier-template-admin@alami.test',
        ]);

        $response = $this->actingAs($user)->post(route('supplier.store'), [
            'kode_supplier' => 'S00001',
            'name' => 'Supplier A',
            'alamat' => 'Yogyakarta',
            'no_telp' => '08123456789',
            'po_number_prefix' => 'PO-{SUPPLIER_CODE}-{YYYY}{MM}-{SEQ}',
            'po_number_padding' => 5,
            'po_template' => UploadedFile::fake()->create('template-po-supplier-a.xlsx', 24),
        ]);

        $response->assertRedirect(route('supplier.index'));
        $supplier = Supplier::where('kode_supplier', 'S00001')->firstOrFail();

        $this->assertSame(
            'templates/documents/suppliers/'.$supplier->id.'/template-po-supplier-a.xlsx',
            $supplier->po_template,
        );
        Storage::disk('public')->assertExists($supplier->po_template);

        $oldPath = $supplier->po_template;
        $this->actingAs($user)->put(route('supplier.update', $supplier), [
            'kode_supplier' => $supplier->kode_supplier,
            'name' => $supplier->name,
            'alamat' => $supplier->alamat,
            'no_telp' => $supplier->no_telp,
            'po_number_prefix' => $supplier->po_number_prefix,
            'po_number_padding' => $supplier->po_number_padding,
            'po_template' => UploadedFile::fake()->create('template-po-supplier-a.docx', 24),
        ])->assertRedirect(route('supplier.index'));

        $supplier->refresh();
        $this->assertStringEndsWith('.docx', $supplier->po_template);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($supplier->po_template);

        $this->actingAs($user)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertDontSee('Template Dokumen Pembelian &amp; Penjualan', false)
            ->assertSee('Template Dokumen Penjualan');
    }
}
