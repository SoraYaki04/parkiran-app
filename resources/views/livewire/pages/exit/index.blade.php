<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ParkirSessions;
use App\Models\TarifParkir;
use App\Models\TransaksiParkir;
use App\Models\Pembayaran;
use App\Models\SlotParkir;
use App\Models\Kendaraan;
use App\Models\Member;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

new #[Layout('layouts.app')]
#[Title('Exit Gate')]
class extends Component
{
    public string $search = '';
    public ?ParkirSessions $session = null;

    public int $totalPrice = 0;
    public int $durationMinutes = 0;

    public int $durasiHari = 0;
    public int $durasiJam = 0;
    public int $durasiMenit = 0;

    public string $paymentMethod = 'cash';
    public int $bayar = 0; 
    public int $kembalian = 0;

    public ?Member $member = null;
    public int $diskonPersen = 0;
    public int $diskonNominal = 0;
    public int $totalBayarFinal = 0;


    public array $recentExits = [];

    public string $struk = '';

    public function mount()
    {
        $this->loadRecentExits();
    }

    /* ======================
        ACTIVITY LOGGER
    =======================*/
    private function logActivity(
        string $action,
        string $description,
        string $target = null,
        string $category = 'TRANSAKSI'
    ) {
        ActivityLog::log(
            action: $action,
            description: $description,
            target: $target,
            category: $category,
        );
    }

