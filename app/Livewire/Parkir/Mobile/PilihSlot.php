<?php

namespace App\Livewire\Parkir\Mobile;

use Livewire\Component;
use App\Models\ParkirSessions;
use App\Models\SlotParkir;
use App\Models\Kendaraan;
use App\Models\AreaParkir;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PilihSlot extends Component
{
    public string $sessionToken;
    public ?ParkirSessions $session = null;

    public ?int $selectedAreaId = null;
    public $areas;
    public $slots;
    public $slotId = null;

    public string $platNomor = '';

    public bool $invalidSession = false;
    public string $errorMessage = '';

    /* =====================
        MOUNT
    ===================== */
    public function mount($token)
    {
        $this->sessionToken = $token;

        $this->session = ParkirSessions::where('token', $token)
            ->whereIn('status', ['WAITING_INPUT', 'SCANNED'])
            ->where('expired_at', '>', now())
            ->first();

        if (!$this->session) {
            $this->invalidSession = true;
            $this->errorMessage = 'QR tidak valid / expired';
            return;
        }

        if ($this->session->status === 'WAITING_INPUT') {
            $this->session->update(['status' => 'SCANNED']);
        }

        $this->areas = AreaParkir::withCount('slots')
            ->orderBy('nama_area')
            ->get();

        $this->slots = collect();
    }

    /* =====================
        PILIH AREA
    ===================== */
    public function selectArea($areaId)
    {
        $this->selectedAreaId = $areaId;
        $this->slotId = null;

        $this->slots = SlotParkir::where('area_id', $areaId)
            ->where('tipe_kendaraan_id', $this->session->tipe_kendaraan_id)
            ->orderBy('baris')
            ->orderBy('kolom')
            ->get();

    }

    /* =====================
        PILIH SLOT
    ===================== */
    public function selectSlot($slotId)
    {
        if (
            SlotParkir::where('id', $slotId)
                ->where('area_id', $this->selectedAreaId)
                ->where('status', 'kosong')
                ->exists()
        ) {
            $this->slotId = $slotId;
        }
    }

    /* =====================
        NORMALISASI PLAT
        hasil: "AG 1353 KDT"
    ===================== */
    private function normalizePlat(string $input): string
    {
        $input = strtoupper(trim($input));
        $input = preg_replace('/[^A-Z0-9]/', '', $input);

        if (!preg_match('/^([A-Z]{1,2})(\d{1,5})([A-Z]{0,3})$/', $input, $m)) {
            throw new \Exception('Format plat tidak valid');
        }

        return trim($m[1] . ' ' . $m[2] . ' ' . ($m[3] ?? ''));
    }

    /* =====================
        KONFIRMASI
    ===================== */
    public function confirm()
    {
        if (!$this->selectedAreaId || !$this->slotId || !$this->platNomor) {
            $this->errorMessage = 'Data belum lengkap';
            return;
        }

        try {
            $platFormatted = $this->normalizePlat($this->platNomor);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        // 🚫 CEK BLACKLIST
        $existingVehicle = Kendaraan::where('plat_nomor', $platFormatted)->first();
        if ($existingVehicle && $existingVehicle->status === 'nonaktif') {
            $this->errorMessage = 'Kendaraan diblacklist';
            return;
        }

        $exitToken = strtoupper(Str::random(10));

        DB::transaction(function () use ($exitToken, $platFormatted) {

            $slot = SlotParkir::where('id', $this->slotId)
                ->where('area_id', $this->selectedAreaId)
                ->where('status', 'kosong')
                ->lockForUpdate()
                ->firstOrFail();

            // ⬇️ JANGAN sentuh status kendaraan
            $kendaraan = Kendaraan::updateOrCreate(
                ['plat_nomor' => $platFormatted],
                [
                    'tipe_kendaraan_id' => $this->session->tipe_kendaraan_id,
                    'slot_parkir_id'    => $slot->id,
                ]
            );

            $this->session->update([
                'slot_parkir_id' => $slot->id,
                'plat_nomor'     => $platFormatted,
                'status'         => 'IN_PROGRESS',
                'confirmed_at'   => now(),
                'exit_token'     => $exitToken,
            ]);

            $slot->update(['status' => 'terisi']);
        });

        return redirect()->route('mobile.karcis', [
            'token' => $exitToken
        ]);
    }

    public function render()
    {
        return view('livewire.parkir.mobile.pilih-slot')
            ->layout('layouts.mobile');
    }
}
