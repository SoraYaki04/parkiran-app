<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kendaraan;

class KendaraanSeeder extends Seeder
{
    public function run(): void
    {
        Kendaraan::create([
            'plat_nomor' => 'AG 1234 AA',
            'tipe_kendaraan_id' => 1, // Motor
            'nama_pemilik' => 'Budi Santoso',
            'status' => 'aktif',
            'slot_parkir_id' => null,
        ]);

        Kendaraan::create([
            'plat_nomor' => 'AG 5678 BB',
            'tipe_kendaraan_id' => 2, // Mobil
            'nama_pemilik' => 'Siti Aminah',
            'status' => 'aktif',
            'slot_parkir_id' => null,
        ]);

        Kendaraan::create([
            'plat_nomor' => 'N 9999 ZZ',
            'tipe_kendaraan_id' => 3, // Truk
            'nama_pemilik' => 'PT Logistik Jaya',
            'status' => 'nonaktif',
            'slot_parkir_id' => null,
        ]);
    }
}
