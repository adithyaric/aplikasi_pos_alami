<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_page_does_not_manage_document_templates(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'setting-page-admin',
            'email' => 'setting-page-admin@alami.test',
        ]);

        $response = $this->actingAs($user)->get(route('setting'));

        $response->assertOk();
        $response->assertDontSee('Template PO');
        $response->assertDontSee('Template Dokumen Pembelian');
    }

    public function test_document_templates_can_be_uploaded_and_downloaded_from_laporan(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'setting-upload-admin',
            'email' => 'setting-upload-admin@alami.test',
        ]);

        $response = $this->actingAs($user)->post(route('laporan.templates.update'), [
            'purchase_template_docx' => UploadedFile::fake()->create('custom-purchase.docx', 24),
            'purchase_template_xlsx' => UploadedFile::fake()->create('custom-purchase.xlsx', 24),
            'sales_invoice_template_xlsx' => UploadedFile::fake()->create('custom-invoice.xlsx', 24),
            'sales_delivery_template_xlsx' => UploadedFile::fake()->create('custom-delivery.xlsx', 24),
        ]);

        $response->assertRedirect(route('laporan.index'));

        $variablesPage = $this->actingAs($user)->get(route('laporan.index'));
        $variablesPage->assertOk();
        $variablesPage->assertSee('{{company.name}}');
        $variablesPage->assertSee('{{item.name}}');
        $variablesPage->assertSee('{{purchase.items.name}}');
        $variablesPage->assertSee('{{sale.items.name}}');
        $variablesPage->assertSee('Bisa dipakai di semua template');
        $variablesPage->assertSee('Pembelian / PO');
        $variablesPage->assertSee('Penjualan / Invoice / Surat Jalan');
        $variablesPage->assertDontSee('{{supplier.email}}');

        Storage::disk('public')->assertExists('templates/documents/pembelian-docx.docx');
        Storage::disk('public')->assertExists('templates/documents/pembelian-xlsx.xlsx');
        Storage::disk('public')->assertExists('templates/documents/penjualan-invoice.xlsx');
        Storage::disk('public')->assertExists('templates/documents/penjualan-surat-jalan.xlsx');

        $downloadDocx = $this->actingAs($user)->get(route('laporan.templates.download', 'pembelian-docx'));
        $downloadXlsx = $this->actingAs($user)->get(route('laporan.templates.download', 'pembelian-xlsx'));

        $downloadDocx->assertOk();
        $downloadDocx->assertHeader('content-disposition', 'attachment; filename=pembelian-docx.docx');

        $downloadXlsx->assertOk();
        $downloadXlsx->assertHeader('content-disposition', 'attachment; filename=pembelian-xlsx.xlsx');
    }
}
