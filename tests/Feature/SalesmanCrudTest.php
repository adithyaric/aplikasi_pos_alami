<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Salesman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SalesmanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_salesman_store_creates_linked_sales_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'salesman-admin@alami.test',
        ]);

        $outlet = Outlet::create([
            'name' => 'Cabang Sales',
            'jenis_outlet' => 'branch',
            'alamat' => 'Yogyakarta',
        ]);

        $response = $this->actingAs($admin)->post(route('salesman.store'), [
            'code' => 'SLS-001',
            'name' => 'Sales ALAMI 1',
            'alamat' => 'Jl. Malioboro',
            'no_telp' => '081234567890',
            'email' => 'sales1@alami.test',
            'outlet_id' => $outlet->id,
            'password' => 'secret123',
            'confirm-password' => 'secret123',
        ]);

        $response->assertRedirect(route('salesman.index'));

        $salesman = Salesman::with('user')->firstOrFail();

        $this->assertSame('SLS-001', $salesman->code);
        $this->assertNotNull($salesman->user_id);
        $this->assertSame('sales', $salesman->user->role);
        $this->assertSame('sales1@alami.test', $salesman->user->email);
        $this->assertSame('081234567890', $salesman->user->no_telp);
        $this->assertSame($outlet->id, (int) $salesman->user->outlet_id);
        $this->assertTrue(Hash::check('secret123', $salesman->user->password));
    }

    public function test_salesman_update_refreshes_linked_user_data_and_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'salesman-update-admin@alami.test',
        ]);

        $oldOutlet = Outlet::create([
            'name' => 'Cabang Lama',
            'jenis_outlet' => 'branch',
            'alamat' => 'Bantul',
        ]);

        $newOutlet = Outlet::create([
            'name' => 'Cabang Baru',
            'jenis_outlet' => 'branch',
            'alamat' => 'Sleman',
        ]);

        $user = User::factory()->create([
            'name' => 'Sales Lama',
            'role' => 'sales',
            'email' => 'sales-old@alami.test',
            'no_telp' => '081111111111',
            'outlet_id' => $oldOutlet->id,
            'password' => bcrypt('oldsecret'),
        ]);

        $salesman = Salesman::create([
            'code' => 'SLS-OLD',
            'name' => 'Sales Lama',
            'alamat' => 'Alamat Lama',
            'no_telp' => '081111111111',
            'outlet_id' => $oldOutlet->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin)->put(route('salesman.update', $salesman), [
            'code' => 'SLS-NEW',
            'name' => 'Sales Baru',
            'alamat' => 'Alamat Baru',
            'no_telp' => '082222222222',
            'email' => 'sales-new@alami.test',
            'outlet_id' => $newOutlet->id,
            'password' => 'newsecret123',
            'confirm-password' => 'newsecret123',
        ]);

        $response->assertRedirect(route('salesman.index'));

        $salesman->refresh();
        $user->refresh();

        $this->assertSame('SLS-NEW', $salesman->code);
        $this->assertSame('Sales Baru', $salesman->name);
        $this->assertSame('082222222222', $salesman->no_telp);
        $this->assertSame($newOutlet->id, (int) $salesman->outlet_id);

        $this->assertSame('Sales Baru', $user->name);
        $this->assertSame('sales-new@alami.test', $user->email);
        $this->assertSame('082222222222', $user->no_telp);
        $this->assertSame($newOutlet->id, (int) $user->outlet_id);
        $this->assertTrue(Hash::check('newsecret123', $user->password));
    }
}
