<?php

use App\Models\ParkirSessions;
use App\Models\Pembayaran;
use App\Models\TipeKendaraan;
use App\Models\AreaParkir;
use App\Models\SlotParkir;
use App\Models\TransaksiParkir;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ActivityLog;

new #[Layout('layouts.app')]
#[Title('Laporan & Analytics')]
class extends Component {

    public string $activeTab = 'harian';

    // Harian
    public string $tanggalHarian;

    // Rentang
    public string $tanggalMulai;
    public string $tanggalAkhir;

    public function mount()
    {
        $this->tanggalHarian = Carbon::today()->format('Y-m-d');
        $this->tanggalMulai = Carbon::today()->subDays(7)->format('Y-m-d');
        $this->tanggalAkhir = Carbon::today()->format('Y-m-d');
    }

    /* ======================
        ACTIVITY LOGGER
    =======================*/
    private function logActivity(
        string $action,
        string $description,
        string $target = null,
        string $category = 'LAPORAN'
    ) {
        ActivityLog::log(
            action: $action,
            description: $description,
            target: $target,
            category: $category,
        );
    }

    public function exportHarianPdf()
    {
        if ($this->activeTab === 'occupancy' || $this->activeTab === 'analytics') {
            return;
        }

        $this->logActivity(
            'EXPORT',
            'Export laporan harian PDF',
            $this->tanggalHarian
        );

        return redirect()->route('export.laporan-harian.pdf', [
            'tanggal' => $this->tanggalHarian
        ]);
    }

    public function exportHarianExcel()
    {
        if ($this->activeTab === 'occupancy' || $this->activeTab === 'analytics') {
            return;
        }

        $this->logActivity(
            'EXPORT',
            'Export laporan harian Excel',
            $this->tanggalHarian
        );

        return redirect()->route('export.laporan-harian.excel', [
            'tanggal' => $this->tanggalHarian
        ]);
    }

    public function exportRentangPdf()
    {
        if ($this->activeTab === 'occupancy' || $this->activeTab === 'analytics') {
            return;
        }

        $this->logActivity(
            'EXPORT',
            'Export laporan rentang PDF',
            "{$this->tanggalMulai} s/d {$this->tanggalAkhir}"
        );

        return redirect()->route('export.rentang.pdf', [
            'mulai' => $this->tanggalMulai,
            'akhir' => $this->tanggalAkhir
        ]);
    }

    public function exportRentangExcel()
    {
        if ($this->activeTab === 'occupancy' || $this->activeTab === 'analytics') {
            return;
        }

        $this->logActivity(
            'EXPORT',
            'Export laporan rentang Excel',
            "{$this->tanggalMulai} s/d {$this->tanggalAkhir}"
        );

        return redirect()->route('export.rentang.excel', [
            'mulai' => $this->tanggalMulai,
            'akhir' => $this->tanggalAkhir
        ]);
    }

    public function exportAnalyticsCsv()
    {
        $this->logActivity(
            'EXPORT',
            'Export analytics CSV',
            "{$this->tanggalMulai} s/d {$this->tanggalAkhir}"
        );

        return redirect()->route('export.analytics.csv', [
            'mulai' => $this->tanggalMulai,
            'akhir' => $this->tanggalAkhir
        ]);
    }

    /* ======================
        DATA LAPORAN HARIAN
    =======================*/
    public function getLaporanHarianProperty()
    {
        $tanggal = Carbon::parse($this->tanggalHarian);

        $sessions = ParkirSessions::whereDate('confirmed_at', $tanggal)
            ->where('status', 'finished')
            ->get();

        $pembayaran = Pembayaran::whereDate('tanggal_bayar', $tanggal)->get();

        $perTipe = ParkirSessions::whereDate('confirmed_at', $tanggal)
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get()
            ->map(function ($item) use ($tanggal) {
                $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
                $pendapatan = Pembayaran::whereDate('tanggal_bayar', $tanggal)
                    ->whereHas('transaksiParkir', function ($q) use ($item) {
                        $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id);
                    })
                    ->sum('total_bayar');
                $item->tipeKendaraan = $tipe;
                $item->pendapatan = $pendapatan;
                return $item;
            });