    public function loadRecentExits()
    {
        $this->recentExits = TransaksiParkir::with('kendaraan')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'plat_nomor' => $item->kendaraan->plat_nomor ?? '-',
                'waktu_keluar' => $item->waktu_keluar
                    ? Carbon::parse($item->waktu_keluar)->format('d/m/Y H:i')
                    : '-',
            ])
            ->toArray();
    }

    public function updatedSearch($value)
    {
        if (strlen($value) >= 5) {
            $this->searchVehicle();
        }
    }

    private function normalizePlat(string $input): string
    {
        $input = strtoupper(trim($input));
        $input = preg_replace('/[^A-Z0-9]/', '', $input);

        if (!preg_match('/^([A-Z]{1,2})(\d{1,5})([A-Z]{0,3})$/', $input, $m)) {
            throw new \Exception('Format plat tidak valid');
        }

        return trim($m[1] . ' ' . $m[2] . ' ' . ($m[3] ?? ''));
    }


    private function parseQr(string $value): array
    {
        if (str_contains($value, '|')) {
            [$plat, $token] = explode('|', $value, 2);
        } else {
            $plat = $value;
            $token = null;
        }

        // 🔥 normalize di SINI
        $plat = $this->normalizePlat($plat);

        return [
            'plat' => $plat,
            'token' => $token ? trim($token) : null,
        ];
    }


    private function generateStruk()
    {
        if (!$this->session) return;

        $kendaraan = Kendaraan::where('plat_nomor', $this->session->plat_nomor)
            ->where('status', 'aktif')
            ->firstOrFail();

        $operator = Auth::user()->name;

        $this->struk  = "==================\n";
        $this->struk .= "STRUK KELUAR PARKIR\n";
        $this->struk .= "==================\n";
        $this->struk .= "Plat: {$kendaraan->plat_nomor}\n";
        $this->struk .= "Jam Masuk: " . Carbon::parse($this->session->confirmed_at)->format('H:i') . "\n";
        $this->struk .= "Jam Keluar: " . now()->format('H:i') . "\n";
        $this->struk .= "Durasi: {$this->durasiJam} jam {$this->durasiMenit} menit\n\n";
        $this->struk .= "Tarif Dasar: Rp " . number_format($this->totalPrice,0,',','.') . "\n";

        if ($this->member) {
            $this->struk .= "Member: {$this->member->tier->nama}\n";
            $this->struk .= "Diskon ({$this->diskonPersen}%): - Rp "
                . number_format($this->diskonNominal,0,',','.') . "\n";
        }

        $this->struk .= "==================\n";
        $this->struk .= "TOTAL BAYAR: Rp " . number_format($this->totalBayarFinal,0,',','.') . "\n";
        $labelMetode = match ($this->paymentMethod) {
            'cash' => 'CASH',
            'card' => 'KARTU',
            'qris' => 'QRIS',
            default => strtoupper($this->paymentMethod),
        };

        $this->struk .= "Metode: {$labelMetode}\n";
        $this->struk .= "Tanggal: " . now()->format('d-m-Y') . "\n";
        $this->struk .= "Operator: {$operator}\n";

        $this->logActivity(
            'GENERATE_STRUK',
            "Struk parkir di-generate. Plat: {$kendaraan->plat_nomor}, Total Bayar: Rp ".number_format($this->totalBayarFinal,0,',','.').", Metode: ".strtoupper($this->paymentMethod),
            "Parkir Session ID: {$this->session->id}"
        );
    }


    public function searchVehicle()
    {
        try {
            $parsed = $this->parseQr($this->search);

            $query = ParkirSessions::with(['slot.area', 'tipeKendaraan'])
                ->where('status', 'IN_PROGRESS')
                ->where('plat_nomor', $parsed['plat']);

            if ($parsed['token']) {
                $query->where('exit_token', $parsed['token']);
            }

            $this->session = $query->first();

            if (!$this->session) {
                throw new \Exception('QR / Plat tidak valid');
            }

            $this->calculateBill();
            $this->generateStruk();
            $this->search = '';

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->search = '';
        }
    }


    private function calculateBill()
    {
        $masuk = $this->session->confirmed_at ?? $this->session->created_at;
        $this->durationMinutes = max(1, Carbon::parse($masuk)->diffInMinutes(now()));

        $this->durasiHari = intdiv($this->durationMinutes, 1440);
        $sisaMenitHari = $this->durationMinutes % 1440;
        $this->durasiJam = intdiv($sisaMenitHari, 60);
        $this->durasiMenit = $sisaMenitHari % 60;

        // ===== MEMBER =====
        $kendaraan = Kendaraan::with([
            'member' => function ($q) {
                $q->where('status', 'aktif')
                ->whereHas('tier', function ($t) {
                    $t->where('status', 'aktif');
                });
            },
            'member.tier'
        ])
        ->where('plat_nomor', $this->session->plat_nomor)
        ->first();

        $this->member = null;

        if (
            $kendaraan &&
            $kendaraan->member &&
            $kendaraan->member->status === 'aktif' &&
            now()->between(
                $kendaraan->member->tanggal_mulai,
                $kendaraan->member->tanggal_berakhir
            )
        ) {
            $this->member = $kendaraan->member;
        }


        // ===== TARIF NORMAL =====
        $tarifHarian = TarifParkir::where('tipe_kendaraan_id', $this->session->tipe_kendaraan_id)
            ->orderBy('durasi_max', 'desc')
            ->first();

        if (!$tarifHarian) {
            $this->totalPrice = 0;
            return;
        }

        $total = 0;

        if ($this->durasiHari > 0) {
            $total += $this->durasiHari * $tarifHarian->tarif;
        }

        if ($sisaMenitHari > 0) {
            $tarifSisa = TarifParkir::where('tipe_kendaraan_id', $this->session->tipe_kendaraan_id)
                ->where('durasi_min', '<=', $sisaMenitHari)
                ->where('durasi_max', '>=', $sisaMenitHari)
                ->first() ?? $tarifHarian;

            $total += $tarifSisa->tarif;
        }

        $this->totalPrice = $total;

        // ===== DISKON MEMBER =====
        $this->diskonPersen = 0;
        $this->diskonNominal = 0;

        if ($this->member && $this->member->tier) {
            $this->diskonPersen = $this->member->tier->diskon_persen;
            $this->diskonNominal = round($total * ($this->diskonPersen / 100));
        }

        $this->totalBayarFinal = max(0, $total - $this->diskonNominal);
    }


    public function setPayment(string $method)
    {
        $this->paymentMethod = $method;
        $this->generateStruk();
    }

    public function updatedBayar($value)
    {
        $this->bayar = (int) $value;
        $this->kembalian = max(0, $this->bayar - $this->totalBayarFinal);
        $this->generateStruk();
    }

