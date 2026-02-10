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

    // Analytics Filter
    public $filterMonth;
    public $filterYear;

    public function mount()
    {
        $this->tanggalHarian = Carbon::today()->format('Y-m-d');
        $this->tanggalMulai = Carbon::today()->subDays(7)->format('Y-m-d');
        $this->tanggalAkhir = Carbon::today()->format('Y-m-d');

        $this->filterMonth = Carbon::now()->month;
        $this->filterYear = Carbon::now()->year;
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

    // Revenue per day for last 7 days + Forecast (Linear Regression)
    public function getRevenueChartDataProperty()
    {
        $labels = [];
        $actualData = [];
        $forecastData = [];
        
        // 1. Get Actual Data (Last 7 Days)
        $points = []; // [x, y] for regression
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->translatedFormat('d M');
            $revenue = Pembayaran::whereDate('tanggal_bayar', $date)->sum('total_bayar');
            $actualData[] = $revenue;
            $points[] = ['x' => 6 - $i, 'y' => $revenue]; // x: 0 to 6
        }

        // 2. Linear Regression (y = mx + b)
        $n = count($points);
        if ($n > 1) {
            $sumX = 0; $sumY = 0; $sumXY = 0; $sumXX = 0;
            foreach ($points as $p) {
                $sumX += $p['x'];
                $sumY += $p['y'];
                $sumXY += ($p['x'] * $p['y']);
                $sumXX += ($p['x'] * $p['x']);
            }
            
            $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
            $intercept = ($sumY - $slope * $sumX) / $n;

            // 3. Project Next 7 Days
            for ($i = 1; $i <= 7; $i++) {
                $futureDate = Carbon::today()->addDays($i);
                $labels[] = $futureDate->translatedFormat('d M') . ' (Prediksi)';
                
                // Keep actual data null for future points
                $actualData[] = null;

                // Calculate forecast
                $x = 6 + $i; // Continue x sequence
                $y = max(0, $slope * $x + $intercept); // No negative revenue
                $forecastData[] = round($y);
            }
            
            // Fill previous forecast data with nulls to align
            $forecastData = array_merge(array_fill(0, 7, null), $forecastData);
             // Add the last actual point to forecast to connect lines
             $forecastData[6] = $actualData[6];

        } else {
             // Fallback if not enough data
             $forecastData = [];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pendapatan Aktual',
                    'data' => $actualData,
                    'borderColor' => '#10B981', // Emerald 500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ],
                [
                    'label' => 'Forecast (Linear Trend)',
                    'data' => $forecastData,
                    'borderColor' => '#F59E0B', // Amber 500
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension' => 0.4
                ]
            ],
            'trend_desc' => $n > 1 ? ($slope > 0 ? "Tren Positif (+)" : "Tren Negatif (-)") . " Rata-rata pertumbuhan harian: Rp " . number_format($slope, 0, ',', '.') : "Data tidak cukup"
        ];
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

    // Peak Hour Analysis (Filtered by Month/Year)
    public function getPeakHourAnalysisProperty()
    {
        // 1. Filter by Selected Month & Year
        $query = ParkirSessions::whereYear('confirmed_at', $this->filterYear)
            ->whereMonth('confirmed_at', $this->filterMonth)
            ->where('status', 'finished');

        // 2. Group by Hour
        $peakHours = (clone $query)
            ->select(DB::raw('HOUR(confirmed_at) as hour'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('HOUR(confirmed_at)'))
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        // 3. Stacked Bar Data (Hour x Vehicle Type) for Chart
        $hourlyDistribution = (clone $query)
             ->select(
                DB::raw('HOUR(confirmed_at) as hour'),
                'tipe_kendaraan_id',
                DB::raw('count(*) as total')
             )
             ->groupBy(DB::raw('HOUR(confirmed_at)'), 'tipe_kendaraan_id')
             ->get();
        
        // Prepare Chart Data
        $chartLabels = range(0, 23); // 00:00 - 23:00
        $chartDatasets = [];
        $types = TipeKendaraan::all();
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']; // Blue, Green, Amber, Red, Violet

        foreach ($types as $index => $type) {
            $data = array_fill(0, 24, 0);
            foreach ($hourlyDistribution as $item) {
                if ($item->tipe_kendaraan_id == $type->id) {
                    $data[$item->hour] = $item->total;
                }
            }
            
            $chartDatasets[] = [
                'label' => $type->nama_tipe,
                'data' => $data,
                'backgroundColor' => $colors[$index % count($colors)],
            ];
        }

        $avgDuration = TransaksiParkir::whereYear('waktu_masuk', $this->filterYear)
            ->whereMonth('waktu_masuk', $this->filterMonth)
            ->whereNotNull('durasi_menit')
            ->avg('durasi_menit');

        return [
            'peak_hours' => $peakHours,
            'avg_duration' => round($avgDuration ?? 0, 1),
            'chart_data' => [
                'labels' => array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT).":00", $chartLabels),
                'datasets' => $chartDatasets
            ]
        ];
    }

    // Member Performance
    public function getMemberPerformanceProperty()
    {
        if ($this->activeTab === 'analytics') {
            $mulai = Carbon::createFromDate($this->filterYear, $this->filterMonth, 1)->startOfMonth();
            $akhir = $mulai->copy()->endOfMonth();
        } else {
            $mulai = Carbon::parse($this->tanggalMulai)->startOfDay();
            $akhir = Carbon::parse($this->tanggalAkhir)->endOfDay();
        }

        $baseQuery = TransaksiParkir::whereBetween('waktu_keluar', [$mulai, $akhir]);

        $memberStats = (clone $baseQuery)
            ->selectRaw('
                COUNT(CASE WHEN member_id IS NOT NULL THEN 1 END) as member_trx,
                COUNT(CASE WHEN member_id IS NULL THEN 1 END) as non_member_trx,
                SUM(CASE WHEN member_id IS NOT NULL THEN total_bayar ELSE 0 END) as member_rev,
                SUM(CASE WHEN member_id IS NULL THEN total_bayar ELSE 0 END) as non_member_rev
            ')
            ->first();

        // Join with pembayaran is tricky for aggregates in one go if multiple payments?
        // Assuming 1-to-1 or total_bayar is in TransaksiParkir correctly (it is).
        // My schema has total_bayar in TransaksiParkir.

        return [
            'member_transactions' => $memberStats->member_trx ?? 0,
            'non_member_transactions' => $memberStats->non_member_trx ?? 0,
            'member_revenue' => $memberStats->member_rev ?? 0,
            'non_member_revenue' => $memberStats->non_member_rev ?? 0,
        ];
    }

    public function getMemberRevenueDistributionProperty()
    {
        $perf = $this->memberPerformance;
        $total = $perf['member_revenue'] + $perf['non_member_revenue'];
        
        // Avoid division by zero
        $pctMember = $total > 0 ? round(($perf['member_revenue'] / $total) * 100, 1) : 0;
        $pctNonMember = $total > 0 ? round(($perf['non_member_revenue'] / $total) * 100, 1) : 0;

        return [
            'total_revenue' => $total,
            'pct_member' => $pctMember,
            'chart_data' => [
                'labels' => ['Member (' . $pctMember . '%)', 'Non-Member (' . $pctNonMember . '%)'],
                'datasets' => [[
                    'data' => [$perf['member_revenue'], $perf['non_member_revenue']],
                    'backgroundColor' => ['#10B981', '#64748b'], // Green-500, Slate-500
                    'borderWidth' => 0
                ]]
            ]
        ];
    }

    // Payment Method Distribution (for Pie Chart)
    public function getPaymentMethodDistributionProperty()
    {
        if ($this->activeTab === 'analytics') {
            $mulai = Carbon::createFromDate($this->filterYear, $this->filterMonth, 1)->startOfMonth();
            $akhir = $mulai->copy()->endOfMonth();
        } else {
            $mulai = Carbon::parse($this->tanggalMulai)->startOfDay();
            $akhir = Carbon::parse($this->tanggalAkhir)->endOfDay();
        }

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
    <header class="px-4 md:px-8 py-4 md:py-6 border-b border-gray-800 flex flex-col sm:flex-row justify-between sm:items-center gap-3">
        <div>
            <h2 class="text-white text-2xl md:text-3xl font-black">Laporan & Analytics</h2>
            <p class="text-slate-400 text-sm">Statistik, analisis pendapatan & visualisasi data</p>
        </div>

        {{-- EXPORT DROPDOWN --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.away="open = false"
                class="{{ ($activeTab === 'occupancy') ? 'opacity-50 cursor-not-allowed' : '' }} flex items-center gap-2 bg-primary text-black px-4 py-2 rounded-lg font-bold text-sm shadow-lg shadow-primary/20 w-fit"
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
    <div class="px-4 md:px-8 pt-4 md:pt-6 flex flex-col lg:flex-row lg:items-center justify-between gap-3 md:gap-4 print:hidden">
        
        {{-- LEFT: TABS --}}
        <div class="bg-surface-dark p-1.5 rounded-xl border border-[#3E4C59] inline-flex gap-1 overflow-x-auto">
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
        <div class="flex flex-wrap items-center gap-2 md:gap-3 bg-surface-dark/50 p-1.5 rounded-xl border border-[#3E4C59]/50">
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
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Bulan</span>
                    <select wire:model.live="filterMonth" class="bg-transparent border-none text-white text-xs font-semibold focus:ring-1 focus:ring-primary cursor-pointer w-24">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="flex items-center gap-2 px-3">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tahun</span>
                    <select wire:model.live="filterYear" class="bg-transparent border-none text-white text-xs font-semibold focus:ring-1 focus:ring-primary cursor-pointer w-20">
                        @for($y=date('Y'); $y>=2020; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            @endif
        </div>
    </div>

    {{-- CONTENT AREA --}}
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-4 md:py-6 scrollbar-hide">
        
        <div class="space-y-6">
            {{-- SUMMARY CARDS (Lebih Kecil) --}}
            @if($activeTab !== 'occupancy' && $activeTab !== 'analytics')
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
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
                    <div class="bg-gray-900/60 px-4 md:px-5 py-3 border-b border-[#3E4C59]">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest">Per Tipe Kendaraan</h3>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[350px]">
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
                </div>

                {{-- Per Metode --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden shadow-sm h-fit">
                    <div class="bg-gray-900/60 px-4 md:px-5 py-3 border-b border-[#3E4C59]">
                        <h3 class="text-white text-xs font-black uppercase tracking-widest">Metode Pembayaran</h3>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[350px]">
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
            </div>
            @endif

            {{-- Khusus Rentang: Tabel Harian di Bawah --}}
            @if($activeTab === 'rentang')
            <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                <div class="bg-gray-900/60 px-4 md:px-5 py-3 border-b border-[#3E4C59]">
                    <h3 class="text-white text-xs font-black uppercase tracking-widest">Detail Pendapatan Harian</h3>
                </div>
                <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[450px]">
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
                  wire:key="analytics-tab-{{ $filterMonth }}-{{ $filterYear }}"
                  x-data="analyticsCharts(
                      @js($this->revenueChartData), 
                      @js($this->vehicleDistribution), 
                      @js($this->memberRevenueDistribution), 
                      @js($this->paymentMethodDistribution), 
                      @js($this->peakHourAnalysis)
                  )">

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
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
                        <div class="mb-4">
                            <h3 class="text-white text-xs font-black uppercase tracking-widest">Forecast Pendapatan</h3>
                            <p class="text-[10px] text-slate-400 mt-1">
                                {{ $this->revenueChartData['trend_desc'] }}
                            </p>
                        </div>
                        <div class="h-64">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    {{-- Vehicle Distribution Pie Chart --}}
                    <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                        <div class="mb-4">
                             <h3 class="text-white text-xs font-black uppercase tracking-widest">Distribusi Kendaraan</h3>
                             <p class="text-[10px] text-slate-400 mt-1">Proporsi tipe kendaraan berdasarkan transaksi.</p>
                        </div>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="vehiclePieChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Charts Row 2 --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Peak Hour Prediction --}}
                    <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                         <div class="mb-4">
                            <h3 class="text-white text-xs font-black uppercase tracking-widest">Prediksi Jam Sibuk ({{ \Carbon\Carbon::create()->month($filterMonth)->translatedFormat('F') }} {{ $filterYear }})</h3>
                            <p class="text-[10px] text-slate-400 mt-1">Analisis kepadatan per jam berdasarkan historis data.</p>
                        </div>
                        <div class="h-64">
                            <canvas id="peakHourChart"></canvas>
                        </div>
                    </div>

                    {{-- Member Revenue Distribution --}}
                    <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                        <div class="mb-4">
                            <h3 class="text-white text-xs font-black uppercase tracking-widest">Distribusi Revenue Member</h3>
                            <p class="text-[10px] text-slate-400 mt-1">Proporsi pendapatan dari member berdasarkan kategori.</p>
                        </div>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="memberRevenueChart"></canvas>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- Charts Row 3: Payment Method --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Payment Method Distribution --}}
                     <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-5">
                        <div class="mb-4">
                            <h3 class="text-white text-xs font-black uppercase tracking-widest">Metode Pembayaran (Revenue)</h3>
                            <p class="text-[10px] text-slate-400 mt-1">Distribusi pendapatan berdasarkan metode pembayaran.</p>
                        </div>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="paymentMethodChart"></canvas>
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

@script
<script>
    Livewire.hook('morph.updated', ({ component, el }) => {
        // Re-init charts if we are on analytics tab
        if(document.getElementById('revenueChart')) {
            window.dispatchEvent(new Event('init-charts'));
        }
    });
</script>
@endscript

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function() {
        const registerAnalyticsCharts = () => {
            Alpine.data('analyticsCharts', (revenueData, vehicleDist, memberRevenueData, paymentDist, peakHourData) => ({
                charts: {},
                memberRevenueData: memberRevenueData, // Make data accessible to x-text
                
                init() {
                    // Check if Chart is loaded, if not wait a bit
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => this.init(), 100);
                        return;
                    }
                    this.initCharts();
                },

                initCharts() {
                    this.destroyCharts();

                    // 1. Revenue & Forecast Chart
                    const ctx1 = document.getElementById('revenueChart');
                    if (ctx1 && revenueData) {
                        this.charts.revenue = new Chart(ctx1, {
                            type: 'line',
                            data: {
                                labels: revenueData.labels,
                                datasets: revenueData.datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { labels: { color: '#94a3b8' } },
                                    tooltip: { 
                                        mode: 'index',
                                        intersect: false 
                                    }
                                },
                                 scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: '#334155' },
                                        ticks: { color: '#94a3b8' }
                                    },
                                    x: {
                                        grid: { display: false },
                                        ticks: { color: '#94a3b8' }
                                    }
                                }
                            }
                        });
                    }

                    // 2. Vehicle Distribution (Pie)
                    const ctx2 = document.getElementById('vehiclePieChart');
                    if (ctx2 && vehicleDist) {
                         this.charts.vehicle = new Chart(ctx2, {
                            type: 'doughnut',
                            data: {
                                labels: vehicleDist.map(d => d.label),
                                datasets: [{
                                    data: vehicleDist.map(d => d.value),
                                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                                    borderWidth: 0
                                }]
                            },
                             options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'right', labels: { color: '#94a3b8', font: { size: 10 } } }
                                }
                            }
                        });
                    }

                    // 3. Peak Hour Prediction (Stacked Bar)
                    const ctx3 = document.getElementById('peakHourChart');
                    if(ctx3 && peakHourData) {
                         this.charts.peakHour = new Chart(ctx3, {
                            type: 'bar',
                            data: peakHourData.chart_data,
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: { stacked: true, grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 } } },
                                    y: { stacked: true, grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }
                                },
                                plugins: {
                                    legend: { labels: { color: '#94a3b8', boxWidth: 10 } },
                                }
                            }
                        });
                    }

                    // 4. Member Revenue Distribution (Doughnut)
                    const ctx4 = document.getElementById('memberRevenueChart');
                    if (ctx4 && memberRevenueData) {
                        this.charts.memberRevenue = new Chart(ctx4, {
                            type: 'doughnut',
                            data: memberRevenueData.chart_data,
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%', // Donut style
                                plugins: {
                                    legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 10 } } }
                                }
                            }
                        });

                    }

                    // 5. Payment Method Distribution (Pie)
                    const ctx5 = document.getElementById('paymentMethodChart');
                    if (ctx5 && paymentDist) {
                        this.charts.payment = new Chart(ctx5, {
                            type: 'pie', // Pie chart for contrast
                            data: {
                                labels: paymentDist.map(d => d.label),
                                datasets: [{
                                    data: paymentDist.map(d => d.value),
                                    backgroundColor: ['#6366f1', '#14b8a6', '#f43f5e', '#eab308'], // Indigo, Teal, Rose, Yellow
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'right', labels: { color: '#94a3b8', font: { size: 10 } } }
                                }
                            }
                        });
                    }
                },

                destroyCharts() {
                    Object.values(this.charts).forEach(chart => {
                        if(chart) chart.destroy();
                    });
                    this.charts = {};
                }
            }));
        };

        if (typeof Alpine !== 'undefined') {
            registerAnalyticsCharts();
        } else {
            document.addEventListener('alpine:init', registerAnalyticsCharts);
        }
    })();
</script>

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

