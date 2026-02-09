<?php

namespace Database\Seeders;

use App\Models\AreaParkir;
use App\Models\Kendaraan;
use App\Models\Member;
use App\Models\ParkirSessions;
use App\Models\Pembayaran;
use App\Models\SlotParkir;
use App\Models\TierMember;
use App\Models\TipeKendaraan;
use App\Models\TransaksiParkir;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LaporanSeeder extends Seeder
{
    /**
     * Seed data for Laporan & Analytics
     * Generates: Areas, Slots, Kendaraan, Members, ParkirSessions, TransaksiParkir, Pembayaran
     */
    public function run(): void
    {
        // ==================== AREA PARKIR ====================
        $areas = [
            ['kode_area' => 'A', 'nama_area' => 'Area Utama', 'lokasi_fisik' => 'Lantai 1 Depan', 'kapasitas_total' => 30],
            ['kode_area' => 'B', 'nama_area' => 'Area Belakang', 'lokasi_fisik' => 'Lantai 1 Belakang', 'kapasitas_total' => 20],
        ];

        foreach ($areas as $area) {
            AreaParkir::firstOrCreate(['kode_area' => $area['kode_area']], $area);
        }

        $areaUtama = AreaParkir::where('kode_area', 'A')->first();
        $areaBelakang = AreaParkir::where('kode_area', 'B')->first();

        // ==================== SLOT PARKIR ====================
        $tipeMotor = TipeKendaraan::where('kode_tipe', 'MTR')->first();
        $tipeMobil = TipeKendaraan::where('kode_tipe', 'MBL')->first();
        $tipeBus = TipeKendaraan::where('kode_tipe', 'BUS')->first();

        // Area A: 15 Motor, 10 Mobil, 5 Bus
        $this->createSlots($areaUtama, 'A', 15, $tipeMotor->id);
        $this->createSlots($areaUtama, 'B', 10, $tipeMobil->id);
        $this->createSlots($areaUtama, 'C', 5, $tipeBus->id);

        // Area B: 10 Motor, 10 Mobil
        $this->createSlots($areaBelakang, 'D', 10, $tipeMotor->id);
        $this->createSlots($areaBelakang, 'E', 10, $tipeMobil->id);

        // ==================== KENDARAAN ====================
        $kendaraanData = [];
        
        // 30 Motor
        for ($i = 1; $i <= 30; $i++) {
            $kendaraanData[] = [
                'plat_nomor' => 'B ' . rand(1000, 9999) . ' MTR',
                'tipe_kendaraan_id' => $tipeMotor->id,
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // 20 Mobil
        for ($i = 1; $i <= 20; $i++) {
            $kendaraanData[] = [
                'plat_nomor' => 'B ' . rand(1000, 9999) . ' MBL',
                'tipe_kendaraan_id' => $tipeMobil->id,
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // 5 Bus
        for ($i = 1; $i <= 5; $i++) {
            $kendaraanData[] = [
                'plat_nomor' => 'B ' . rand(1000, 9999) . ' BUS',
                'tipe_kendaraan_id' => $tipeBus->id,
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($kendaraanData as $data) {
            Kendaraan::firstOrCreate(['plat_nomor' => $data['plat_nomor']], $data);
        }

        $kendaraanList = Kendaraan::all();

        // ==================== MEMBERS (10 members) ====================
        $tierGold = TierMember::where('nama', 'like', '%Gold%')->first() ?? TierMember::first();
        
        if ($tierGold) {
            $memberKendaraan = $kendaraanList->take(10);
            foreach ($memberKendaraan as $idx => $kendaraan) {
                Member::firstOrCreate(
                    ['kendaraan_id' => $kendaraan->id],
                    [
                        'kode_member' => 'MBR' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                        'nama' => 'Member ' . ($idx + 1),
                        'no_hp' => '08' . rand(1000000000, 9999999999),
                        'tier_member_id' => $tierGold->id,
                        'tanggal_mulai' => now()->subMonths(3),
                        'tanggal_berakhir' => now()->addMonths(9),
                        'status' => 'aktif',
                    ]
                );
            }
        }

        $members = Member::all();
        $slots = SlotParkir::all();

        // ==================== TRANSAKSI DATA (Last 14 days) ====================
        $paymentMethods = ['tunai', 'kartu', 'e-wallet', 'qris'];

        for ($dayOffset = 13; $dayOffset >= 0; $dayOffset--) {
            $date = Carbon::today()->subDays($dayOffset);
            
            // Generate 10-30 transactions per day
            $transactionsPerDay = rand(10, 30);

            for ($t = 0; $t < $transactionsPerDay; $t++) {
                // Random hour (weighted towards peak hours 10-12, 17-19)
                $hour = $this->getWeightedHour();
                $minute = rand(0, 59);

                $waktuMasuk = $date->copy()->setTime($hour, $minute);
                $durasiMenit = rand(30, 180); // 30 min to 3 hours
                $waktuKeluar = $waktuMasuk->copy()->addMinutes($durasiMenit);

                // Pick random kendaraan and slot
                $kendaraan = $kendaraanList->random();
                $slot = $slots->where('tipe_kendaraan_id', $kendaraan->tipe_kendaraan_id)->random();

                // Check if this kendaraan has a member
                $member = $members->where('kendaraan_id', $kendaraan->id)->first();

                // Calculate tarif based on duration
                $tarif = $this->calculateTarif($kendaraan->tipe_kendaraan_id, $durasiMenit);

                // Create ParkirSession
                $session = ParkirSessions::create([
                    'token' => Str::uuid()->toString(),
                    'tipe_kendaraan_id' => $kendaraan->tipe_kendaraan_id,
                    'plat_nomor' => $kendaraan->plat_nomor,
                    'slot_parkir_id' => $slot->id,
                    'status' => 'FINISHED',
                    'generated_at' => $waktuMasuk->copy()->subMinutes(5),
                    'expired_at' => $waktuMasuk->copy()->addMinutes(30),
                    'exit_token' => Str::uuid()->toString(),
                    'confirmed_at' => $waktuKeluar,
                ]);

                // Create TransaksiParkir
                $transaksi = TransaksiParkir::create([
                    'kode_karcis' => 'TRX' . $date->format('Ymd') . str_pad($t + 1, 4, '0', STR_PAD_LEFT),
                    'parkir_session_id' => $session->id,
                    'kendaraan_id' => $kendaraan->id,
                    'slot_parkir_id' => $slot->id,
                    'tipe_kendaraan_id' => $kendaraan->tipe_kendaraan_id,
                    'waktu_masuk' => $waktuMasuk,
                    'waktu_keluar' => $waktuKeluar,
                    'durasi_menit' => $durasiMenit,
                    'total_bayar' => $tarif,
                    'member_id' => $member?->id,
                    'operator' => 'System',
                ]);

                // Create Pembayaran
                Pembayaran::create([
                    'transaksi_parkir_id' => $transaksi->id,
                    'tarif_dasar' => $tarif,
                    'diskon_persen' => $member ? 10 : 0,
                    'diskon_nominal' => $member ? (int)($tarif * 0.1) : 0,
                    'total_bayar' => $member ? (int)($tarif * 0.9) : $tarif,
                    'metode_pembayaran' => $paymentMethods[array_rand($paymentMethods)],
                    'jumlah_bayar' => $tarif,
                    'kembalian' => $member ? (int)($tarif * 0.1) : 0,
                    'tanggal_bayar' => $waktuKeluar,
                ]);
            }
        }

        $this->command->info('✅ Laporan Seeder completed!');
        $this->command->info('   - Areas: ' . AreaParkir::count());
        $this->command->info('   - Slots: ' . SlotParkir::count());
        $this->command->info('   - Kendaraan: ' . Kendaraan::count());
        $this->command->info('   - Members: ' . Member::count());
        $this->command->info('   - ParkirSessions: ' . ParkirSessions::count());
        $this->command->info('   - TransaksiParkir: ' . TransaksiParkir::count());
        $this->command->info('   - Pembayaran: ' . Pembayaran::count());
    }

    private function createSlots(AreaParkir $area, string $baris, int $jumlah, int $tipeKendaraanId): void
    {
        for ($i = 1; $i <= $jumlah; $i++) {
            SlotParkir::firstOrCreate(
                ['area_id' => $area->id, 'kode_slot' => $baris . $i],
                [
                    'baris' => $baris,
                    'kolom' => $i,
                    'tipe_kendaraan_id' => $tipeKendaraanId,
                    'status' => 'kosong',
                ]
            );
        }
    }

    private function getWeightedHour(): int
    {
        // Peak hours: 10-12, 17-19 (higher probability)
        $hours = array_merge(
            range(6, 9),   // Morning: 4 hours
            range(10, 12), range(10, 12), range(10, 12), // Peak morning: 3x weight
            range(13, 16), // Afternoon: 4 hours
            range(17, 19), range(17, 19), range(17, 19), // Peak evening: 3x weight
            range(20, 21)  // Night: 2 hours
        );
        return $hours[array_rand($hours)];
    }

    private function calculateTarif(int $tipeKendaraanId, int $durasiMenit): int
    {
        // Based on TarifParkirSeeder
        $tarifMap = [
            1 => [60 => 2000, 120 => 4000, 1440 => 6000], // Motor
            2 => [60 => 5000, 120 => 10000, 1440 => 15000], // Mobil
            3 => [60 => 10000, 120 => 20000, 1440 => 30000], // Bus
        ];

        $tarifLevels = $tarifMap[$tipeKendaraanId] ?? $tarifMap[1];

        foreach ($tarifLevels as $maxDurasi => $tarif) {
            if ($durasiMenit <= $maxDurasi) {
                return $tarif;
            }
        }

        return end($tarifLevels);
    }
}
