<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role_id' => 1,
            'status' => 'aktif',
        ]);

        User::create([
            'name' => 'Petugas Parkir',
            'username' => 'petugas',
            'password' => Hash::make('petugas123'),
            'role_id' => 2,
            'status' => 'aktif',
        ]);

        User::create([
            'name' => 'Owner Parkir',
            'username' => 'owner',
            'password' => Hash::make('owner123'),
            'role_id' => 3,
            'status' => 'aktif',
        ]);
    }
}
