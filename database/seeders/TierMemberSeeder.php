<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TierMember;

class TierMemberSeeder extends Seeder
{
    public function run(): void
    {
        TierMember::insert([
            [
                'nama' => 'Bronze',
                'harga' => 50000,
                'periode' => 'bulanan',
                'diskon_persen' => 5,
                'status' => 'aktif',
            ],
            [
                'nama' => 'Silver',
                'harga' => 100000,
                'periode' => 'bulanan',
                'diskon_persen' => 10,
                'status' => 'aktif',
            ],
            [
                'nama' => 'Gold',
                'harga' => 250000,
                'periode' => 'tahunan',
                'diskon_persen' => 20,
                'status' => 'aktif',
            ],
        ]);
    }
}
