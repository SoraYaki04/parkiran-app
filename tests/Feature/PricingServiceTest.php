<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_time_based_pricing()
    {
        // 1. Setup Data
        $type = \App\Models\TipeKendaraan::create(['nama_tipe' => 'Mobil', 'kode_tipe' => 'MBL']);
        
        \App\Models\PricingRule::create([
            'name' => 'Hourly Rate',
            'type' => 'TIME_BASED',
            'config' => [
                'first_hour' => 5000,
                'next_hour' => 3000,
                'max_daily' => 25000
            ],
            'vehicle_type_id' => $type->id,
            'is_active' => true,
            'priority' => 10
        ]);

        $session = \App\Models\ParkirSessions::create([
            'token' => 'ABC',
            'tipe_kendaraan_id' => $type->id,
            'plat_nomor' => 'B 1234 CD',
            'status' => 'IN_PROGRESS',
            'confirmed_at' => now()->subMinutes(130), // 2 hours 10 mins -> 3 hours charged
        ]);

        // 2. Execute
        $service = new \App\Services\PricingService();
        $result = $service->calculate($session);

        // 3. Assert
        // 1st hour: 5000
        // 2nd hour: 3000
        // 3rd hour: 3000
        // Total: 11000
        $this->assertEquals(11000, $result['base_price']);
        $this->assertEquals('Hourly Rate', $result['applied_rule']);
    }

    public function test_duration_based_pricing()
    {
        $type = \App\Models\TipeKendaraan::create(['nama_tipe' => 'Motor', 'kode_tipe' => 'MTR']);
        
        \App\Models\PricingRule::create([
            'name' => 'Progressive Rate',
            'type' => 'DURATION_BASED',
            'config' => [
                'tiers' => [
                    ['max' => 60, 'price' => 2000],  // <= 1 hour
                    ['max' => 120, 'price' => 3000], // <= 2 hours
                    ['max' => 180, 'price' => 5000], // <= 3 hours
                ]
            ],
            'vehicle_type_id' => $type->id,
            'is_active' => true,
        ]);

        $session = \App\Models\ParkirSessions::create([
            'token' => 'XYZ',
            'tipe_kendaraan_id' => $type->id,
            'plat_nomor' => 'B 5678 EF',
            'status' => 'IN_PROGRESS',
            'confirmed_at' => now()->subMinutes(90), // 1.5 hours
        ]);

        $service = new \App\Services\PricingService();
        $result = $service->calculate($session);

        // Should fall into 2nd tier (<= 120 mins)
        $this->assertEquals(3000, $result['base_price']);
    }

    public function test_member_discount()
    {
        $type = \App\Models\TipeKendaraan::create(['nama_tipe' => 'Mobil', 'kode_tipe' => 'MBL']);
        
        // Rule: Flat 10000
        \App\Models\PricingRule::create([
            'name' => 'Flat Rate',
            'type' => 'FLAT',
            'config' => ['price' => 10000],
            'vehicle_type_id' => $type->id,
            'is_active' => true,
        ]);

        // Create Member
        $tier = \App\Models\TierMember::create([
            'nama' => 'Gold',
            'harga' => 100000,
            'periode' => 'bulanan',
            'diskon_persen' => 10,
            'status' => 'aktif'
        ]);
        $vehicle = \App\Models\Kendaraan::create([
            'plat_nomor' => 'B 9999 XX',
            'tipe_kendaraan_id' => $type->id,
            'status' => 'aktif'
        ]);

        $member = \App\Models\Member::create([
            'kode_member' => 'MBR001',
            'nama' => 'John Doe',
            'email' => 'john@example.com', 
            'no_hp' => '08123456789',
            'tier_member_id' => $tier->id,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(25),
            'kendaraan_id' => $vehicle->id, // Linked here
        ]);

        $session = \App\Models\ParkirSessions::create([
            'token' => 'MEMBER',
            'tipe_kendaraan_id' => $type->id,
            'plat_nomor' => 'B 9999 XX',
            'status' => 'IN_PROGRESS',
            'confirmed_at' => now()->subMinutes(60),
        ]);

        $service = new \App\Services\PricingService();
        $result = $service->calculate($session);

        $this->assertEquals(10000, $result['base_price']);
        $this->assertEquals(10, $result['discount_percent']);
        $this->assertEquals(1000, $result['discount_nominal']); // 10% of 10000
        $this->assertEquals(9000, $result['final_price']);
    }
}
