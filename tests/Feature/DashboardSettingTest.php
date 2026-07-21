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

    public function test_setting_page_renders_po_template_section(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'setting-page-admin',
            'email' => 'setting-page-admin@alami.test',
        ]);

        $response = $this->actingAs($user)->get(route('setting'));

        $response->assertOk();
        $response->assertSee('Template PO');
        $response->assertSee('contoh-po-docs.docx');
        $response->assertSee('contoh-po-excel.xlsx');
    }

    public function test_po_template_can_be_uploaded_and_downloaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'setting-upload-admin',
            'email' => 'setting-upload-admin@alami.test',
        ]);

        $response = $this->actingAs($user)->post(route('setting.store'), [
            'name' => 'ALAMI',
            'email' => 'admin@alami.test',
            'telp' => '08123456789',
            'address' => 'Yogyakarta',
            'website' => 'https://alami.test',
            'po_template_docx' => UploadedFile::fake()->create('custom-template.docx', 24),
            'po_template_xlsx' => UploadedFile::fake()->create('custom-template.xlsx', 24),
        ]);

        $response->assertRedirect(route('setting'));

        Storage::disk('public')->assertExists('templates/po/po-template-docx.docx');
        Storage::disk('public')->assertExists('templates/po/po-template-xlsx.xlsx');

        $downloadDocx = $this->actingAs($user)->get(route('setting.po-template.download', 'docx'));
        $downloadXlsx = $this->actingAs($user)->get(route('setting.po-template.download', 'xlsx'));

        $downloadDocx->assertOk();
        $downloadDocx->assertHeader('content-disposition', 'attachment; filename=po-template-docx.docx');

        $downloadXlsx->assertOk();
        $downloadXlsx->assertHeader('content-disposition', 'attachment; filename=po-template-xlsx.xlsx');
    }
}
