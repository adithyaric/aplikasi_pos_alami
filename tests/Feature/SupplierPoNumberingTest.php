<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPoNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_next_po_code_uses_supplier_format(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'supplier-po-number-admin',
            'email' => 'supplier-po-number-admin@alami.test',
        ]);

        $supplier = Supplier::create([
            'kode_supplier' => 'S00077',
            'name' => 'Supplier PO Test',
            'alamat' => 'Yogyakarta',
            'no_telp' => '08123456789',
            'po_number_prefix' => 'PO-{SUPPLIER_CODE}-{YYYY}{MM}-',
            'po_number_padding' => 4,
        ]);

        Pembelian::create([
            'code' => 'PO-S00077-202607-0001',
            'supplier_id' => $supplier->id,
            'total' => 0,
            'is_published' => false,
            'owner_approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('pembelian.next-code', $supplier));

        $response->assertOk()
            ->assertJson([
                'code' => 'PO-S00077-202607-0002',
            ]);

        Carbon::setTestNow();
    }

    public function test_supplier_next_po_code_supports_roman_month_and_front_sequence(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'supplier-po-number-roman-admin',
            'email' => 'supplier-po-number-roman-admin@alami.test',
        ]);

        $supplier = Supplier::create([
            'kode_supplier' => 'S00088',
            'name' => 'Supplier PO Roman Test',
            'alamat' => 'Bandung',
            'no_telp' => '081298765432',
            'po_number_prefix' => '{SEQ}/PO/{SUPPLIER_CODE}/{YYYY}{ROMAN_MM}',
            'po_number_padding' => 4,
        ]);

        Pembelian::create([
            'code' => '0001/PO/S00088/2026VII',
            'supplier_id' => $supplier->id,
            'total' => 0,
            'is_published' => false,
            'owner_approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('pembelian.next-code', $supplier));

        $response->assertOk()
            ->assertJson([
                'code' => '0002/PO/S00088/2026VII',
            ]);

        Carbon::setTestNow();
    }
}
