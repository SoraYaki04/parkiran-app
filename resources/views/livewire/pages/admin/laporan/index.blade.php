<?php

use App\Models\ParkirSessions;
use App\Models\Pembayaran;
use App\Models\TipeKendaraan;
use App\Models\AreaParkir;
use App\Models\SlotParkir;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('layouts.app')]
#[Title('Laporan')]
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
        DATA LAPORAN HARIAN
    =======================*/
    public function getLaporanHarianProperty()
    {
        $tanggal = Carbon::parse($this->tanggalHarian);

        // Get finished sessions for the day
        $sessions = ParkirSessions::whereDate('confirmed_at', $tanggal)
            ->where('status', 'finished')
            ->get();

        // Get pembayaran for the day
        $pembayaran = Pembayaran::whereDate('tanggal_bayar', $tanggal)->get();

        // Breakdown per tipe kendaraan
        $perTipe = ParkirSessions::whereDate('confirmed_at', $tanggal)
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get()
            ->map(function ($item) use ($tanggal) {
                $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
                // Get pendapatan from pembayaran for this tipe
                $pendapatan = Pembayaran::whereDate('tanggal_bayar', $tanggal)
                    ->whereHas('transaksiParkir', function ($q) use ($item) {
                        $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id);
                    })
                    ->sum('total_bayar');
                $item->tipeKendaraan = $tipe;
                $item->pendapatan = $pendapatan;
                return $item;
            });

        // Breakdown per metode pembayaran
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

        // Get finished sessions for the range
        $sessions = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->get();

        // Get pembayaran for the range
        $pembayaran = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])->get();

        // Breakdown per tipe kendaraan
        $perTipe = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get()
            ->map(function ($item) use ($mulai, $akhir) {
                $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
                // Get pendapatan from pembayaran for this tipe
                $pendapatan = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
                    ->whereHas('transaksiParkir', function ($q) use ($item) {
                        $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id);
                    })
                    ->sum('total_bayar');
                $item->tipeKendaraan = $tipe;
                $item->pendapatan = $pendapatan;
                return $item;
            });

        // Breakdown per metode pembayaran
        $perMetode = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
            ->select('metode_pembayaran', DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy('metode_pembayaran')
            ->get();

        // Daily breakdown from pembayaran
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

            // Per tipe kendaraan
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

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
};
?>

