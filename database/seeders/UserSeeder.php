<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => 'admin123', // AUTO di-hash oleh casts()
            'role_id' => 1,
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Petugas Parkir',
            'username' => 'petugas',
            'password' => 'petugas123',
            'role_id' => 2,
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Owner Parkir',
            'username' => 'owner',
            'password' => 'owner123',
            'role_id' => 3,
            'status' => 'active',
        ]);
    }
}
