<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\ParkirSessions;
use App\Models\TransaksiParkir;
use App\Models\AreaParkir;
use Carbon\Carbon;

new #[Layout('layouts.app')]
#[Title('Data Parkir')]
class extends Component {
    use WithPagination;

    public ?int $areaId = null;
    public ?string $hari = null; 
    public ?int $bulan = null;
    public ?int $tahun = null;
    public string $search = '';

    public string $activeTab = 'berjalan';

    public function mount()
    {
        if (request()->get('tab') === 'selesai') {
            $this->activeTab = 'selesai';
        }
    }

    public function updated($property)
    {
        if (in_array($property, ['areaId', 'hari', 'bulan', 'tahun', 'search'])) {
            $this->resetPage();
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    /* ======================
        DATA AREA (FIX ERROR)
    =======================*/
    public function getAreasProperty()
    {
        return AreaParkir::orderBy('nama_area')->get();
    }

    /* ======================
        PARKIR BERJALAN
    =======================*/
    public function getParkirBerjalanProperty()
    {
        return ParkirSessions::with(['tipeKendaraan', 'slot.area'])
            ->when($this->areaId, function ($q) {
                $q->whereHas('slotParkir', fn ($q2) =>
                    $q2->where('area_id', $this->areaId)
                );
            })
            ->when($this->search, fn ($q) => 
                $q->where('plat_nomor', 'like', '%' . $this->search . '%')
            )
            ->when($this->hari, fn ($q) =>
                $q->whereDate('generated_at', $this->hari)
            )
            ->when($this->bulan, fn ($q) =>
                $q->whereMonth('generated_at', $this->bulan)
            )
            ->when($this->tahun, fn ($q) =>
                $q->whereYear('generated_at', $this->tahun)
            )
            ->whereIn('status', ['WAITING_INPUT', 'SCANNED', 'IN_PROGRESS'])
            ->orderBy('generated_at', 'desc')
            ->paginate(10);
    }

    /* ======================
        PARKIR SELESAI
    =======================*/
    public function getParkirSelesaiProperty()
    {
        return TransaksiParkir::with([
                'kendaraan',
                'slotParkir.area',
                'tipeKendaraan',
                'member.tier',
                'pembayaran'
            ])
            ->when($this->areaId, function ($q) {
                $q->whereHas('slotParkir', fn ($q2) =>
                    $q2->where('area_id', $this->areaId)
                );
            })
            ->when($this->search, function ($q) {
                $q->whereHas('kendaraan', fn ($k) => 
                    $k->where('plat_nomor', 'like', '%' . $this->search . '%')
                );
            })
            ->when($this->hari, fn ($q) =>
                $q->whereDate('waktu_keluar', $this->hari)
            )
            ->when($this->bulan, fn ($q) =>
                $q->whereMonth('waktu_keluar', $this->bulan)
            )
            ->when($this->tahun, fn ($q) =>
                $q->whereYear('waktu_keluar', $this->tahun)
            )
            ->whereNotNull('waktu_keluar')
            ->orderBy('waktu_keluar', 'desc')
            ->paginate(15);
    }
};
?>

<div class="flex-1 flex flex-col h-full overflow-hidden">

    {{-- HEADER --}}
    <header class="px-4 md:px-8 py-4 md:py-6 border-b border-gray-800 flex-shrink-0">
        <h2 class="text-white text-2xl md:text-3xl font-black">Data Parkir</h2>
        <p class="text-slate-400 text-sm">Monitoring parkir berjalan & selesai</p>
    </header>

    {{-- ACTION BAR (TABS & FILTERS) --}}
    <div class="px-4 md:px-8 pt-4 md:pt-6 flex flex-col lg:flex-row lg:items-center justify-between gap-3 md:gap-4 flex-shrink-0">
        {{-- TABS --}}
        <div class="bg-surface-dark p-1.5 rounded-xl border border-[#3E4C59] inline-flex gap-1 h-fit">
            <button wire:click="setTab('berjalan')"
                class="px-6 py-2 rounded-lg font-bold text-sm transition
                {{ $activeTab === 'berjalan' ? 'bg-primary text-black shadow-lg shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                Berjalan
            </button>
            <button wire:click="setTab('selesai')"
                class="px-6 py-2 rounded-lg font-bold text-sm transition
                {{ $activeTab === 'selesai' ? 'bg-primary text-black shadow-lg shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                Selesai
            </button>
        </div>

        {{-- FILTERS --}}
        <div class="flex flex-wrap items-center gap-3 bg-surface-dark/50 p-2 rounded-xl border border-[#3E4C59]/50">
            {{-- SEARCH --}}
            <div class="flex items-center gap-2 px-2 border-r border-[#3E4C59]">
                 <span class="material-symbols-outlined text-gray-500 text-[18px]">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="PLAT NOMOR"
                    oninput="formatPlatDash(this)" maxlength="13"
                    class="bg-transparent border-none text-white text-xs font-semibold focus:ring-0 w-[140px] placeholder-gray-600 uppercase">
            </div>

            {{-- AREA --}}
            <div class="flex items-center gap-2 px-2 border-r border-[#3E4C59]">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Area</span>
                <select wire:model.live="areaId"
                    class="bg-transparent border-none text-white text-xs font-semibold focus:ring-0 cursor-pointer min-w-[120px]">
                    <option value="" class="bg-gray-900 text-white">Semua Lokasi</option>
                    @foreach($this->areas as $area)
                        <option value="{{ $area->id }}" class="bg-gray-900 text-white">{{ $area->nama_area }}</option>
                    @endforeach
                </select>
            </div>

            {{-- TANGGAL --}}
            <div class="flex items-center gap-2 px-2 border-r border-[#3E4C59]">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tgl</span>
                <input type="date" wire:model.live="hari"
                    class="bg-transparent border-none text-white text-xs font-semibold focus:ring-0 cursor-pointer">
            </div>

            {{-- BULAN --}}
            <div class="flex items-center gap-2 px-2 border-r border-[#3E4C59]">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Bulan</span>
                <select wire:model.live="bulan"
                    class="bg-transparent border-none text-white text-xs font-semibold focus:ring-0 cursor-pointer">
                    <option value="">Semua</option>
                    @for($i=1; $i<=12; $i++)
                        <option value="{{ $i }}" class="bg-gray-900 text-white">
                            {{ Carbon::create()->month($i)->translatedFormat('M') }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- TAHUN --}}
            <div class="flex items-center gap-2 px-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tahun</span>
                <select wire:model.live="tahun"
                    class="bg-transparent border-none text-white text-xs font-semibold focus:ring-0 cursor-pointer">
                    <option value="">Semua</option>
                    @for($y = now()->year; $y >= now()->year-5; $y--)
                        <option value="{{ $y }}" class="bg-gray-900 text-white">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="flex-1 flex flex-col px-4 md:px-8 py-4 md:py-6 overflow-hidden">

        {{-- PARKIR BERJALAN --}}
        @if($activeTab === 'berjalan')
        <div wire:poll.5s class="flex-1 flex flex-col bg-surface-dark border border-[#3E4C59] rounded-xl shadow-sm overflow-hidden">

            {{-- TABLE WRAPPER --}}
            <div class="flex-1 overflow-y-auto scrollbar-hide">
                <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[600px]">
                    <thead class="bg-gray-900/60 sticky top-0 z-10 border-b border-[#3E4C59]">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Info Kendaraan</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Slot</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Waktu Masuk</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#3E4C59]">
                        @forelse($this->parkirBerjalan as $item)
                        <tr class="hover:bg-surface-hover transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-white font-bold text-base uppercase">
                                    {{ preg_replace('/^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/', '$1 $2 $3', preg_replace('/[^A-Z0-9]/', '', $item->plat_nomor)) ?? 'No Plate' }}
                                </div>
                                <div class="text-slate-500 text-xs mt-0.5">{{ $item->tipeKendaraan->nama_tipe ?? '-' }} • {{ $item->token }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono font-bold">
                                    {{ $item->slot->kode_slot ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                <div class="text-sm font-medium">{{ Carbon::parse($item->generated_at)->format('H:i') }}</div>
                                <div class="text-[10px] text-slate-500 italic">{{ Carbon::parse($item->generated_at)->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-tighter uppercase
                                    {{ $item->status === 'IN_PROGRESS' ? 'bg-yellow-500 text-black' : 'bg-slate-700 text-white' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-500 italic">Tidak ada parkir berjalan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
        {{-- PAGINATION --}}
        <div class="px-6 py-4 border-t border-[#3E4C59] bg-gray-900/20 flex-shrink-0">
            {{ $this->parkirBerjalan->links() }}
        </div>
        @endif

        {{-- PARKIR SELESAI --}}
        @if($activeTab === 'selesai')
        <div class="flex-1 flex flex-col bg-surface-dark border border-[#3E4C59] rounded-xl shadow-sm overflow-hidden">

            <div class="flex-1 overflow-y-auto scrollbar-hide">
                <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[650px]">
                    <thead class="bg-gray-900/60 sticky top-0 z-10 border-b border-[#3E4C59]">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Kendaraan</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Waktu (Masuk/Keluar)</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Durasi & Member</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#3E4C59]">
                        @forelse($this->parkirSelesai as $item)
                        <tr class="hover:bg-surface-hover transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-white font-bold text-base uppercase leading-tight">
                                    {{ preg_replace('/^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/', '$1 $2 $3', preg_replace('/[^A-Z0-9]/', '', $item->kendaraan->plat_nomor ?? '')) ?: '-' }}
                                </div>
                                <div class="text-slate-500 text-[11px] mt-1">{{ $item->kode_karcis }} • {{ $item->tipeKendaraan->nama_tipe ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-green-400 text-xs font-bold tracking-tighter">{{ Carbon::parse($item->waktu_masuk)->format('H:i') }}</span>
                                    <span class="text-slate-600 text-[10px]">→</span>
                                    <span class="text-red-400 text-xs font-bold tracking-tighter">{{ Carbon::parse($item->waktu_keluar)->format('H:i') }}</span>
                                </div>
                                <div class="text-slate-500 text-[10px] mt-1 italic">{{ Carbon::parse($item->waktu_keluar)->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-200 text-sm font-medium">{{ $item->durasi_menit }} Menit</div>
                                <div class="text-primary text-[10px] font-bold uppercase tracking-widest">{{ $item->member->tier->nama ?? 'Reguler' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-green-400 font-black text-lg leading-tight">Rp{{ number_format($item->total_bayar, 0, ',', '.') }}</div>
                                <div class="text-slate-500 text-[10px] font-medium tracking-tighter">{{ $item->pembayaran->metode_pembayaran ?? 'CASH' }}</div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-500 italic">Tidak ada histori parkir ditemukan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4 border-t border-[#3E4C59] bg-gray-900/20 flex-shrink-0">
                {{ $this->parkirSelesai->links() }}
            </div>
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
    function formatPlatDash(el) {
        let value = el.value.toUpperCase();

        // hapus semua karakter selain huruf dan angka
        value = value.replace(/[^A-Z0-9]/g, '');

        let depan = '';
        let nomor = '';
        let belakang = '';

        let i = 0;

        // Bagian depan: huruf 1-2
        while(i < value.length && depan.length < 2 && /[A-Z]/.test(value[i])) {
            depan += value[i];
            i++;
        }

        // Bagian tengah: angka 1-4 → hanya jika huruf depan ada
        if(depan.length > 0) {
            while(i < value.length && nomor.length < 4 && /[0-9]/.test(value[i])) {
                nomor += value[i];
                i++;
            }
        }

        // Bagian belakang: huruf 0-3 → hanya jika angka tengah ada
        if(nomor.length > 0) {
            while(i < value.length && belakang.length < 3 && /[A-Z]/.test(value[i])) {
                belakang += value[i];
                i++;
            }
        }

        // gabungkan dengan spasi
        let hasil = depan;
        if(nomor) hasil += ' ' + nomor;
        if(belakang) hasil += ' ' + belakang;

        if (el.value !== hasil) {
            el.value = hasil;
            el.dispatchEvent(new Event('input'));
        }
    }
</script>
@endpush
