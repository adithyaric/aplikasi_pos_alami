<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_admin_can_view_categories_and_open_the_product_filter(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin-gudang',
            'email' => 'category-admin@alami.test',
        ]);
        $category = Category::create(['name' => 'Rokok Filter']);
        $product = Product::create([
            'code' => 'CAT-001',
            'name' => 'Produk Kategori Test',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 1000,
            'harga_jual' => 1500,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
        ]);

        $categoryPage = $this->actingAs($admin)->get(route('category.product.index'));

        $categoryPage->assertOk();
        $categoryPage->assertSee('Halaman Kategori');
        $categoryPage->assertSee('Rokok Filter');
        $categoryPage->assertSee('Lihat Produk');
        $categoryPage->assertSee(route('product.index', ['category_id' => $category->id]), false);

        $this->assertSame($category->id, $product->category_id);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin-gudang',
            'email' => 'category-delete-admin@alami.test',
        ]);
        $category = Category::create(['name' => 'Kategori Terpakai']);
        Product::create([
            'code' => 'CAT-002',
            'name' => 'Produk Terpakai',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 1000,
            'harga_jual' => 1500,
            'status_produk' => 'sudah',
            'satuan' => 'PCS',
        ]);

        $response = $this->actingAs($admin)->delete(route('category.destroy', $category));

        $response->assertRedirect();
        $response->assertSessionHas('toast_error');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }
}
