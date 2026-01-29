<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipeKendaraan;

class TipeKendaraanSeeder extends Seeder
{
    public function run(): void
    {
        TipeKendaraan::insert([
            [
                'kode_tipe' => 'MTR',
                'nama_tipe' => 'Motor',
            ],
            [
                'kode_tipe' => 'MBL',
                'nama_tipe' => 'Mobil',
            ],
            [
                'kode_tipe' => 'BUS',
                'nama_tipe' => 'Bus',
            ],
        ]);
    }
}