<div class="flex-1 flex flex-col h-full overflow-hidden">

    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-end">
        <div>
            <h2 class="text-white text-3xl font-black">Laporan</h2>
            <p class="text-slate-400">Lihat statistik dan laporan parkir</p>
        </div>

        {{-- Export Buttons --}}
        <div class="flex gap-2" x-data="{ open: false }">
            <div class="relative">
                <button @click="open = !open" @click.away="open = false"
                        class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
                    <span class="material-symbols-outlined">download</span>
                    Export
                    <span class="material-symbols-outlined text-sm">expand_more</span>
                </button>

                {{-- Dropdown Menu --}}
                <div x-show="open" x-transition
                     class="absolute right-0 mt-2 w-48 bg-card-dark border border-[#3E4C59] rounded-lg shadow-xl z-50">
                    
                    @if($activeTab === 'harian')
                        <a href="{{ route('admin.export.harian.pdf', ['tanggal' => $tanggalHarian]) }}"
                           class="flex items-center gap-2 px-4 py-3 text-white hover:bg-surface-hover transition">
                            <span class="material-symbols-outlined text-red-400">picture_as_pdf</span>
                            Export PDF
                        </a>
                        <a href="{{ route('admin.export.harian.excel', ['tanggal' => $tanggalHarian]) }}"
                           class="flex items-center gap-2 px-4 py-3 text-white hover:bg-surface-hover transition">
                            <span class="material-symbols-outlined text-green-400">table_chart</span>
                            Export Excel
                        </a>
                    @elseif($activeTab === 'rentang')
                        <a href="{{ route('admin.export.rentang.pdf', ['mulai' => $tanggalMulai, 'akhir' => $tanggalAkhir]) }}"
                           class="flex items-center gap-2 px-4 py-3 text-white hover:bg-surface-hover transition">
                            <span class="material-symbols-outlined text-red-400">picture_as_pdf</span>
                            Export PDF
                        </a>
                        <a href="{{ route('admin.export.rentang.excel', ['mulai' => $tanggalMulai, 'akhir' => $tanggalAkhir]) }}"
                           class="flex items-center gap-2 px-4 py-3 text-white hover:bg-surface-hover transition">
                            <span class="material-symbols-outlined text-green-400">table_chart</span>
                            Export Excel
                        </a>
                    @elseif($activeTab === 'occupancy')
                        <a href="{{ route('admin.export.occupancy.pdf') }}"
                           class="flex items-center gap-2 px-4 py-3 text-white hover:bg-surface-hover transition">
                            <span class="material-symbols-outlined text-red-400">picture_as_pdf</span>
                            Export PDF
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    {{-- TABS --}}
    <div class="px-8 pt-6 print:hidden">
        <div class="bg-surface-dark p-2 rounded-xl border border-[#3E4C59] inline-flex gap-2">
            <button wire:click="setTab('harian')"
                    class="px-4 py-2 rounded-lg font-medium transition {{ $activeTab === 'harian' ? 'bg-primary text-black' : 'text-slate-400 hover:text-white' }}">
                Laporan Harian
            </button>
            <button wire:click="setTab('rentang')"
                    class="px-4 py-2 rounded-lg font-medium transition {{ $activeTab === 'rentang' ? 'bg-primary text-black' : 'text-slate-400 hover:text-white' }}">
                Rentang Tanggal
            </button>
            <button wire:click="setTab('occupancy')"
                    class="px-4 py-2 rounded-lg font-medium transition {{ $activeTab === 'occupancy' ? 'bg-primary text-black' : 'text-slate-400 hover:text-white' }}">
                Occupancy
            </button>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">

        {{-- ==================== LAPORAN HARIAN ==================== --}}
        @if($activeTab === 'harian')
        <div class="space-y-6" id="print-area">
            {{-- Print Header --}}
            <div class="hidden print:block mb-6">
                <h1 class="text-2xl font-bold text-center">Laporan Transaksi Harian</h1>
                <p class="text-center text-gray-600">Tanggal: {{ \Carbon\Carbon::parse($tanggalHarian)->format('d F Y') }}</p>
            </div>

            {{-- Filter --}}
            <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59] print:hidden">
                <div class="flex items-center gap-4">
                    <label class="text-slate-400">Pilih Tanggal:</label>
                    <input type="date" wire:model.live="tanggalHarian"
                           class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-surface-dark p-6 rounded-xl border border-[#3E4C59]">
                    <p class="text-slate-400 text-sm">Total Transaksi</p>
                    <p class="text-white text-3xl font-bold mt-1">{{ number_format($this->laporanHarian['total_transaksi']) }}</p>
                </div>
                <div class="bg-surface-dark p-6 rounded-xl border border-[#3E4C59]">
                    <p class="text-slate-400 text-sm">Total Pendapatan</p>
                    <p class="text-green-400 text-3xl font-bold mt-1">Rp {{ number_format($this->laporanHarian['total_pendapatan'], 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Breakdown Tables --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Per Tipe Kendaraan --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                    <div class="bg-gray-900 px-6 py-4">
                        <h3 class="text-white font-bold">Per Tipe Kendaraan</h3>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-slate-400 text-xs">Tipe</th>
                                <th class="px-6 py-3 text-center text-slate-400 text-xs">Jumlah</th>
                                <th class="px-6 py-3 text-right text-slate-400 text-xs">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#3E4C59]">
                            @forelse($this->laporanHarian['per_tipe'] as $item)
                                <tr class="hover:bg-surface-hover">
                                    <td class="px-6 py-4 text-white">{{ $item->tipeKendaraan->nama_tipe ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-white">{{ $item->total }}</td>
                                    <td class="px-6 py-4 text-right text-green-400">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Per Metode Pembayaran --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                    <div class="bg-gray-900 px-6 py-4">
                        <h3 class="text-white font-bold">Per Metode Pembayaran</h3>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-slate-400 text-xs">Metode</th>
                                <th class="px-6 py-3 text-center text-slate-400 text-xs">Jumlah</th>
                                <th class="px-6 py-3 text-right text-slate-400 text-xs">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#3E4C59]">
                            @forelse($this->laporanHarian['per_metode'] as $item)
                                <tr class="hover:bg-surface-hover">
                                    <td class="px-6 py-4 text-white capitalize">{{ $item->metode_pembayaran ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-white">{{ $item->total }}</td>
                                    <td class="px-6 py-4 text-right text-green-400">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif


        {{-- ==================== LAPORAN RENTANG TANGGAL ==================== --}}
        @if($activeTab === 'rentang')
        <div class="space-y-6">
            {{-- Print Header --}}
            <div class="hidden print:block mb-6">
                <h1 class="text-2xl font-bold text-center">Laporan Transaksi</h1>
                <p class="text-center text-gray-600">Periode: {{ \Carbon\Carbon::parse($tanggalMulai)->format('d F Y') }} - {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d F Y') }}</p>
            </div>

            {{-- Filter --}}
            <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59] print:hidden">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-slate-400">Dari:</label>
                        <input type="date" wire:model.live="tanggalMulai"
                               class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-slate-400">Sampai:</label>
                        <input type="date" wire:model.live="tanggalAkhir"
                               class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    </div>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-surface-dark p-6 rounded-xl border border-[#3E4C59]">
                    <p class="text-slate-400 text-sm">Total Transaksi</p>
                    <p class="text-white text-3xl font-bold mt-1">{{ number_format($this->laporanRentang['total_transaksi']) }}</p>
                </div>
                <div class="bg-surface-dark p-6 rounded-xl border border-[#3E4C59]">
                    <p class="text-slate-400 text-sm">Total Pendapatan</p>
                    <p class="text-green-400 text-3xl font-bold mt-1">Rp {{ number_format($this->laporanRentang['total_pendapatan'], 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Daily Breakdown --}}
            <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                <div class="bg-gray-900 px-6 py-4">
                    <h3 class="text-white font-bold">Breakdown Per Hari</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-slate-400 text-xs">Tanggal</th>
                                <th class="px-6 py-3 text-center text-slate-400 text-xs">Jumlah Transaksi</th>
                                <th class="px-6 py-3 text-right text-slate-400 text-xs">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#3E4C59]">
                            @forelse($this->laporanRentang['per_hari'] as $item)
                                <tr class="hover:bg-surface-hover">
                                    <td class="px-6 py-4 text-white">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-center text-white">{{ $item->total }}</td>
                                    <td class="px-6 py-4 text-right text-green-400">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Per Tipe & Metode --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Per Tipe Kendaraan --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                    <div class="bg-gray-900 px-6 py-4">
                        <h3 class="text-white font-bold">Per Tipe Kendaraan</h3>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-slate-400 text-xs">Tipe</th>
                                <th class="px-6 py-3 text-center text-slate-400 text-xs">Jumlah</th>
                                <th class="px-6 py-3 text-right text-slate-400 text-xs">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#3E4C59]">
                            @forelse($this->laporanRentang['per_tipe'] as $item)
                                <tr class="hover:bg-surface-hover">
                                    <td class="px-6 py-4 text-white">{{ $item->tipeKendaraan->nama_tipe ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-white">{{ $item->total }}</td>
                                    <td class="px-6 py-4 text-right text-green-400">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Per Metode Pembayaran --}}
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                    <div class="bg-gray-900 px-6 py-4">
                        <h3 class="text-white font-bold">Per Metode Pembayaran</h3>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-slate-400 text-xs">Metode</th>
                                <th class="px-6 py-3 text-center text-slate-400 text-xs">Jumlah</th>
                                <th class="px-6 py-3 text-right text-slate-400 text-xs">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#3E4C59]">
                            @forelse($this->laporanRentang['per_metode'] as $item)
                                <tr class="hover:bg-surface-hover">
                                    <td class="px-6 py-4 text-white capitalize">{{ $item->metode_pembayaran ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-white">{{ $item->total }}</td>
                                    <td class="px-6 py-4 text-right text-green-400">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif


        {{-- ==================== LAPORAN OCCUPANCY ==================== --}}
        @if($activeTab === 'occupancy')
        <div class="space-y-6">
            {{-- Print Header --}}
            <div class="hidden print:block mb-6">
                <h1 class="text-2xl font-bold text-center">Laporan Occupancy</h1>
                <p class="text-center text-gray-600">Per Tanggal: {{ now()->format('d F Y H:i') }}</p>
            </div>

            @forelse($this->occupancy as $data)
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
                    {{-- Area Header --}}
                    <div class="bg-gray-900 px-6 py-4 flex justify-between items-center">
                        <div>
                            <h3 class="text-white font-bold text-lg">{{ $data['area']->nama_area }}</h3>
                            <p class="text-slate-400 text-sm">{{ $data['area']->lokasi_fisik }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold {{ $data['persentase'] >= 90 ? 'text-red-400' : ($data['persentase'] >= 70 ? 'text-yellow-400' : 'text-green-400') }}">
                                {{ $data['persentase'] }}%
                            </p>
                            <p class="text-slate-400 text-sm">{{ $data['terisi'] }} / {{ $data['total_slots'] }} slot</p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="px-6 py-4">
                        <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="h-3 rounded-full transition-all {{ $data['persentase'] >= 90 ? 'bg-red-500' : ($data['persentase'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ $data['persentase'] }}%"></div>
                        </div>
                    </div>

                    {{-- Per Tipe --}}
                    <div class="px-6 pb-4">
                        <table class="w-full">
                            <thead class="bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-slate-400 text-xs">Tipe Kendaraan</th>
                                    <th class="px-4 py-2 text-center text-slate-400 text-xs">Total Slot</th>
                                    <th class="px-4 py-2 text-center text-slate-400 text-xs">Terisi</th>
                                    <th class="px-4 py-2 text-center text-slate-400 text-xs">Kosong</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#3E4C59]">
                                @foreach($data['per_tipe'] as $tipe)
                                    <tr>
                                        <td class="px-4 py-3 text-white">{{ $tipe['nama_tipe'] }}</td>
                                        <td class="px-4 py-3 text-center text-white">{{ $tipe['total'] }}</td>
                                        <td class="px-4 py-3 text-center text-red-400">{{ $tipe['terisi'] }}</td>
                                        <td class="px-4 py-3 text-center text-green-400">{{ $tipe['kosong'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="bg-surface-dark border border-[#3E4C59] rounded-xl p-10 text-center">
                    <p class="text-slate-500">Tidak ada data area parkir</p>
                </div>
            @endforelse
        </div>
        @endif

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
