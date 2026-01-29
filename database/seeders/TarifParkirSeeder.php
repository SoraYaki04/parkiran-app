<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TarifParkir;

class TarifParkirSeeder extends Seeder
{
    public function run(): void
    {
        // ================= MOTOR =================
        TarifParkir::insert([
            [
                'tipe_kendaraan_id' => 1, // Motor
                'durasi_min' => 0,
                'durasi_max' => 60,
                'tarif' => 2000,
            ],
            [
                'tipe_kendaraan_id' => 1, // Motor
                'durasi_min' => 61,
                'durasi_max' => 120,
                'tarif' => 4000,
            ],
            [
                'tipe_kendaraan_id' => 1, // Motor
                'durasi_min' => 121,
                'durasi_max' => 1440, // 1 hari
                'tarif' => 6000,
            ],
        ]);

        // ================= MOBIL =================
        TarifParkir::insert([
            [
                'tipe_kendaraan_id' => 2, // Mobil
                'durasi_min' => 0,
                'durasi_max' => 60,
                'tarif' => 5000,
            ],
            [
                'tipe_kendaraan_id' => 2, // Mobil
                'durasi_min' => 61,
                'durasi_max' => 120,
                'tarif' => 10000,
            ],
            [
                'tipe_kendaraan_id' => 2, // Mobil
                'durasi_min' => 121,
                'durasi_max' => 1440,
                'tarif' => 15000,
            ],
        ]);

        // ================= BUS =================
        TarifParkir::insert([
            [
                'tipe_kendaraan_id' => 3, // Bus
                'durasi_min' => 0,
                'durasi_max' => 60,
                'tarif' => 10000,
            ],
            [
                'tipe_kendaraan_id' => 3, // Bus
                'durasi_min' => 61,
                'durasi_max' => 120,
                'tarif' => 20000,
            ],
            [
                'tipe_kendaraan_id' => 3, // Bus
                'durasi_min' => 121,
                'durasi_max' => 1440,
                'tarif' => 30000,
            ],
        ]);
    }
}