public function finalizeExit()
{
    if (!$this->session) return;

    $kendaraan = Kendaraan::where('plat_nomor', $this->session->plat_nomor)
        ->where('status', 'aktif')
        ->firstOrFail();

    $operator = Auth::user()->name;

    DB::transaction(function () use ($kendaraan, $operator) {
        $transaksi = TransaksiParkir::create([
            'kode_karcis'        => 'OUT-' . strtoupper(Str::random(8)),
            'parkir_session_id'  => $this->session->id,
            'kendaraan_id'       => $kendaraan->id,
            'slot_parkir_id'     => $this->session->slot_parkir_id,
            'tipe_kendaraan_id'  => $this->session->tipe_kendaraan_id,
            'waktu_masuk'        => $this->session->confirmed_at,
            'waktu_keluar'       => now(),
            'durasi_menit'       => $this->durationMinutes,
            'total_bayar'        => $this->totalBayarFinal,
            'member_id'          => $this->member?->id,
            'operator'           => $operator,
        ]);

        Pembayaran::create([
            'transaksi_parkir_id' => $transaksi->id,
            'tarif_dasar'         => $this->totalPrice,
            'diskon_persen'       => $this->diskonPersen,
            'diskon_nominal'      => $this->diskonNominal,
            'total_bayar'         => $this->totalBayarFinal,
            'metode_pembayaran'   => strtoupper($this->paymentMethod),
            'tanggal_bayar'       => now(),
        ]);

        SlotParkir::where('id', $this->session->slot_parkir_id)
            ->update(['status' => 'kosong']);

        $this->session->update(['status' => 'FINISHED']);
        $kendaraan->update(['slot_parkir_id' => null]);

        // ============================
        // LOG AKTIVITAS TRANSAKSI KELUAR
        // ============================
        $memberInfo = $this->member ? ", Member: {$this->member->tier->nama}" : "";
        $this->logActivity(
            'EXIT_PARKIR',
            "Kendaraan keluar. Plat: {$kendaraan->plat_nomor}, Durasi: {$this->durasiJam}j {$this->durasiMenit}m, Total Bayar: Rp ".number_format($this->totalBayarFinal,0,',','.').", Metode: ".strtoupper($this->paymentMethod).$memberInfo,
            "Transaksi ID: {$transaksi->id}"
        );
    });

    $this->loadRecentExits();
    $this->resetSession();

    $this->dispatch('exit-success');
    session()->flash('success', 'Transaksi parkir selesai & gate terbuka!');
}


    public function resetSession()
    {
        $this->session = null;
        $this->bayar = 0;
        $this->kembalian = 0;
        $this->struk = '';
    }
};
?>