        $perMetode = Pembayaran::whereDate('tanggal_bayar', $tanggal)
            ->select('metode_pembayaran', DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy('metode_pembayaran')
            ->get();

        return [
            'total_transaksi' => $sessions->count(),
            'total_pendapatan' => $pembayaran->sum('total_bayar'),
            'per_tipe' => $perTipe,
            'per_metode' => $perMetode,
        ];
    }

    /* ======================
        DATA LAPORAN RENTANG
    =======================*/
    public function getLaporanRentangProperty()
    {
        $mulai = Carbon::parse($this->tanggalMulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggalAkhir)->endOfDay();

        $sessions = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->get();

        $pembayaran = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])->get();

        $perTipe = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get()
            ->map(function ($item) use ($mulai, $akhir) {
                $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
                $pendapatan = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
                    ->whereHas('transaksiParkir', function ($q) use ($item) {
                        $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id);
                    })
                    ->sum('total_bayar');
                $item->tipeKendaraan = $tipe;
                $item->pendapatan = $pendapatan;
                return $item;
            });

        $perMetode = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
            ->select('metode_pembayaran', DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy('metode_pembayaran')
            ->get();

        $perHari = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
            ->select(DB::raw('DATE(tanggal_bayar) as tanggal'), DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy(DB::raw('DATE(tanggal_bayar)'))
            ->orderBy('tanggal')
            ->get();

        return [
            'total_transaksi' => $sessions->count(),
            'total_pendapatan' => $pembayaran->sum('total_bayar'),
            'per_tipe' => $perTipe,
            'per_metode' => $perMetode,
            'per_hari' => $perHari,
        ];
    }

    /* ======================
        DATA OCCUPANCY
    =======================*/
    public function getOccupancyProperty()
    {
        $areas = AreaParkir::with(['slots', 'kapasitas.tipeKendaraan'])->get();

        $result = [];

        foreach ($areas as $area) {
            $totalSlots = $area->slots->count();
            $terisi = $area->slots->where('status', 'terisi')->count();
            $kosong = $area->slots->where('status', 'kosong')->count();
            $persentase = $totalSlots > 0 ? round(($terisi / $totalSlots) * 100, 1) : 0;

            $perTipe = $area->slots->groupBy('tipe_kendaraan_id')->map(function ($slots, $tipeId) {
                $tipe = TipeKendaraan::find($tipeId);
                return [
                    'nama_tipe' => $tipe ? $tipe->nama_tipe : 'Unknown',
                    'total' => $slots->count(),
                    'terisi' => $slots->where('status', 'terisi')->count(),
                    'kosong' => $slots->where('status', 'kosong')->count(),
                ];
            });

            $result[] = [
                'area' => $area,
                'total_slots' => $totalSlots,
                'terisi' => $terisi,
                'kosong' => $kosong,
                'persentase' => $persentase,
                'per_tipe' => $perTipe,
            ];
        }

        return $result;
    }

    /* ======================
        ANALYTICS DATA
    =======================*/

    // Revenue per day for last 7 days (for Bar Chart)
    public function getRevenueChartDataProperty()
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->translatedFormat('D, d M');
            $data[] = Pembayaran::whereDate('tanggal_bayar', $date)->sum('total_bayar');
        }

        return ['labels' => $labels, 'data' => $data];
    }

    // Vehicle Type Distribution (for Pie Chart)
    public function getVehicleDistributionProperty()
    {
        $mulai = Carbon::parse($this->tanggalMulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggalAkhir)->endOfDay();

        $distribution = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get()
            ->map(function ($item) {
                $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
                return [
                    'label' => $tipe ? $tipe->nama_tipe : 'Unknown',
                    'value' => $item->total,
                ];
            });

        return $distribution->values()->toArray();
    }

    // Peak Hour Analysis
    public function getPeakHourAnalysisProperty()
    {
        $mulai = Carbon::parse($this->tanggalMulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggalAkhir)->endOfDay();

        $peakHours = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->select(DB::raw('HOUR(confirmed_at) as hour'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('HOUR(confirmed_at)'))
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        $avgDuration = TransaksiParkir::whereBetween('waktu_masuk', [$mulai, $akhir])
            ->whereNotNull('durasi_menit')
            ->avg('durasi_menit');

        return [
            'peak_hours' => $peakHours,
            'avg_duration' => round($avgDuration ?? 0, 1),
        ];
    }

    // Member Performance
    public function getMemberPerformanceProperty()
    {
        $mulai = Carbon::parse($this->tanggalMulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggalAkhir)->endOfDay();

        $memberTransactions = TransaksiParkir::whereBetween('waktu_masuk', [$mulai, $akhir])
            ->whereNotNull('member_id')
            ->count();

        $nonMemberTransactions = TransaksiParkir::whereBetween('waktu_masuk', [$mulai, $akhir])
            ->whereNull('member_id')
            ->count();

        $memberRevenue = TransaksiParkir::whereBetween('waktu_masuk', [$mulai, $akhir])
            ->whereNotNull('member_id')
            ->join('pembayaran', 'transaksi_parkir.id', '=', 'pembayaran.transaksi_parkir_id')
            ->sum('pembayaran.total_bayar');

        $nonMemberRevenue = TransaksiParkir::whereBetween('waktu_masuk', [$mulai, $akhir])
            ->whereNull('member_id')
            ->join('pembayaran', 'transaksi_parkir.id', '=', 'pembayaran.transaksi_parkir_id')
            ->sum('pembayaran.total_bayar');

        return [
            'member_transactions' => $memberTransactions,
            'non_member_transactions' => $nonMemberTransactions,
            'member_revenue' => $memberRevenue,
            'non_member_revenue' => $nonMemberRevenue,
        ];
    }

    // Payment Method Distribution (for Pie Chart)
    public function getPaymentMethodDistributionProperty()
    {
        $mulai = Carbon::parse($this->tanggalMulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggalAkhir)->endOfDay();

        $distribution = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
            ->select('metode_pembayaran', DB::raw('sum(total_bayar) as total'))
            ->groupBy('metode_pembayaran')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => ucfirst($item->metode_pembayaran ?? 'Lainnya'),
                    'value' => $item->total,
                ];
            });

        return $distribution->values()->toArray();
    }

    // Occupancy Trend (for Line Chart)
    public function getOccupancyTrendProperty()
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->translatedFormat('D, d M');

            $finishedCount = ParkirSessions::whereDate('confirmed_at', $date)
                ->where('status', 'finished')
                ->count();

            $totalSlots = SlotParkir::count();
            $occupancyRate = $totalSlots > 0 ? round(($finishedCount / $totalSlots) * 100, 1) : 0;
            $data[] = min($occupancyRate, 100);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
};

