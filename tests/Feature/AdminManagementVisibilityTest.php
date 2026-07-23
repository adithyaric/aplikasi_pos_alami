<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_form_hides_sales_role_option(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'admin-visibility@alami.test',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.create'));

        $response->assertOk();
        $response->assertDontSee('value="sales"', false);
    }

    public function test_admin_index_hides_sales_accounts(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'name' => 'Super Admin Visible',
            'email' => 'admin-index@alami.test',
        ]);

        User::factory()->create([
            'role' => 'sales',
            'name' => 'Sales Hidden',
            'email' => 'sales-hidden@alami.test',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.index'));

        $response->assertOk();
        $response->assertSee('Super Admin Visible');
        $response->assertDontSee('Sales Hidden');
    }

    public function test_sales_user_cannot_be_edited_from_admin_page(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'admin-edit@alami.test',
        ]);

        $salesUser = User::factory()->create([
            'role' => 'sales',
            'email' => 'sales-edit@alami.test',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.edit', $salesUser));

        $response->assertNotFound();
    }
}
