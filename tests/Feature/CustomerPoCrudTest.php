<?php

namespace Tests\Feature;

use App\Models\CustomerPo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_and_update_customer_po(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-admin',
            'email' => 'customer-po-admin@alami.test',
        ]);

        $createResponse = $this->actingAs($user)->post(route('customer-po.store'), [
            'name' => 'PT Contoh Customer',
        ]);

        $createResponse->assertRedirect(route('customer-po.index'));
        $this->assertDatabaseHas('customer_pos', [
            'name' => 'PT Contoh Customer',
        ]);

        $customerPo = CustomerPo::firstOrFail();

        $updateResponse = $this->actingAs($user)->put(route('customer-po.update', $customerPo), [
            'name' => 'PT Customer Update',
        ]);

        $updateResponse->assertRedirect(route('customer-po.index'));
        $this->assertDatabaseHas('customer_pos', [
            'id' => $customerPo->id,
            'name' => 'PT Customer Update',
        ]);
    }

    public function test_ajax_store_reuses_existing_customer_po_case_insensitively(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-ajax-admin',
            'email' => 'customer-po-ajax-admin@alami.test',
        ]);

        $existing = CustomerPo::create([
            'name' => 'PT Existing Customer',
        ]);

        $existing->delete();

        $response = $this->actingAs($user)->postJson(route('customer-po.store'), [
            'name' => '  pt existing customer  ',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'id' => $existing->id,
                    'name' => 'pt existing customer',
                ],
            ]);

        $this->assertSame(1, CustomerPo::withTrashed()->count());
        $this->assertDatabaseHas('customer_pos', [
            'id' => $existing->id,
            'name' => 'pt existing customer',
            'deleted_at' => null,
        ]);
    }

    public function test_modal_store_returns_json_through_web_route(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-modal-admin',
            'email' => 'customer-po-modal-admin@alami.test',
        ]);

        $response = $this->actingAs($user)->post(route('customer-po.store'), [
            '_ajax' => 1,
            'name' => 'PT Modal Customer',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'PT Modal Customer',
                ],
            ]);

        $this->assertDatabaseHas('customer_pos', [
            'name' => 'PT Modal Customer',
        ]);
    }

    public function test_ajax_index_returns_matching_customer_po_options(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-search-admin',
            'email' => 'customer-po-search-admin@alami.test',
        ]);

        CustomerPo::create(['name' => 'ALAMI Jogja']);
        CustomerPo::create(['name' => 'ALAMI Solo']);
        CustomerPo::create(['name' => 'Bukan Match']);

        $response = $this->actingAs($user)->getJson(route('customer-po.index', [
            'q' => 'alami',
        ]));

        $response->assertOk()
            ->assertJson([
                'results' => [
                    [
                        'id' => 'ALAMI Jogja',
                        'text' => 'ALAMI Jogja',
                    ],
                    [
                        'id' => 'ALAMI Solo',
                        'text' => 'ALAMI Solo',
                    ],
                ],
            ]);

        $response->assertJsonMissing([
            'id' => 'Bukan Match',
            'text' => 'Bukan Match',
        ]);
    }

    public function test_customer_po_options_route_returns_matching_options(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-options-admin',
            'email' => 'customer-po-options-admin@alami.test',
        ]);

        CustomerPo::create(['name' => 'PT Options Customer']);
        CustomerPo::create(['name' => 'PT Other Customer']);

        $response = $this->actingAs($user)->getJson(route('customer-po.options', [
            'q' => 'Options',
        ]));

        $response->assertOk()
            ->assertJson([
                'results' => [
                    [
                        'id' => 'PT Options Customer',
                        'text' => 'PT Options Customer',
                    ],
                ],
            ]);

        $response->assertJsonMissing([
            'id' => 'PT Other Customer',
            'text' => 'PT Other Customer',
        ]);
    }

    public function test_pembelian_customer_po_options_route_returns_matching_options(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'pembelian-customer-po-options-admin',
            'email' => 'pembelian-customer-po-options-admin@alami.test',
        ]);

        CustomerPo::create(['name' => 'PT Pembelian Options Customer']);
        CustomerPo::create(['name' => 'PT Pembelian Other Customer']);

        $response = $this->actingAs($user)->getJson(route('pembelian.customer-po-options', [
            'q' => 'Options',
        ]));

        $response->assertOk()
            ->assertJson([
                [
                    'name' => 'PT Pembelian Options Customer',
                ],
            ]);

        $response->assertJsonMissing([
            'name' => 'PT Pembelian Other Customer',
        ]);
    }

    public function test_customer_po_options_route_includes_existing_pembelian_history(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-options-history-admin',
            'email' => 'customer-po-options-history-admin@alami.test',
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Customer PO Options History',
            'kode_supplier' => 'S-CPO-OH',
        ]);

        \App\Models\Pembelian::create([
            'code' => 'PO-CPO-OH-001',
            'customer_po' => 'PT Options From PO History',
            'supplier_id' => $supplier->id,
            'total' => 0,
            'is_published' => false,
            'owner_approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->getJson(route('customer-po.options', [
            'q' => 'History',
        ]));

        $response->assertOk()
            ->assertJson([
                'results' => [
                    [
                        'id' => 'PT Options From PO History',
                        'text' => 'PT Options From PO History',
                    ],
                ],
            ]);
    }

    public function test_index_with_search_query_returns_json_without_json_headers(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-query-admin',
            'email' => 'customer-po-query-admin@alami.test',
        ]);

        CustomerPo::create(['name' => 'PT Searchable']);

        $response = $this->actingAs($user)->get(route('customer-po.index', [
            'q' => 'Search',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');
        $response->assertSee('PT Searchable');
    }

    public function test_pembelian_create_page_loads_customer_po_options_from_ajax_route(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-form-admin',
            'email' => 'customer-po-form-admin@alami.test',
        ]);

        CustomerPo::create(['name' => 'PT Existing On Form']);

        $response = $this->actingAs($user)->get(route('pembelian.create'));

        $response->assertOk();
        $response->assertDontSee('<option value="PT Existing On Form"', false);
        $response->assertSee('class="form-control customer-po-select"', false);
        $response->assertSee('data-options-url="'.route('pembelian.customer-po-options').'"', false);
        $response->assertSee('data-selected-customer-po=""', false);
        $response->assertDontSee('id="customer_po"', false);
        $response->assertDontSee('class="form-control select2 customer-po-select"', false);
        $response->assertDontSee('tags: true', false);
        $response->assertDontSee('createTag', false);
        $response->assertSee(route('customer-po.index'));
        $response->assertSee(route('customer-po.store'));
        $response->assertSee('id="modalCustomerPo"', false);
        $response->assertSee('id="customerPoModalForm"', false);
        $response->assertSee('id="btnSaveCustomerPo"', false);
        $response->assertSee('initializeCustomerPoSelect', false);
        $response->assertSee('initializeCustomerPoSelectWidget', false);
        $response->assertSee('loadCustomerPoOptions', false);
        $response->assertSee('notifyCustomerPo', false);
        $response->assertDontSee('ajax: {', false);
    }

    public function test_pembelian_create_page_does_not_render_customer_po_history_options(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-history-admin',
            'email' => 'customer-po-history-admin@alami.test',
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Customer PO History',
            'kode_supplier' => 'S-CPO-H',
        ]);

        \App\Models\Pembelian::create([
            'code' => 'PO-CPO-H-001',
            'customer_po' => 'PT Existing From PO History',
            'supplier_id' => $supplier->id,
            'total' => 0,
            'is_published' => false,
            'owner_approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('pembelian.create'));

        $response->assertOk();
        $response->assertDontSee('<option value="PT Existing From PO History"', false);
        $response->assertSee('data-options-url="'.route('pembelian.customer-po-options').'"', false);
    }

    public function test_pembelian_store_persists_submitted_customer_po_value(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-submit-admin',
            'email' => 'customer-po-submit-admin@alami.test',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Kategori Customer PO Submit',
            'type' => 'product',
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Customer PO Submit',
            'kode_supplier' => 'S-CPO-S',
        ]);

        $product = \App\Models\Product::create([
            'code' => 'CPO-SUBMIT-001',
            'name' => 'Produk Customer PO Submit',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 1000,
            'harga_jual' => 1500,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
            'satuan_besar' => 'BOX',
            'konversi_qty' => 1,
        ]);

        $supplier->products()->attach($product->id);

        $response = $this->actingAs($user)->post(route('pembelian.store'), [
            'code' => 'PO-CPO-SUBMIT-001',
            'customer_po' => 'PT New Customer From Submit',
            'supplier_id' => $supplier->id,
            'total' => '1000',
            'product' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit' => 'BOX',
                    'harga_beli' => '1000',
                    'subtotal' => '1000',
                ],
            ],
        ]);

        $response->assertRedirect(route('pembelian.index'));
        $this->assertDatabaseHas('pembelians', [
            'code' => 'PO-CPO-SUBMIT-001',
            'customer_po' => 'PT New Customer From Submit',
        ]);
        $this->assertDatabaseHas('customer_pos', [
            'name' => 'PT New Customer From Submit',
        ]);
    }

    public function test_pembelian_edit_page_loads_customer_po_options_from_ajax_route(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'customer-po-edit-form-admin',
            'email' => 'customer-po-edit-form-admin@alami.test',
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Customer PO',
            'kode_supplier' => 'S-CPO',
        ]);

        $pembelian = \App\Models\Pembelian::create([
            'code' => 'PO-CPO-001',
            'customer_po' => 'PT Existing On Edit',
            'supplier_id' => $supplier->id,
            'total' => 0,
            'is_published' => false,
            'owner_approval_status' => 'approved',
        ]);

        CustomerPo::create(['name' => 'PT Existing On Edit']);
        CustomerPo::create(['name' => 'PT Existing On Edit Dropdown']);

        $response = $this->actingAs($user)->get(route('pembelian.edit', $pembelian));

        $response->assertOk();
        $response->assertSee('class="form-control customer-po-select"', false);
        $response->assertSee('data-options-url="'.route('pembelian.customer-po-options').'"', false);
        $response->assertSee('data-selected-customer-po="PT Existing On Edit"', false);
        $response->assertDontSee('id="customer_po"', false);
        $response->assertDontSee('class="form-control select2 customer-po-select"', false);
        $response->assertDontSee('tags: true', false);
        $response->assertDontSee('createTag', false);
        $response->assertSee(route('customer-po.store'));
        $response->assertSee('id="modalCustomerPo"', false);
        $response->assertSee('id="customerPoModalForm"', false);
        $response->assertSee('id="btnSaveCustomerPo"', false);
        $response->assertSee('initializeCustomerPoSelect', false);
        $response->assertSee('initializeCustomerPoSelectWidget', false);
        $response->assertSee('loadCustomerPoOptions', false);
        $response->assertSee('notifyCustomerPo', false);
        $response->assertDontSee('ajax: {', false);
        $response->assertDontSee('<option value="PT Existing On Edit"', false);
        $response->assertDontSee('<option value="PT Existing On Edit Dropdown"', false);
    }
}
