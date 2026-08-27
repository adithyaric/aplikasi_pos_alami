<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BranchSalesDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_can_store_multiple_optional_photos_and_an_osm_geotag(): void
    {
        Storage::fake('public');

        $branch = Outlet::create([
            'name' => 'Cabang Dokumentasi',
            'jenis_outlet' => 'branch',
        ]);
        $shop = Outlet::create([
            'name' => 'Toko Dokumentasi',
            'jenis_outlet' => 'toko',
        ]);
        $user = User::factory()->create([
            'role' => 'sales',
            'outlet_id' => $branch->id,
            'username' => 'sales-documentation',
            'email' => 'sales-documentation@alami.test',
        ]);
        $category = Category::create([
            'name' => 'Kategori Dokumentasi',
            'type' => 'product',
        ]);
        $product = Product::create([
            'code' => 'DOC-PRODUCT-001',
            'name' => 'Produk Dokumentasi',
            'category_id' => $category->id,
            'is_serialized' => false,
            'harga_beli' => 7000,
            'harga_jual' => 10000,
            'status_produk' => 'sudah',
            'satuan' => 'Pack',
        ]);

        OwnerStock::create([
            'owner_id' => $branch->id,
            'product_id' => $product->id,
            'stock_id' => null,
            'qty' => 10,
            'sku' => 'DOC-STOCK-001',
            'harga_beli' => 7000,
        ]);

        $this->actingAs($user)
            ->get(route('penjualan.create'))
            ->assertOk()
            ->assertSee('name="sales_photos[]"', false)
            ->assertSee('Ambil Lokasi Saat Ini')
            ->assertSee('OpenStreetMap');

        $response = $this->actingAs($user)->post(route('penjualan.store'), [
            'sale_date' => now()->toDateString(),
            'buyer_type' => 'toko',
            'outlet_target_id' => $shop->id,
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'latitude' => '-6.2000000',
            'longitude' => '106.8166667',
            'location_accuracy' => '12.50',
            'location_captured_at' => now()->toISOString(),
            'sales_photos' => [
                UploadedFile::fake()->image('customer-front.jpg', 120, 80),
                UploadedFile::fake()->image('delivery-proof.png', 120, 80),
            ],
            'items' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'unit' => 'Pack',
                'price' => '10.000',
            ]],
        ]);

        $sale = Penjualan::firstOrFail();

        $response->assertRedirect(route('penjualan.show', $sale));
        $this->assertSame('-6.2000000', $sale->latitude);
        $this->assertSame('106.8166667', $sale->longitude);
        $this->assertSame('12.50', $sale->location_accuracy);
        $this->assertCount(2, $sale->photos);

        foreach ($sale->photos as $photo) {
            Storage::disk('public')->assertExists($photo->path);
        }

        $this->actingAs($user)
            ->get(route('penjualan.show', $sale))
            ->assertOk()
            ->assertSee('OpenStreetMap')
            ->assertSee('Dokumentasi Penjualan');
    }
}
