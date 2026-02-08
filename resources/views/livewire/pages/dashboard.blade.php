<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ParkirSessions;
use App\Models\SlotParkir;
use App\Models\TipeKendaraan;
use App\Models\TransaksiParkir;
use App\Models\AreaParkir;
use Carbon\Carbon;

new #[Layout('layouts.app')]
#[Title('Dashboard Overview')]
class extends Component
{
    public $search = '';

    public function getOccupancyProperty()
    {
        $tipeKendaraan = TipeKendaraan::all();
        $slots = SlotParkir::all();
        $sessions = ParkirSessions::where('status', 'IN_PROGRESS')->get();

        return $tipeKendaraan->map(function($t) use ($slots, $sessions) {
            $total = $slots->where('tipe_kendaraan_id', $t->id)->count();
            $used  = $sessions->where('tipe_kendaraan_id', $t->id)->count();
            return [
                'nama_tipe'      => $t->nama_tipe,
                'total_slot'     => $total,
                'used_slot'      => $used,
                'available_slot' => $total - $used,
                'percentage'     => $total > 0 ? round($used / $total * 100) : 0,
            ];
        });
    }

    public function getTodayRevenueProperty()
    {
        return TransaksiParkir::whereDate('waktu_masuk', Carbon::today())
                ->sum('total_bayar');
    }

    public function getRecentMovementsProperty()
    {
        return ParkirSessions::with(['tipeKendaraan', 'slot.area'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();
    }

    public function getAreaOccupancyProperty()
    {
        $areas = AreaParkir::all();

        return $areas->map(function($area) {
            $slots = $area->slots;
            $used = $slots->where('status', 'terisi')->count();
            $total = $slots->count();
            $percentage = $total > 0 ? round($used / $total * 100) : 0;

            $color = 'primary'; 
            if ($percentage >= 90) $color = 'red-500';
            elseif ($percentage <= 30) $color = 'emerald-500';

            return [
                'nama'       => $area->nama_area,
                'used'       => $used,
                'total'      => $total,
                'available'  => $total - $used,
                'percentage' => $percentage,
                'color'      => $color,
            ];
        });
    }

    public function getIsAdminProperty()
    {
        return auth()->user()->role_id === 1;
    }
}
?>


 <div class="flex-1 flex flex-col h-full overflow-hidden bg-gray-900 text-gray-200" wire:poll.10s>
    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-end bg-gray-900 backdrop-blur-xl sticky top-0 z-30">
        <div>
            <h2 class="text-white text-3xl font-black">
               Dashboard
            </h2>
            <p class="text-slate-400">
                Ringkasan data dan kontrol sistem kendaraaan
            </p>
        </div>
        <div class="text-right">
            <div class="flex items-center justify-end gap-3">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-primary shadow-[0_0_12px_#facc14]"></span>
                </span>
                <h1 class="text-white text-4xl font-black tracking-tighter tabular-nums">
                    {{ now()->format('H:i') }}
                </h1>
            </div>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-[0.3em] mt-1">
                    Waktu Sistem (WIB)
            </p>
        </div>

    </header>

    <div class="flex-1 overflow-y-auto p-8 space-y-10 scrollbar-hide">
        @if($this->isAdmin)
            
            {{-- TOP STATS --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- REVENUE CARD --}}
                <div class="lg:col-span-4 bg-primary p-8 rounded-[2rem] shadow-[0_20px_50px_rgba(250,204,20,0.15)] relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-gray-950 text-[10px] font-black uppercase tracking-[0.2em] opacity-60">Pendapatan Hari Ini</p>
                        <h3 class="text-5xl font-black text-gray-950 mt-2 tracking-tighter italic">
                            <span class="text-2xl font-bold mr-1">Rp</span>{{ number_format($this->todayRevenue,0,',','.') }}
                        </h3>
                    </div>
                    <span class="material-symbols-outlined absolute -right-8 -bottom-8 text-[180px] text-black/5 group-hover:rotate-12 transition-transform duration-700">monetization_on</span>
                </div>

                {{-- VEHICLE TYPE STATS --}}
                <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($this->occupancy as $o)
                    <div class="bg-gray-900 border border-gray-800 p-7 rounded-[2rem] flex items-center justify-between hover:border-primary/50 transition-all group shadow-xl">
                        <div>
                            <p class="text-gray-500 text-[10px] uppercase font-black tracking-widest group-hover:text-primary transition-colors">{{ $o['nama_tipe'] }} Kapasitas</p>
                            <h4 class="text-4xl font-black text-white mt-2 tracking-tighter">
                                {{ $o['used_slot'] }} <span class="text-gray-700 text-xl font-bold italic">/ {{ $o['total_slot'] }}</span>
                            </h4>
                        </div>
                        <div class="size-16 rounded-2xl bg-gray-800 flex items-center justify-center group-hover:bg-primary transition-all duration-500 shadow-lg border border-gray-700">
                             <span class="material-symbols-outlined text-4xl text-primary group-hover:text-gray-950 transition-colors">
                                transportation
                             </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- AREA PROGRESS SECTION --}}
            <section>
                <div class="flex items-center gap-4 mb-8">
                    <div class="h-8 w-2 bg-primary rounded-full"></div>
                    <h3 class="text-white font-black text-xl uppercase tracking-tighter italic">Penggunaan <span class="text-primary">Zona</span></h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach($this->areaOccupancy as $area)
                    <div class="bg-gray-900 border border-gray-800 p-6 rounded-3xl hover:bg-gray-800/50 transition-all group shadow-lg">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h4 class="text-white font-black uppercase text-sm tracking-tight group-hover:text-primary transition-colors">{{ $area['nama'] }}</h4>
                                <p class="text-gray-500 text-[9px] font-black uppercase tracking-widest mt-1">{{ $area['available'] }} Slot Tersedia</p>
                            </div>
                            <span class="text-2xl font-black italic {{ str_contains($area['color'], 'red') ? 'text-red-500' : 'text-primary' }}">
                                {{ $area['percentage'] }}%
                            </span>
                        </div>