<main
    x-data="{ open: false, successModal: false }"
    @exit-success.window="
        successModal = true;
        setTimeout(() => successModal = false, 4000)
    "
    class="flex flex-1 overflow-hidden h-full relative bg-slate-100 dark:bg-slate-950">


    <div class="w-full h-full flex flex-col md:flex-row">
        
        <div class="w-full md:w-4/12 flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 md:p-6">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1 border-l-4 border-primary-500 pl-3">Gerbang Keluar</h1>
                <p class="text-slate-500 text-sm">Scan QR atau input plat manual</p>
            </div>

            <div class="flex flex-col gap-4 mb-10">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input 
                            wire:model.live="search" 
                            id="scanner-input"
                            class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 h-16 pl-5 pr-4 text-lg font-bold uppercase focus:border-primary-500 focus:ring-0 transition-all text-slate-900 dark:text-white" 
                            placeholder="INPUT PLAT"
                            oninput="formatPlatDash(this)"
                            @keydown.enter="$wire.searchVehicle()"
                        />
                    </div>
                        <button
                            @click="open = true; openScanner()"
                            type="button"
                            class="size-16 flex items-center justify-center bg-primary-500 hover:bg-primary-600 text-primary-950 rounded-xl transition-colors shadow-lg shadow-primary-500/20">
                            <span class="material-symbols-outlined font-bold">qr_code_scanner</span>
                        </button>
                </div>

                @if (session()->has('error'))
                    <div class="p-4 text-sm bg-red-50 text-red-600 rounded-xl border border-red-100 flex items-center gap-2 animate-shake">
                        <span class="material-symbols-outlined">warning</span> {{ session('error') }}
                    </div>
                @endif
            </div>

            <div class="flex-1 min-h-[100px] overflow-y-auto scrollbar-hide">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-widest">
                        Transaksi Sebelumnya
                    </h3>

                    {{-- TOMBOL SELENGKAPNYA --}}
                    <a
                        href="{{ route('data_parkir', ['tab' => 'selesai']) }}" wire:navigate
                        class="text-[10px] font-black uppercase tracking-widest
                            text-primary-500 hover:text-primary-600 transition"
                    >
                        Selengkapnya →
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentExits as $exit)
                        <div class="flex items-center justify-between p-4 rounded-xl
                                    bg-slate-50 dark:bg-slate-800/50
                                    border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-full bg-primary-100 dark:bg-primary-900/30
                                            flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary-600">
                                        history
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 uppercase">
                                        {{ $exit['plat_nomor'] }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        {{ $exit['waktu_keluar'] }}
                                    </span>
                                </div>
                            </div>

                            <span class="text-[10px] font-black px-2 py-1
                                        bg-emerald-500/10 text-emerald-600 rounded">
                                DONE
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">
                            Belum ada transaksi
                        </p>
                    @endforelse
                </div>
            </div>

        </div>

        <div class="flex-1 h-full flex flex-col overflow-hidden bg-slate-100 dark:bg-slate-950">
            @if($session)
                <div class="flex-1 overflow-y-auto p-6 lg:p-10 scrollbar-hide">
                    <div class="max-w-2xl mx-auto w-full space-y-6">
                        
                        <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <div class="p-5 md:p-8 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r from-primary-50/50 to-transparent dark:from-primary-500/5">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h2 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight">{{ $session->plat_nomor }}</h2>
                                        <p class="text-sm text-slate-500 font-bold uppercase mt-1">{{ $session->tipeKendaraan->nama_tipe ?? '-' }}</p>
                                    </div>
                                    <span
                                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase shadow-lg
                                        {{ $member?->tier
                                            ? 'bg-primary-500 text-primary-950 shadow-primary-500/20'
                                            : 'bg-slate-800 text-white'
                                        }}"
                                    >
                                        {{ $member?->tier->nama ?? 'Reguler' }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-800">
                                <div class="p-5 md:p-8 text-center">
                                    <p class="text-slate-400 text-[10px] font-black uppercase mb-2 tracking-widest">
                                        Total Tagihan
                                    </p>

                                    <div class="flex items-baseline justify-center gap-1">
                                        <span class="text-lg md:text-xl font-bold text-primary-600">Rp</span>
                                        <span class="text-3xl md:text-5xl font-black text-primary-600 tracking-tighter">
                                            {{ number_format($totalBayarFinal ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>

                                    {{-- INFO DISKON MEMBER --}}
                                    @if($member)
                                        <div class="mt-2 text-xs font-black text-primary-600 uppercase tracking-widest">
                                            Diskon {{ $diskonPersen }}%
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            Hemat Rp {{ number_format($diskonNominal,0,',','.') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="p-5 md:p-8 space-y-3">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-400 font-bold uppercase">Durasi</span>
                                        <span class="font-black dark:text-white text-slate-700">{{ $durasiHari > 0 ? $durasiHari.'d' : '' }} {{ $durasiJam }}j {{ $durasiMenit }}m</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-400 font-bold uppercase">Slot</span>
                                        <span class="font-black dark:text-white text-slate-700">{{ $session->slot->kode_slot ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Waktu Transaksi</h3>
                            <div class="relative space-y-8 before:absolute before:inset-0 before:ml-4 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-primary-200 before:via-slate-200 before:to-transparent dark:before:from-primary-900">
                                <div class="relative flex items-center justify-between pl-10">
                                    <div class="absolute left-0 size-8 bg-emerald-500 rounded-full border-4 border-white dark:border-slate-900 flex items-center justify-center shadow-sm">
                                        <span class="material-symbols-outlined text-white text-[16px]">login</span>
                                    </div>
                                    <span class="text-sm font-bold text-slate-500 uppercase">Jam Masuk</span>
                                    <span class="text-sm font-black dark:text-white italic">{{ Carbon::parse($session->confirmed_at)->format('d M Y, H:i') }}</span>
                                </div>
                                <div class="relative flex items-center justify-between pl-10">
                                    <div class="absolute left-0 size-8 bg-primary-500 rounded-full border-4 border-white dark:border-slate-900 flex items-center justify-center shadow-sm">
                                        <span class="material-symbols-outlined text-primary-950 text-[16px]">logout</span>
                                    </div>
                                    <span class="text-sm font-bold text-slate-500 uppercase">Jam Keluar</span>
                                    <span class="text-sm font-black dark:text-white italic">{{ now()->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="h-10"></div>
                    </div>
                </div>

                <div class="flex-shrink-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 p-4 md:p-6 lg:p-8 shadow-[0_-20px_50px_-15px_rgba(0,0,0,0.1)] z-10">
                    <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-6">
                        
                        <div class="w-full lg:w-auto space-y-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-center lg:text-left">Pilih Metode Pembayaran</p>
                            <div class="flex flex-wrap justify-center lg:justify-start gap-3">
                                @foreach(['cash', 'card', 'qris'] as $method)
                                    <button wire:click="setPayment('{{ $method }}')" 
                                        class="px-5 py-3 rounded-2xl border-2 transition-all duration-300 flex items-center gap-3 group {{ $paymentMethod == $method ? 'border-primary-500 bg-primary-500 text-primary-950 shadow-xl shadow-primary-500/30' : 'border-slate-100 dark:border-slate-800 text-slate-400 hover:border-primary-200' }}">
                                        <span class="material-symbols-outlined text-xl group-hover:scale-110 transition-transform">
                                            {{ $method == 'cash' ? 'payments' : ($method == 'card' ? 'credit_card' : 'qr_code_2') }}
                                        </span>
                                        <span class="text-xs font-black uppercase tracking-widest">{{ $method }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="w-full lg:w-auto flex items-center gap-4 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 pt-6 lg:pt-0 lg:pl-10">
                            <div class="flex gap-3 w-full lg:w-auto items-center">
                                @if($struk)
                                    <button
                                        type="button"
                                        onclick="printStruk(@js($struk))"
                                        class="size-[56px] flex-shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-2xl hover:bg-primary-100 hover:text-primary-700 transition-all flex items-center justify-center group"
                                        title="Cetak Struk"
                                    >
                                        <span class="material-symbols-outlined group-hover:rotate-12 transition-transform">print</span>
                                    </button>
                                @endif

                                <button wire:click="finalizeExit" wire:loading.attr="disabled"
                                    class="flex-1 lg:min-w-[280px] px-6 bg-primary-500 hover:bg-primary-600 disabled:bg-slate-300 text-primary-950 h-[56px] rounded-2xl text-sm font-black shadow-xl shadow-primary-500/30 transition-all active:scale-95 flex items-center justify-center gap-3 uppercase tracking-[0.1em] whitespace-nowrap">
                                    <span wire:loading.remove>Selesaikan & Buka Gate</span>
                                    <span wire:loading class="animate-spin size-5 border-2 border-primary-950/30 border-t-primary-950 rounded-full"></span>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            @else
                <div class="flex-1 flex flex-col items-center justify-center text-center p-10">
                    <div class="relative">
                        <div class="absolute inset-0 bg-primary-500 blur-[100px] opacity-20 animate-pulse"></div>
                        <span class="material-symbols-outlined text-[160px] text-primary-200 dark:text-slate-800 relative z-10">qr_code_scanner</span>
                    </div>
                    <h2 class="text-xl font-black text-slate-400 dark:text-slate-600 uppercase tracking-[0.5em] mt-8">Scan Siap</h2>
                    <p class="text-slate-400 text-xs mt-2 uppercase tracking-widest">Arahkan Karakter QR atau Ketik Plat Nomor di Samping</p>
                </div>
            @endif
        </div>

    </div>
    
    <div 
        x-show="open"
        x-transition
        x-cloak
        x-on:vehicle-found.window="open = false"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md"
    >
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-[40px] overflow-hidden shadow-2xl border border-primary-500/20">
            <div class="p-8 flex justify-between items-center">
                <h3 class="font-black text-xl dark:text-white uppercase tracking-tighter italic">
                    <span class="text-primary-500">Fast</span>Scanner
                </h3>
                <button
                    @click="open = false; closeScanner()"
                    class="size-10 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800"
                >
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="px-8 pb-8">
                <div id="reader" class="overflow-hidden rounded-3xl bg-black aspect-square border-4 border-primary-500"></div>
                <p class="text-center text-slate-500 text-xs mt-6 font-medium uppercase tracking-widest">Arahkan Kamera ke QR Ticket</p>
            </div>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div
        x-show="successModal"
        x-transition
        x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
    >
        <div class="bg-white dark:bg-slate-900 rounded-[32px] p-10 max-w-sm w-full text-center shadow-2xl border border-emerald-500/30">
            
            <div class="mx-auto mb-6 size-20 rounded-full bg-emerald-500/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-500 text-[48px]">check_circle</span>
            </div>

            <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wide">
                Transaksi Berhasil
            </h2>

            <p class="text-slate-500 text-sm mt-3">
                Kendaraan berhasil keluar dan gate telah dibuka.
            </p>

            <button
                @click="successModal = false"
                class="mt-8 w-full h-12 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-black uppercase tracking-widest shadow-lg shadow-emerald-500/30 transition-all"
            >
                OK
            </button>
        </div>
    </div>

</main>

@push('scripts')
    <script>

        //  format plat
        function formatPlatDash(el) {
            let value = el.value.toUpperCase();

            // hapus semua karakter selain huruf dan angka
            value = value.replace(/[^A-Z0-9]/g, '');
            console.log('Input dibersihkan:', value);

            let depan = '';
            let nomor = '';
            let belakang = '';

            let i = 0;

            // Bagian depan: huruf 1-2
            while(i < value.length && depan.length < 2 && /[A-Z]/.test(value[i])) {
                depan += value[i];
                i++;
            }
            console.log('Bagian depan (huruf):', depan, 'sampai index:', i);

            // Bagian tengah: angka 1-4 → hanya jika huruf depan ada
            if(depan.length > 0) {
                while(i < value.length && nomor.length < 4 && /[0-9]/.test(value[i])) {
                    nomor += value[i];
                    i++;
                }
            }
            console.log('Bagian tengah (angka):', nomor, 'sampai index:', i);

            // Bagian belakang: huruf 0-3 → hanya jika angka tengah ada
            if(nomor.length > 0) {
                while(i < value.length && belakang.length < 3 && /[A-Z]/.test(value[i])) {
                    belakang += value[i];
                    i++;
                }
            }
            console.log('Bagian belakang (huruf):', belakang, 'sampai index:', i);

            // sisa input yang diabaikan
            let sisa = value.slice(i);
            if(sisa.length > 0) console.log('Sisa input yang diabaikan:', sisa);

            // gabungkan dengan dash → hanya tambahkan dash jika bagian sebelumnya ada
            let hasil = depan;
            if(nomor) hasil += '-' + nomor;
            if(belakang) hasil += '-' + belakang;

            console.log('Hasil akhir:', hasil);

            el.value = hasil;
        }


        // scan kode qr
        let html5QrCode;
        let scanning = false;

        function openScanner() {
            html5QrCode = new Html5Qrcode("reader");

            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 15, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    if (scanning) return;
                    scanning = true;

                    @this.set('search', decodedText);
                    window.dispatchEvent(new Event('vehicle-found'));
                    closeScanner();
                }
            );
        }

        function closeScanner() {
            scanning = false;
            if (html5QrCode) {
                html5QrCode.stop();
            }
        }

        function printStruk(content) {
            if (!content) {
                alert('Struk kosong');
                return;
            }

            const win = window.open('', '', 'width=300,height=500');

            win.document.open();
            win.document.write(`
                <html>
                <head>
                    <title>Struk Parkir</title>
                    <style>
                        body {
                            font-family: monospace;
                            font-size: 12px;
                            margin: 0;
                            padding: 10px;
                        }
                        pre {
                            white-space: pre-wrap;
                        }
                    </style>
                </head>
                <body>
                    <pre>${content}</pre>
                </body>
                </html>
            `);
            win.document.close();

            win.focus();
            win.print();
            win.close();
        }
    </script>
@endpush

@push('styles')
    <style>
        #reader { border: none !important; }
        #reader video { object-fit: cover !important; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.2s ease-in-out 0s 2; }
        [x-cloak] { display: none !important; }
    </style>
@endpush