<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role_id' => 1, // admin
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Petugas Parkir',
                'username' => 'petugas',
                'password' => Hash::make('petugas123'),
                'role_id' => 2, // petugas
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Owner Parkir',
                'username' => 'owner',
                'password' => Hash::make('owner123'),
                'role_id' => 3, // owner
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
