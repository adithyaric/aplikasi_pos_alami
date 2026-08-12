<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Outlet;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PenjualanNotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_sale_can_be_rendered_as_a_printable_receipt(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings.json', json_encode([
            'name' => 'PR. Tunas Mandiri',
            'address' => "Jl. Ahmad Dahlan RT.04/12\nBarehan Sidoharjo",
            'telp' => '0812341',
            'logo' => null,
        ]));

        $user = User::factory()->create(['role' => 'superadmin']);
        $branch = Outlet::create(['name' => 'BLUZY', 'jenis_outlet' => 'branch']);
        $shop = Outlet::create(['name' => 'Sumber Rejeki', 'jenis_outlet' => 'toko']);

        $penjualan = Penjualan::create([
            'code' => 'CBG.0001.08.26',
            'sale_channel' => 'branch',
            'buyer_type' => 'toko',
            'buyer_id' => $shop->id,
            'buyer_name' => 'Budi Santoso',
            'outlet_id' => $branch->id,
            'user_id' => $user->id,
            'sale_date' => '2026-08-10',
            'payment_type' => 'cash',
            'payment_status' => 'paid',
            'total' => 240500,
        ]);

        foreach ([
            ['TM Alami', 5, 15000, 75000],
            ['Bluzy', 3, 12500, 37500],
            ['Oriental Class', 2, 20000, 40000],
            ['Twin Gold', 4, 18000, 72000],
            ['PS Mild', 1, 16000, 16000],
        ] as [$name, $qty, $price, $subtotal]) {
            $product = Product::create(['name' => $name, 'satuan' => 'Pack']);
            PenjualanItem::create([
                'penjualan_id' => $penjualan->id,
                'product_id' => $product->id,
                'qty' => $qty,
                'qty_input' => $qty,
                'unit' => 'Pack',
                'price' => $price,
                'subtotal' => $subtotal,
            ]);
        }

        $response = $this->actingAs($user)->get(route('laporan.penjualan.nota', $penjualan));

        $response->assertOk()
            ->assertSee('PR. Tunas Mandiri')
            ->assertSee('BLUZY')
            ->assertSee('Budi Santoso')
            ->assertSee('Sumber Rejeki')
            ->assertSee('5 x 15.000')
            ->assertSee('75.000')
            ->assertSee('Rp 240.500')
            ->assertSee('Terima Kasih');
    }

    public function test_warehouse_sale_uses_the_same_printable_receipt(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings.json', json_encode([
            'name' => 'PR. Tunas Mandiri',
            'address' => 'Pacitan',
            'telp' => '0812341',
        ]));

        $user = User::factory()->create(['role' => 'superadmin']);
        $agent = Agent::create(['name' => 'Agen Makmur']);
        $product = Product::create(['name' => 'TM Alami', 'satuan' => 'Pack']);
        $penjualan = Penjualan::create([
            'code' => '0001.08.26',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_id' => $agent->id,
            'buyer_name' => $agent->name,
            'user_id' => $user->id,
            'sale_date' => '2026-08-10',
            'payment_type' => 'cash',
            'payment_status' => 'paid',
            'total' => 15000,
        ]);
        PenjualanItem::create([
            'penjualan_id' => $penjualan->id,
            'product_id' => $product->id,
            'qty' => 1,
            'qty_input' => 1,
            'unit' => 'Pack',
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        $response = $this->actingAs($user)->get(route('laporan.penjualan.nota', $penjualan));

        $response->assertOk()
            ->assertSee('GUDANG')
            ->assertSee('Agen Makmur')
            ->assertSee('Rp 15.000');
    }

    public function test_print_nota_is_only_listed_for_branch_sales(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'superadmin']);
        $branch = Outlet::create(['name' => 'Cabang Nota', 'jenis_outlet' => 'branch']);
        $shop = Outlet::create(['name' => 'Toko Nota', 'jenis_outlet' => 'toko']);

        Penjualan::create([
            'code' => '0001.08.26',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'toko',
            'buyer_id' => $shop->id,
            'buyer_name' => $shop->name,
            'user_id' => $user->id,
            'sale_date' => '2026-08-10',
            'payment_type' => 'cash',
            'payment_status' => 'paid',
            'total' => 1000,
        ]);
        Penjualan::create([
            'code' => 'CBG.0001.08.26',
            'sale_channel' => 'branch',
            'buyer_type' => 'toko',
            'buyer_id' => $shop->id,
            'buyer_name' => $shop->name,
            'outlet_id' => $branch->id,
            'user_id' => $user->id,
            'sale_date' => '2026-08-10',
            'payment_type' => 'cash',
            'payment_status' => 'paid',
            'total' => 1000,
        ]);

        $this->actingAs($user)
            ->get(route('penjualan.index'))
            ->assertOk()
            ->assertDontSee('Print Nota');

        $this->actingAs($user)
            ->get(route('penjualan.branch-index'))
            ->assertOk()
            ->assertSee('Print Nota');
    }
}
