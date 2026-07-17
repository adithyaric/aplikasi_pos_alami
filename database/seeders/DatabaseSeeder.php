<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@mailinator.com'],
            [
                'name' => 'superadmin',
                'username' => 'superadmin@mailinator.com',
                'role' => 'superadmin',
                'status' => 'active',
                'alamat' => 'Yogyakarta',
                'no_telp' => '+62'.str_pad('3', 10, '0', STR_PAD_LEFT),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'remember_token' => Str::random(10),
            ]
        );

        $this->call([
            CurrentDistributionFlowSeeder::class,
        ]);
    }
}
