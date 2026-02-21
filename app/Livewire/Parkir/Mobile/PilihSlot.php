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


    protected function rules()
    {
        return [
            'selectedAreaId' => 'required|exists:area_parkir,id',
            'slotId'         => 'required|exists:slot_parkir,id',
            'platNomor'      => 'required|min:4|max:15',
        ];
    }

    protected function messages()
    {
        return [
            'selectedAreaId.required' => 'Area belum dipilih',
            'slotId.required'         => 'Slot belum dipilih',
            'platNomor.required'      => 'Plat nomor wajib diisi',
        ];
    }

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

        if (! $this->session) {
            $this->invalidSession = true;
            $this->errorMessage = 'QR tidak valid / expired';
            return;
        }

        if ($this->session->status === 'WAITING_INPUT') {
            $this->session->update(['status' => 'SCANNED']);
        }

        /**
         * 🔥 HANYA AREA AKTIF YANG DITAMPILKAN
         */
        $this->areas = AreaParkir::where('status', 'aktif')
            ->withCount('slots')
            ->orderBy('nama_area')
            ->get();

        $this->slots = collect();
    }

    /* =====================
        PILIH AREA
    ===================== */
    public function selectArea($areaId)
    {
        /**
         * 🔐 PROTEKSI BACKEND
         * Area harus AKTIF
         */
        $area = AreaParkir::where('id', $areaId)
            ->where('status', 'aktif')
            ->first();

        if (! $area) {
            $this->errorMessage = 'Area parkir tidak tersedia atau sedang maintenance';
            $this->selectedAreaId = null;
            $this->slots = collect();
            return;
        }

        $this->selectedAreaId = $areaId;
        $this->slotId = null;

        /**
         * Ambil slot sesuai area + tipe kendaraan
         */
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
    ===================== */
private function normalizePlat(string $input): string
{
    $input = strtoupper(trim($input));

    // Hapus semua selain huruf dan angka
    $clean = preg_replace('/[^A-Z0-9]/', '', $input);

    if (! preg_match('/^[A-Z]{1,2}\d{1,4}[A-Z]{0,3}$/', $clean)) {
        throw new \Exception('Format plat harus seperti: B 1234 ABC');
    }

    preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/', $clean, $m);

    $depan  = $m[1];
    $angka  = $m[2];
    $belakang = $m[3] ?? '';

    return trim($depan . ' ' . $angka . ' ' . $belakang);
}
    /* =====================
        KONFIRMASI
    ===================== */
    public function confirm()
    {
        $this->resetErrorBag();

        // 1️⃣ Validasi basic dulu
        $this->validate();

        // 2️⃣ Validasi format plat via normalizer
        try {
            $platFormatted = $this->normalizePlat($this->platNomor);
        } catch (\Exception $e) {
            $this->addError('platNomor', $e->getMessage());
            return;
        }

        // 3️⃣ Cek blacklist kendaraan
        $existingVehicle = Kendaraan::where('plat_nomor', $platFormatted)->first();

        if ($existingVehicle && $existingVehicle->status === 'nonaktif') {
            $this->addError('platNomor', 'Kendaraan diblacklist');
            return;
        }

        // 4️⃣ Pastikan slot masih kosong (double protection)
        $slotExists = SlotParkir::where('id', $this->slotId)
            ->where('area_id', $this->selectedAreaId)
            ->where('status', 'kosong')
            ->exists();

        if (! $slotExists) {
            $this->addError('slotId', 'Slot sudah terisi atau tidak valid');
            return;
        }

        $exitToken = strtoupper(Str::random(10));

        DB::transaction(function () use ($exitToken, $platFormatted) {

            $slot = SlotParkir::where('id', $this->slotId)
                ->where('area_id', $this->selectedAreaId)
                ->where('status', 'kosong')
                ->lockForUpdate()
                ->firstOrFail();

            // Simpan / update kendaraan
            Kendaraan::updateOrCreate(
                ['plat_nomor' => $platFormatted],
                [
                    'tipe_kendaraan_id' => $this->session->tipe_kendaraan_id,
                    'slot_parkir_id'    => $slot->id,
                    'status'            => 'aktif',
                ]
            );

            // Update session parkir
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
