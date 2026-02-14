<?php

namespace App\Livewire\Parkir;

use Livewire\Component;
use App\Models\TipeKendaraan;
use App\Models\AreaParkir;
use App\Models\AreaKapasitas;
use App\Models\SlotParkir;

class DisplayParkir extends Component
{

    public function getVehicleData()
    {
        return TipeKendaraan::all()->map(function ($tipe) {
            // Variabel penampung untuk progress bar per Tipe (akumulasi semua area)
            $totalTipeSlots = 0;
            $totalTipeUsed = 0;

            $areas = AreaParkir::all()->map(function ($area) use ($tipe, &$totalTipeSlots, &$totalTipeUsed) {
                // 1. Ambil kapasitas SPESIFIK untuk tipe ini di area ini
                $capacityRecord = AreaKapasitas::where('area_id', $area->id)
                    ->where('tipe_kendaraan_id', $tipe->id)
                    ->first();

                $totalSlots = $capacityRecord->kapasitas ?? 0;

                // 2. Hitung slot yang TERISI SPESIFIK tipe ini di area ini
                $usedSlots = SlotParkir::where('area_id', $area->id)
                    ->where('tipe_kendaraan_id', $tipe->id)
                    ->where('status', 'terisi')
                    ->count();

                // Akumulasi untuk header tipe
                $totalTipeSlots += $totalSlots;
                $totalTipeUsed += $usedSlots;

                // 3. Hitung ketersediaan dan persentase
                $available = max($totalSlots - $usedSlots, 0);
                $percent = $totalSlots > 0 ? round(($usedSlots / $totalSlots) * 100) : 0;

                // Tentukan style warna berdasarkan persentase
                $style = $this->getStyleByPercent($percent);

                return [
                    'nama_area'   => $area->nama_area,
                    'total_slots' => $totalSlots,
                    'used_slots'  => $usedSlots, // Tambahan untuk tracking
                    'available'   => $available,
                    'percent'     => $percent,
                    'style'       => $style,
                ];
            })->filter(fn ($area) => $area['total_slots'] > 0)->values();

            $tipePercent = $totalTipeSlots > 0 ? round(($totalTipeUsed / $totalTipeSlots) * 100) : 0;

            return [
                'nama_tipe'    => $tipe->nama_tipe,
                'total_slots'  => $totalTipeSlots,
                'used_slots'   => $totalTipeUsed,
                'tipe_percent' => $tipePercent,
                'areas'        => $areas,
            ];
        })->filter(fn ($tipe) => $tipe['total_slots'] > 0)->values();
    }

    private function getStyleByPercent($percent) {
        if ($percent >= 90) {
            return ['color' => 'bg-red-500', 'glow' => 'shadow-[0_0_15px_rgba(239,68,68,0.5)]'];
        } elseif ($percent >= 70) {
            return ['color' => 'bg-yellow-400', 'glow' => 'shadow-[0_0_15px_rgba(250,204,21,0.5)]'];
        }
        return ['color' => 'bg-emerald-500', 'glow' => 'shadow-[0_0_15px_rgba(16,185,129,0.5)]'];
    }

    public function render()
    {
        return view('livewire.display-parkir', [
            'vehicleData' => $this->getVehicleData()
        ])->layout('layouts.guest');
    }
}