?>

<div class="flex-1 flex-col h-full overflow-hidden flex">

    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-center">
        <div>
            <h2 class="text-white text-3xl font-black">Laporan & Analytics</h2>
            <p class="text-slate-400">Statistik, analisis pendapatan & visualisasi data</p>
        </div>

        {{-- EXPORT DROPDOWN --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.away="open = false"
                class="{{ ($activeTab === 'occupancy') ? 'opacity-50 cursor-not-allowed' : '' }} flex items-center gap-2 bg-primary text-black px-4 py-2 rounded-lg font-bold text-sm shadow-lg shadow-primary/20"
                @if($activeTab === 'occupancy') disabled @endif
                >
                <span class="material-symbols-outlined text-lg">download</span>
                Export
                <span class="material-symbols-outlined text-sm">expand_more</span>
            </button>

            <div x-show="open" x-transition 
                class="absolute right-0 mt-2 w-48 bg-[#1A202C] border border-[#3E4C59] rounded-xl shadow-2xl z-50 overflow-hidden">
                @if($activeTab === 'harian')
                    <button wire:click="exportHarianPdf"
                        class="w-full text-left flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm">
                        <span class="material-symbols-outlined text-red-400">picture_as_pdf</span>
                        PDF Harian
                    </button>

                    <button wire:click="exportHarianExcel"
                        class="w-full text-left flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm border-t border-gray-800">
                        <span class="material-symbols-outlined text-green-400">table_chart</span>
                        Excel Harian
                    </button>

                @elseif($activeTab === 'rentang')
                    <button wire:click="exportRentangPdf"
                        class="w-full text-left flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm">
                        <span class="material-symbols-outlined text-red-400">picture_as_pdf</span>
                        PDF Rentang
                    </button>

                    <button wire:click="exportRentangExcel"
                        class="w-full text-left flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm">
                        <span class="material-symbols-outlined text-green-400">table_chart</span>
                        Excel Rentang
                    </button>

                @elseif($activeTab === 'analytics')
                    <button wire:click="exportAnalyticsCsv"
                        class="w-full text-left flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm">
                        <span class="material-symbols-outlined text-blue-400">csv</span>
                        CSV Analytics
                    </button>

                @endif
            </div>
        </div>
    </header>

    {{-- ACTION BAR (TABS & FILTERS) --}}
    <div class="px-8 pt-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 print:hidden">
        
        {{-- LEFT: TABS --}}
        <div class="bg-surface-dark p-1.5 rounded-xl border border-[#3E4C59] inline-flex gap-1">
            <button wire:click="setTab('harian')"
                class="px-4 py-2 rounded-lg font-bold text-xs transition {{ $activeTab === 'harian' ? 'bg-primary text-black' : 'text-slate-400 hover:text-white' }}">
                Harian
            </button>
            <button wire:click="setTab('rentang')"
                class="px-4 py-2 rounded-lg font-bold text-xs transition {{ $activeTab === 'rentang' ? 'bg-primary text-black' : 'text-slate-400 hover:text-white' }}">
                Rentang
            </button>
            <button wire:click="setTab('occupancy')"
                class="px-4 py-2 rounded-lg font-bold text-xs transition {{ $activeTab === 'occupancy' ? 'bg-primary text-black' : 'text-slate-400 hover:text-white' }}">
                Occupancy
            </button>
            <button wire:click="setTab('analytics')"
                class="px-4 py-2 rounded-lg font-bold text-xs transition {{ $activeTab === 'analytics' ? 'bg-primary text-black' : 'text-slate-400 hover:text-white' }}">
                    Analytics
            </button>
        </div>

        {{-- RIGHT: DYNAMIC FILTERS --}}
        <div class="flex items-center gap-3 bg-surface-dark/50 p-1.5 rounded-xl border border-[#3E4C59]/50">
            @if($activeTab === 'harian')
                <div class="flex items-center gap-2 px-3">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pilih Tgl</span>
                    <input type="date" wire:model.live="tanggalHarian"
                        class="bg-transparent border-none text-white text-xs font-semibold focus:ring-1 focus:ring-primary [color-scheme:dark] cursor-pointer">
                </div>
            @endif

            @if($activeTab === 'rentang')
                <div class="flex items-center gap-2 px-3 border-r border-[#3E4C59]">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Dari</span>
                    <input type="date" wire:model.live="tanggalMulai"
                        class="bg-transparent border-none text-white text-xs font-semibold focus:ring-1 focus:ring-primary [color-scheme:dark] cursor-pointer">
                </div>
                <div class="flex items-center gap-2 px-3">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sampai</span>
                    <input type="date" wire:model.live="tanggalAkhir"
                        class="bg-transparent border-none text-white text-xs font-semibold focus:ring-1 focus:ring-primary [color-scheme:dark] cursor-pointer">
                </div>
            @endif

            @if($activeTab === 'occupancy')
                <div
                    wire:poll.5s
                    class="px-4 py-1.5 text-[10px] font-bold text-primary uppercase tracking-tighter italic">
                    Real-time Data Terupdate
                </div>
            @endif

            @if($activeTab === 'analytics')
                <div class="flex items-center gap-2 px-3 border-r border-[#3E4C59]">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Dari</span>
                    <input type="date" wire:model.live="tanggalMulai"
                        class="bg-transparent border-none text-white text-xs font-semibold focus:ring-1 focus:ring-primary [color-scheme:dark] cursor-pointer">
                </div>
                <div class="flex items-center gap-2 px-3">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sampai</span>
                    <input type="date" wire:model.live="tanggalAkhir"
                        class="bg-transparent border-none text-white text-xs font-semibold focus:ring-1 focus:ring-primary [color-scheme:dark] cursor-pointer">
                </div>
            @endif
        </div>
    </div>

    {{-- CONTENT AREA --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        
        <div class="space-y-6">
            {{-- SUMMARY CARDS (Lebih Kecil) --}}
            @if($activeTab !== 'occupancy' && $activeTab !== 'analytics')
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-surface-dark p-4 rounded-xl border border-[#3E4C59]">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Transaksi</p>
                    <p class="text-white text-xl font-black mt-1">
                        {{ number_format($activeTab === 'harian' ? $this->laporanHarian['total_transaksi'] : $this->laporanRentang['total_transaksi']) }}
                    </p>
                </div>
                <div class="bg-surface-dark p-4 rounded-xl border border-[#3E4C59] md:col-span-2">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Pendapatan</p>
                    <p class="text-green-400 text-xl font-black mt-1">
                        Rp {{ number_format($activeTab === 'harian' ? $this->laporanHarian['total_pendapatan'] : $this->laporanRentang['total_pendapatan'], 0, ',', '.') }}
                    </p>
                </div>
            </div>
            @endif

            {{-- TABLES / BREAKDOWN --}}
            @if($activeTab === 'harian' || $activeTab === 'rentang')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @php $data = ($activeTab === 'harian') ? $this->laporanHarian : $this->laporanRentang; @endphp
                
                {{-- Per Tipe --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden shadow-sm h-fit">
                    <div class="bg-gray-900/60 px-5 py-3 border-b border-[#3E4C59]">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest">Per Tipe Kendaraan</h3>
                    </div>
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-[#3E4C59]">
                            @foreach($data['per_tipe'] as $item)
                            <tr class="hover:bg-surface-hover transition-colors">
                                <td class="px-5 py-3 text-white font-medium text-sm">{{ $item->tipeKendaraan->nama_tipe ?? '-' }}</td>
                                <td class="px-5 py-3 text-center text-slate-400 text-xs">{{ $item->total }} Transaksi</td>
                                <td class="px-5 py-3 text-right text-green-400 font-bold text-sm">Rp{{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Per Metode --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden shadow-sm h-fit">
                    <div class="bg-gray-900/60 px-5 py-3 border-b border-[#3E4C59]">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest">Metode Pembayaran</h3>
                    </div>
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-[#3E4C59]">
                            @foreach($data['per_metode'] as $item)
                            <tr class="hover:bg-surface-hover transition-colors">
                                <td class="px-5 py-3 text-white font-medium text-sm capitalize">{{ $item->metode_pembayaran ?? '-' }}</td>
                                <td class="px-5 py-3 text-center text-slate-400 text-xs">{{ $item->total }} Kali</td>
                                <td class="px-5 py-3 text-right text-green-400 font-bold text-sm">Rp{{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Khusus Rentang: Tabel Harian di Bawah --}}
            @if($activeTab === 'rentang')
            <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                <div class="bg-gray-900/60 px-5 py-3 border-b border-[#3E4C59]">
                    <h3 class="text-white text-xs font-black uppercase tracking-widest">Detail Pendapatan Harian</h3>
                </div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-900/40 text-[10px] text-slate-500 uppercase font-bold">
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3 text-center">Volume</th>
                            <th class="px-6 py-3 text-right">Total (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#3E4C59]">
                        @foreach($this->laporanRentang['per_hari'] as $item)
                        <tr class="hover:bg-surface-hover transition-colors">
                            <td class="px-6 py-3 text-white font-mono">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-3 text-center text-slate-400">{{ $item->total }}</td>
                            <td class="px-6 py-3 text-right text-green-400 font-bold italic">Rp{{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Occupancy (Lebih Compact) --}}
            @if($activeTab === 'occupancy')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->occupancy as $data)
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-white font-black text-lg">{{ $data['area']->nama_area }}</h3>
                            <span class="text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded border border-blue-500/20 uppercase font-bold tracking-tighter">
                                {{ $data['terisi'] }} / {{ $data['total_slots'] }} Slot Terisi
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black {{ $data['persentase'] >= 90 ? 'text-red-500' : ($data['persentase'] >= 70 ? 'text-yellow-500' : 'text-green-500') }}">
                                {{ $data['persentase'] }}%
                            </span>
                        </div>
                    </div>
                    
                    <div class="w-full bg-gray-800 rounded-full h-1.5 mb-4">
                        <div class="h-1.5 rounded-full {{ $data['persentase'] >= 90 ? 'bg-red-500' : ($data['persentase'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                             style="width: {{ $data['persentase'] }}%"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        @foreach($data['per_tipe'] as $tipe)
                        <div class="bg-gray-900/50 p-2 rounded-lg text-center border border-gray-800">
                            <p class="text-[9px] text-slate-500 font-bold uppercase truncate">{{ $tipe['nama_tipe'] }}</p>
                            <p class="text-xs text-white font-black">{{ $tipe['terisi'] }} <span class="text-[9px] text-slate-600">/ {{ $tipe['total'] }}</span></p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- ==================== ANALYTICS TAB ==================== --}}
            @if($activeTab === 'analytics')
            <div class="space-y-6" 
                 wire:key="analytics-tab"
                 wire:ignore.self
                 x-data="analyticsCharts(@js($this->revenueChartData), @js($this->vehicleDistribution), @js($this->occupancyTrend), @js($this->paymentMethodDistribution))"
                 x-init="$nextTick(() => initCharts())">

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Member Transactions --}}
                    <div class="bg-surface-dark p-4 rounded-xl border border-[#3E4C59]">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Transaksi Member</p>
                        <p class="text-blue-400 text-xl font-black mt-1">{{ number_format($this->memberPerformance['member_transactions']) }}</p>
                    </div>
                    {{-- Non-Member Transactions --}}
                    <div class="bg-surface-dark p-4 rounded-xl border border-[#3E4C59]">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Transaksi Non-Member</p>
                        <p class="text-orange-400 text-xl font-black mt-1">{{ number_format($this->memberPerformance['non_member_transactions']) }}</p>
                    </div>
                    {{-- Member Revenue --}}
                    <div class="bg-surface-dark p-4 rounded-xl border border-[#3E4C59]">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Revenue Member</p>
                        <p class="text-green-400 text-xl font-black mt-1">Rp {{ number_format($this->memberPerformance['member_revenue'], 0, ',', '.') }}</p>
                    </div>
                    {{-- Avg Duration --}}
                    <div class="bg-surface-dark p-4 rounded-xl border border-[#3E4C59]">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Rata-rata Durasi</p>
                        <p class="text-purple-400 text-xl font-black mt-1">{{ $this->peakHourAnalysis['avg_duration'] }} menit</p>
                    </div>
                </div>

                {{-- Charts Row 1 --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Revenue Bar Chart --}}
                    <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest mb-4">Pendapatan 7 Hari Terakhir</h3>
                        <div class="h-64">
                            <canvas id="revenueBarChart"></canvas>
                        </div>
                    </div>

                    {{-- Vehicle Distribution Pie Chart --}}
                    <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest mb-4">Distribusi Tipe Kendaraan</h3>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="vehiclePieChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Charts Row 2 --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Occupancy Line Chart --}}
                    <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest mb-4">Trend Occupancy (7 Hari)</h3>
                        <div class="h-64">
                            <canvas id="occupancyLineChart"></canvas>
                        </div>
                    </div>

                    {{-- Payment Method Pie Chart --}}
                    <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest mb-4">Metode Pembayaran</h3>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="paymentPieChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Peak Hours Table --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                    <div class="bg-gray-900/60 px-5 py-3 border-b border-[#3E4C59]">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest">Top 5 Jam Tersibuk</h3>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-900/40 text-[10px] text-slate-500 uppercase font-bold">
                                <th class="px-6 py-3">Jam</th>
                                <th class="px-6 py-3 text-right">Total Kendaraan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#3E4C59]">
                            @foreach($this->peakHourAnalysis['peak_hours'] as $item)
                            <tr class="hover:bg-surface-hover transition-colors">
                                <td class="px-6 py-3 text-white font-mono">{{ str_pad($item->hour, 2, '0', STR_PAD_LEFT) }}:00 - {{ str_pad($item->hour + 1, 2, '0', STR_PAD_LEFT) }}:00</td>
                                <td class="px-6 py-3 text-right text-primary font-bold">{{ number_format($item->total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
            @endif
        </div>

    </div>
</div>

@push('styles')
    
{{-- Print Styles --}}
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #print-area, #print-area * {
            visibility: visible;
        }
        .print\:hidden {
            display: none !important;
        }
        .print\:block {
            display: block !important;
        }
        .bg-surface-dark {
            background: white !important;
            border: 1px solid #ddd !important;
        }
        .text-white, .text-green-400, .text-slate-400 {
            color: black !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>


// Alpine.js component for analytics charts
document.addEventListener('alpine:init', () => {
    Alpine.data('analyticsCharts', (revenueData, vehicleData, occupancyData, paymentData) => ({
        chartInstances: {},
        revenueData: revenueData,
        vehicleData: vehicleData,
        occupancyData: occupancyData,
        paymentData: paymentData,

        initCharts() {
            // Use setTimeout to ensure canvas elements are fully rendered in the DOM
            setTimeout(() => {
                this.renderCharts();
            }, 150);
        },

        renderCharts() {
            // Destroy existing charts
            Object.values(this.chartInstances).forEach(chart => {
                if (chart) chart.destroy();
            });
            this.chartInstances = {};

            // Chart.js default styling for dark mode
            Chart.defaults.color = '#94a3b8';
            Chart.defaults.borderColor = '#3E4C59';

            // Revenue Bar Chart
            const revenueCtx = document.getElementById('revenueBarChart');
            if (revenueCtx && this.revenueData) {
                this.chartInstances.revenue = new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: this.revenueData.labels || [],
                        datasets: [{
                            label: 'Pendapatan (Rp)',
                            data: this.revenueData.data || [],
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: value => 'Rp ' + value.toLocaleString('id-ID') }
                            }
                        }
                    }
                });
            }

            // Vehicle Pie Chart
            const vehicleCtx = document.getElementById('vehiclePieChart');
            if (vehicleCtx && this.vehicleData && this.vehicleData.length > 0) {
                this.chartInstances.vehicle = new Chart(vehicleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: this.vehicleData.map(v => v.label),
                        datasets: [{
                            data: this.vehicleData.map(v => v.value),
                            backgroundColor: ['#3b82f6', '#f97316', '#22c55e', '#a855f7', '#eab308', '#ec4899'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'right' } }
                    }
                });
            }

            // Occupancy Line Chart
            const occupancyCtx = document.getElementById('occupancyLineChart');
            if (occupancyCtx && this.occupancyData) {
                this.chartInstances.occupancy = new Chart(occupancyCtx, {
                    type: 'line',
                    data: {
                        labels: this.occupancyData.labels || [],
                        datasets: [{
                            label: 'Occupancy Rate (%)',
                            data: this.occupancyData.data || [],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#3b82f6',
                            pointRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: { callback: value => value + '%' }
                            }
                        }
                    }
                });
            }

            // Payment Pie Chart
            const paymentCtx = document.getElementById('paymentPieChart');
            if (paymentCtx && this.paymentData && this.paymentData.length > 0) {
                this.chartInstances.payment = new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: this.paymentData.map(p => p.label),
                        datasets: [{
                            data: this.paymentData.map(p => p.value),
                            backgroundColor: ['#22c55e', '#3b82f6', '#f97316', '#a855f7', '#eab308'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'right' } }
                    }
                });
            }
        }
    }));
});
</script>
@endpush
