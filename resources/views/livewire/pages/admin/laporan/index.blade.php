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

<div class="flex-1 flex-col h-full overflow-hidden flex">

    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-center">
        <div>
            <h2 class="text-white text-3xl font-black">Laporan</h2>
            <p class="text-slate-400">Statistik & analisis pendapatan</p>
        </div>

        {{-- EXPORT DROPDOWN --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.away="open = false"
                class="flex items-center gap-2 bg-primary text-black px-4 py-2 rounded-lg font-bold text-sm shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined text-lg">download</span>
                Export
                <span class="material-symbols-outlined text-sm">expand_more</span>
            </button>

            <div x-show="open" x-transition 
                class="absolute right-0 mt-2 w-48 bg-[#1A202C] border border-[#3E4C59] rounded-xl shadow-2xl z-50 overflow-hidden">
                @if($activeTab === 'harian')
                    <a href="{{ route('admin.export.harian.pdf', ['tanggal' => $tanggalHarian]) }}" class="flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm">
                        <span class="material-symbols-outlined text-red-400">picture_as_pdf</span> PDF Harian
                    </a>
                    <a href="{{ route('admin.export.harian.excel', ['tanggal' => $tanggalHarian]) }}" class="flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm border-t border-gray-800">
                        <span class="material-symbols-outlined text-green-400">table_chart</span> Excel Harian
                    </a>
                @elseif($activeTab === 'rentang')
                    <a href="{{ route('admin.export.rentang.pdf', ['mulai' => $tanggalMulai, 'akhir' => $tanggalAkhir]) }}" class="flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm">
                        <span class="material-symbols-outlined text-red-400">picture_as_pdf</span> PDF Rentang
                    </a>
                    <a href="{{ route('admin.export.rentang.excel', ['mulai' => $tanggalMulai, 'akhir' => $tanggalAkhir]) }}" class="flex items-center gap-3 px-4 py-3 text-white hover:bg-white/5 transition text-sm border-t border-gray-800">
                        <span class="material-symbols-outlined text-green-400">table_chart</span> Excel Rentang
                    </a>
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
                <div class="px-4 py-1.5 text-[10px] font-bold text-primary uppercase tracking-tighter italic">
                    Real-time Data Terupdate
                </div>
            @endif
        </div>
    </div>

    {{-- CONTENT AREA --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        
        <div class="space-y-6">
            {{-- SUMMARY CARDS (Lebih Kecil) --}}
            @if($activeTab !== 'occupancy')
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
