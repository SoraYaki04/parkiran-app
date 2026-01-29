<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Kendaraan;
use App\Models\TarifParkir;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            TierMemberSeeder::class,
            TipeKendaraanSeeder::class,
            KendaraanSeeder::class,
            TarifParkirSeeder::class,
        ]);
    }
}