                        {{-- PROGRESS BAR --}}
                        <div class="space-y-4">
                            <div class="w-full bg-gray-950 rounded-full h-3 overflow-hidden p-0.5 border border-gray-800 shadow-inner">
                                <div class="h-full rounded-full transition-all duration-1000 ease-out shadow-[0_0_15px_rgba(250,204,20,0.2)] bg-{{ $area['color'] }}" 
                                     style="width: {{ $area['percentage'] }}%">
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-widest text-gray-600 italic">
                                <span>Dipakai: {{ $area['used'] }}</span>
                                <span>Maksimal: {{ $area['total'] }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- TABLE SECTION --}}
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-8 w-2 bg-primary rounded-full"></div>
                        <h3 class="text-white font-black text-xl uppercase tracking-tighter italic">Aktivitas <span class="text-primary">Terbaru</span></h3>
                    </div>
                </div>

                <div class="bg-gray-900 border border-gray-800 rounded-[2rem] overflow-hidden shadow-2xl">
                    <table class="w-full text-left">
                        <thead class="bg-gray-950/80 border-b border-gray-800">
                            <tr class="text-gray-500 text-[10px] uppercase font-black tracking-[0.2em]">
                                <th class="px-8 py-6">Plat Nomor</th>
                                <th class="px-8 py-6">Tipe Kendaraan</th>
                                <th class="px-8 py-6">Lokasi</th>
                                <th class="px-8 py-6">Waktu Masuk</th>
                                <th class="px-8 py-6 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            @foreach($this->recentMovements as $session)
                            <tr class="hover:bg-primary/5 transition-colors group">
                                <td class="px-8 py-5">
                                    <span class="px-4 py-2 bg-gray-800 text-primary rounded-xl font-mono font-black text-sm border border-gray-700 group-hover:border-primary/50 transition-all shadow-md">
                                        {{ $session->plat_nomor }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-gray-300 text-xs font-black uppercase tracking-widest">{{ $session->tipeKendaraan->nama_tipe }}</td>
                                <td class="px-8 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-white font-black text-xs uppercase">{{ $session->slot->area->nama_area ?? '-' }}</span>
                                        <span class="text-[9px] text-primary font-black uppercase tracking-widest italic opacity-70">{{ $session->slot->kode_slot }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-gray-400 text-xs font-black tracking-widest">{{ $session->created_at->format('H:i:s') }}</td>
                                <td class="px-8 py-5 text-center">
                                    @if($session->status == 'IN_PROGRESS')
                                        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg bg-primary/10 border border-primary/20 text-primary text-[9px] font-black uppercase tracking-[0.15em]">
                                            <span class="size-1.5 bg-primary rounded-full animate-ping"></span> 
                                            In Parking
                                        </span>
                                    @else
                                        <span class="text-gray-600 text-[9px] font-black uppercase tracking-widest italic">Completed</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            {{-- PETUGAS VIEW --}}
            <div class="flex flex-col items-center justify-center min-h-[500px] border-4 border-dashed border-gray-900 rounded-[3rem] bg-gray-950/40">
                 <div class="size-32 rounded-3xl bg-gray-900 flex items-center justify-center mb-8 border border-gray-800 shadow-2xl rotate-3 group hover:rotate-0 transition-transform">
                    <span class="material-symbols-outlined text-primary text-6xl">qr_code_scanner</span>
                 </div>
                 <h3 class="text-white text-3xl font-black uppercase tracking-tighter italic">Terminal <span class="text-primary">Officer</span></h3>
                 <p class="text-gray-500 mt-3 text-xs font-black uppercase tracking-[0.3em]">Silahkan akses terminal melalui menu sidebar</p>
                 <div class="mt-8 h-1 w-20 bg-gray-800 rounded-full"></div>
            </div>
        @endif
    </div>
</div>