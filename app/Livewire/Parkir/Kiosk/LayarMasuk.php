<?php

namespace App\Livewire\Parkir\Kiosk;

use Livewire\Component;
use App\Models\ParkirSessions;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class LayarMasuk extends Component
{
    public string $state = 'idle';

    public ?ParkirSessions $session = null;
    public ?int $sessionId = null;
    public int $tipeKendaraanId = 1;

    public ?string $qrUrl = null;
    public int $countdown = 300;
    public int $successCountdown = 10; // detik sebelum reset

    /* ======================
        PILIH KENDARAAN
    =======================*/
    public function pilihKendaraan(int $tipe)
    {
        $this->tipeKendaraanId = $tipe;
    }

    /* ======================
        CREATE SESSION
    =======================*/
    public function createSession()
    {
        $session = ParkirSessions::create([
            'token'             => strtoupper(Str::random(8)),
            'tipe_kendaraan_id' => $this->tipeKendaraanId,
            'status'            => 'WAITING_INPUT',
            'expired_at'        => now()->addMinutes(5),
        ]);

        $this->session   = $session;
        $this->sessionId = $session->id;
        $this->state     = 'qr_generated';

        $url = route('mobile-parkir.pilih-slot', ['token' => $session->token]);

        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 260,
            margin: 5
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $this->qrUrl = 'data:image/png;base64,' . base64_encode($result->getString());
    }

    /* ======================
        POLLING STATUS
    =======================*/
    public function checkStatus()
    {
        if (!$this->sessionId) return;

        $this->session = ParkirSessions::find($this->sessionId);

        if (!$this->session) {
            $this->resetKiosk();
            return;
        }

        // ================= COUNTDOWN EXPIRED =================
        $diff = now()->diffInSeconds($this->session->expired_at, false);
        $this->countdown = max(0, $diff);

        if ($diff <= 0 && $this->session->status !== 'IN_PROGRESS') {
            $this->session->update(['status' => 'CANCELLED']);
            $this->resetKiosk();
            return;
        }

        // ================= STATUS HANDLING =================
        if ($this->session->status === 'SCANNED') {
            $this->state = 'waiting_input';
        }

        if ($this->session->status === 'IN_PROGRESS') {
            $this->state = 'success';

            // auto reset setelah success
            if ($this->successCountdown > 0) {
                $this->successCountdown--;
            } else {
                $this->resetKiosk();
            }
        }
    }


    /* ======================
        RESET KIOSK
    =======================*/
    public function resetKiosk()
    {
        $this->reset([
            'state',
            'session',
            'sessionId',
            'qrUrl',
            'countdown',
            'tipeKendaraanId',
            'successCountdown',
        ]);

        $this->state = 'idle';
        $this->countdown = 300;
        $this->successCountdown = 10;
        $this->tipeKendaraanId = 1;
    }


    public function render()
    {
        return view('livewire.parkir.kiosk.layar-masuk')
            ->layout('layouts.kiosk');
    }
}
