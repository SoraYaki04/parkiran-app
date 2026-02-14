<?php

namespace Database\Seeders;

use App\Models\AreaParkir;
use App\Models\Kendaraan;
use App\Models\Member;
use App\Models\ParkirSessions;
use App\Models\Pembayaran;
use App\Models\SlotParkir;
use App\Models\TipeKendaraan;
use App\Models\TransaksiParkir;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnalyticsTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates data for analytics:
     * - Last 2 Months (covers forecast, peak hour monthly filter)
     * - Clear Peak Hour pattern (08:00 and 17:00)
     * - Clear Revenue Growth (daily transactions increase over time)
     * - Member vs Non-Member Revenue
     */
    public function run()
    {
        // Cleanup Old Data to ensure "CASH/KARTU/QRIS" purity
        $this->command->info('🧹 Cleanup: Clearing old transaction data...');
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('pembayaran')->truncate();
        \Illuminate\Support\Facades\DB::table('transaksi_parkir')->truncate();
        \Illuminate\Support\Facades\DB::table('parkir_sessions')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $this->command->info('🚀 Generating Analytics Test Data (CASH, KARTU, QRIS only)...');

        // 1. Ensure Basic Data Exists (Areas, Slots, Vehicles)
        // Check if we have TipeKendaraan
        $tipeMotor = TipeKendaraan::firstOrCreate(['kode_tipe' => 'MTR'], ['nama_tipe' => 'Motor']);
        $tipeMobil = TipeKendaraan::firstOrCreate(['kode_tipe' => 'MBL'], ['nama_tipe' => 'Mobil']);
        $types = [$tipeMotor, $tipeMobil];

        // Check/Create Dummy Area & Slot
        $area = AreaParkir::firstOrCreate(['kode_area' => 'TEST'], ['nama_area' => 'Area Test', 'lokasi_fisik' => 'Area Dummy', 'kapasitas_total' => 100]);
        $slot = SlotParkir::firstOrCreate(['kode_slot' => 'TEST-01'], ['area_id' => $area->id, 'baris' => 'X', 'kolom' => 1, 'tipe_kendaraan_id' => $tipeMotor->id, 'status' => 'kosong']);

        // Check/Create Tier & Create 50 Dummy Members
        // Fix: Use 'nama' column instead of 'tier' based on TierMember model
        $tier = \App\Models\TierMember::firstOrCreate(
            ['nama' => 'Gold'], 
            ['nama' => 'Gold', 'harga' => 50000, 'diskon_persen' => 10, 'masa_berlaku_hari' => 30, 'status' => 'aktif']
        );
        
        $members = [];
        for ($i = 1; $i <= 50; $i++) {
            $members[] = Member::firstOrCreate(
                ['kode_member' => 'MBR-' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'nama' => 'Member ' . $i, 
                    'no_hp' => '08' . rand(1000000000, 9999999999), 
                    'tier_member_id' => $tier->id, 
                    'status' => 'aktif',
                    'tanggal_mulai' => now(),
                    'tanggal_berakhir' => now()->addDays(30)
                ]
            );
        }

        // Check/Create Dummy Vehicle
        $moto = Kendaraan::firstOrCreate(['plat_nomor' => 'TEST-MTR'], ['tipe_kendaraan_id' => $tipeMotor->id, 'status' => 'aktif']);
        $car = Kendaraan::firstOrCreate(['plat_nomor' => 'TEST-MBL'], ['tipe_kendaraan_id' => $tipeMobil->id, 'status' => 'aktif']);
        
        // 2. Generate Transactions for Last 60 Days
        $startDate = Carbon::now()->subDays(60)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $totalSessions = 0;
        $totalRevenue = 0;

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            // Increasing trend: transactions per day grows from 20 to 100
            // Linear growth factor (0 to 1) based on progress
            $progress = $currentDate->diffInDays($startDate) / 60;
            $baseTransactions = 20 + ($progress * 80); // 20 -> 100
            
            // Add some noise (+/- 20%)
            $dailyTransactions = (int) ($baseTransactions * (rand(80, 120) / 100));

            for ($i = 0; $i < $dailyTransactions; $i++) {
                // Determine Entry Time based on Weighted Hour (Peak 08 & 17)
                $hour = $this->getWeightedHour();
                $minute = rand(0, 59);
                
                $entryTime = $currentDate->copy()->setTime($hour, $minute);
                
                // Duration: 30 minutes to 4 hours
                $durationMinutes = rand(30, 240);
                $exitTime = $entryTime->copy()->addMinutes($durationMinutes);
                
                // Ensure exit time is not in future if generating past data
                if ($exitTime->isFuture()) {
                    continue; // Skip future exits
                }

                // Random Vehicle Type
                $type = $types[array_rand($types)];
                $veh = ($type->id == $tipeMotor->id) ? $moto : $car;
                $plat = $veh->plat_nomor;
                
                // Calculate Fee (Simple logic)
                $rateFirst = ($type->kode_tipe == 'MTR') ? 2000 : 5000;
                $rateNext = ($type->kode_tipe == 'MTR') ? 1000 : 2000;
                
                $fee = $rateFirst;
                if ($durationMinutes > 60) {
                    $extraHours = ceil(($durationMinutes - 60) / 60);
                    $fee += $extraHours * $rateNext;
                }

                // 40% chance of Member
                $isMember = (rand(1, 100) <= 40);
                $finalFee = $fee;
                $trxMember = null;

                if ($isMember) {
                    $trxMember = $members[array_rand($members)];
                    $finalFee = $fee * 0.9; // 10% discount
                }

                // Create Parkir Session
                $session = ParkirSessions::create([
                    'token' => Str::uuid(),
                    'tipe_kendaraan_id' => $type->id,
                    'plat_nomor' => $plat,
                    'slot_parkir_id' => $slot->id,
                    'status' => 'finished',
                    'generated_at' => $entryTime,
                    'expired_at' => $entryTime->copy()->addMinutes(15), 
                    'confirmed_at' => $exitTime,
                    'exit_token' => Str::uuid(),
                ]);

                // Create Transaction
                $trx = TransaksiParkir::create([
                    'kode_karcis' => 'TRX-' . $session->id,
                    'parkir_session_id' => $session->id,
                    'kendaraan_id' => $veh->id,
                    'slot_parkir_id' => $slot->id,
                    'tipe_kendaraan_id' => $type->id,
                    'waktu_masuk' => $entryTime,
                    'waktu_keluar' => $exitTime,
                    'durasi_menit' => $durationMinutes,
                    'total_bayar' => $finalFee,
                    'operator' => 'Seeder',
                    'member_id' => $trxMember ? $trxMember->id : null,
                ]);

                // Create Payment
                Pembayaran::create([
                    'transaksi_parkir_id' => $trx->id,
                    'tarif_dasar' => $fee,
                    'diskon_persen' => $isMember ? 10 : 0,
                    'diskon_nominal' => $isMember ? ($fee - $finalFee) : 0,
                    'total_bayar' => $finalFee,
                    'metode_pembayaran' => ['CASH', 'KARTU', 'QRIS'][array_rand(['CASH', 'KARTU', 'QRIS'])],
                    'jumlah_bayar' => $finalFee,
                    'kembalian' => 0,
                    'tanggal_bayar' => $exitTime,
                ]);

                $totalSessions++;
                $totalRevenue += $finalFee;
            }

            $currentDate->addDay();
        }

        $this->command->info("✅ Generated {$totalSessions} sessions with Rp " . number_format($totalRevenue) . " revenue.");
        $this->command->info("   Date Range: " . $startDate->toDateString() . " to " . $endDate->toDateString());
    }

    private function getWeightedHour()
    {
        $r = rand(1, 100);
        if ($r <= 10) return rand(0, 5);   // Night
        if ($r <= 35) return rand(6, 9);   // Morning Peak
        if ($r <= 55) return rand(10, 15); // Midday
        if ($r <= 85) return rand(16, 19); // Evening Peak
        return rand(20, 23);               // Late Night
    }
}
