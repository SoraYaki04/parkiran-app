<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Dashboard')] class extends Component {

    public function getIsAdminProperty()
    {
        return auth()->user()->role_id === 1;
    }

};
?>

<div class="flex-1 flex flex-col h-full overflow-hidden bg-background-dark">

    {{-- HEADER DASHBOARD --}}
    <header class="h-20 px-8 flex items-center justify-between border-b border-gray-800 bg-background-dark/95 backdrop-blur z-10">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">
                Dashboard Overview
            </h2>
            <p class="text-text-muted text-sm">
                Welcome back, {{ auth()->user()->name }}
            </p>
        </div>
    </header>

    {{-- CONTENT --}}
    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">

        @if($this->isAdmin)

            {{-- ===== ADMIN DASHBOARD ===== --}}

            {{-- STATS GRID --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                {{-- Cars --}}
                <div class="bg-card-dark p-6 rounded-xl border border-gray-700 hover:border-primary/50 transition">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2.5 bg-blue-500/10 rounded-lg text-blue-400">
                            <span class="material-symbols-outlined">directions_car</span>
                        </div>
                        <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded">
                            Level 1–3
                        </span>
                    </div>

                    <p class="text-text-muted text-sm">Mobil</p>
                    <h3 class="text-2xl font-bold text-white mt-1">
                        45 <span class="text-gray-500 font-normal">/100</span>
                    </h3>

                    <div class="w-full bg-gray-700 rounded-full h-2 mt-4">
                        <div class="bg-primary h-2 rounded-full" style="width:45%"></div>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">55 spots available</p>
                </div>

                {{-- Motors --}}
                <div class="bg-card-dark p-6 rounded-xl border border-gray-700 hover:border-primary/50 transition">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2.5 bg-orange-500/10 rounded-lg text-orange-400">
                            <span class="material-symbols-outlined">two_wheeler</span>
                        </div>
                        <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded">
                            Level G
                        </span>
                    </div>

                    <p class="text-text-muted text-sm">Motor</p>
                    <h3 class="text-2xl font-bold text-white mt-1">
                        12 <span class="text-gray-500 font-normal">/50</span>
                    </h3>

                    <div class="w-full bg-gray-700 rounded-full h-2 mt-4">
                        <div class="bg-primary h-2 rounded-full" style="width:24%"></div>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">38 spots available</p>
                </div>

                {{-- Buses --}}
                <div class="bg-card-dark p-6 rounded-xl border border-gray-700 hover:border-primary/50 transition">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2.5 bg-purple-500/10 rounded-lg text-purple-400">
                            <span class="material-symbols-outlined">directions_bus</span>
                        </div>
                        <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded">
                            Zone B
                        </span>
                    </div>

                    <p class="text-text-muted text-sm">Bus</p>
                    <h3 class="text-2xl font-bold text-white mt-1">
                        2 <span class="text-gray-500 font-normal">/10</span>
                    </h3>

                    <div class="w-full bg-gray-700 rounded-full h-2 mt-4">
                        <div class="bg-primary h-2 rounded-full" style="width:20%"></div>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">8 spots available</p>
                </div>

                {{-- Revenue --}}
                <div class="bg-card-dark p-6 rounded-xl border border-gray-700 relative overflow-hidden">
                    <span class="material-symbols-outlined absolute right-4 top-4 text-[96px] text-primary opacity-10">
                        attach_money
                    </span>

                    <p class="text-text-muted text-sm">Today Revenue</p>
                    <h3 class="text-3xl font-bold text-white mt-2">
                        Rp 1.240.000
                    </h3>

                    <span class="inline-flex items-center gap-1 mt-3 text-green-400 text-xs font-semibold">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        +12% vs yesterday
                    </span>
                </div>

            </div>

            {{-- CHART + TABLE --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                {{-- CHART --}}
                <div class="bg-card-dark p-6 rounded-xl border border-gray-700">
                    <h3 class="text-white font-bold mb-6">Real-time Occupancy</h3>
                    <div class="flex justify-center">
                        <div class="relative size-56 rounded-full"
                            style="background:conic-gradient(#facc14 0% 37%, #4b5563 37% 100%)">
                            <div class="absolute inset-4 bg-card-dark rounded-full flex flex-col items-center justify-center">
                                <span class="text-sm text-gray-400">Total Filled</span>
                                <span class="text-4xl font-bold text-white">37%</span>
                                <span class="text-xs text-gray-500">59 / 160</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="xl:col-span-2 bg-card-dark rounded-xl border border-gray-700 overflow-hidden">
                    <div class="p-6 border-b border-gray-700">
                        <h3 class="text-white font-bold">Recent Movements</h3>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-gray-800 text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Plate</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Gate</th>
                                <th class="px-6 py-3 text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr class="hover:bg-gray-700/30">
                                <td class="px-6 py-3 text-green-400">IN</td>
                                <td class="px-6 py-3 text-white">ABC-1234</td>
                                <td class="px-6 py-3">Sedan</td>
                                <td class="px-6 py-3">Gate A</td>
                                <td class="px-6 py-3 text-right">10:42</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        @else

            {{-- ===== PETUGAS DASHBOARD (NANTI) ===== --}}
            <div class="text-gray-400">
                Dashboard petugas
            </div>

        @endif

    </div>
</div>
