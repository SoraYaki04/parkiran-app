<?php

namespace App\Livewire\Parkir\Mobile;

use Livewire\Component;
use App\Models\ParkirSessions;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class Karcis extends Component
{
    public ParkirSessions $session;
    public string $qrExit;

    public function mount($token)
    {
        $this->session = ParkirSessions::with([
                'slot.area',
                'tipeKendaraan'
            ])
            ->where('exit_token', $token)
            ->where('status', 'IN_PROGRESS')
            ->firstOrFail();

        // ✅ QR ISI PLAT NOMOR
        $qrValue = $this->session->plat_nomor . '|' . $this->session->exit_token;
        $qr = new QrCode($qrValue);

        $png = (new PngWriter())->write($qr);
        $this->qrExit = 'data:image/png;base64,' . base64_encode($png->getString());
    }


    public function render()
    {
        return view('livewire.parkir.mobile.karcis')
            ->layout('layouts.mobile');
    }
}
