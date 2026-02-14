{{-- Menggunakan h-screen + overflow-hidden agar pas di layar tanpa scroll --}}
<div class="bg-[#0B0F1A] font-sans text-gray-100 h-screen flex flex-col antialiased w-full overflow-hidden" wire:poll.5s>
    
    {{-- Internal CSS --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Space+Grotesk:wght@300;500;700&display=swap');
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        
        /* Glassmorphism Effect */
        .glass {
            background: rgba(31, 41, 55, 0.4);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Hapus scroll dari halaman */
        html, body { overflow: hidden; height: 100%; margin: 0; }
        ::-webkit-scrollbar { display: none; }
        html { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    {{-- HEADER --}}
    <header class="flex-none w-full px-10 py-3 flex items-center justify-between z-10 border-b border-white/5 bg-[#0B0F1A]/80 backdrop-blur-md">
        <div class="flex items-center gap-6">
            <div class="p-3 bg-primary rounded-lg shadow-[0_0_15px_rgba(250,204,21,0.3)]">
                <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
            </div>
            <div>
                <h1 class="text-2xl font-display font-bold text-white tracking-tighter uppercase leading-none">Kapasitas Parkir</h1>
                <p class="text-gray-500 mt-1 text-xs font-bold uppercase tracking-[0.3em]">Penggunaan Langsung</p>
            </div>
        </div>
        
        <div class="flex items-center gap-8">
            <div class="text-right border-r border-white/10 pr-8">
                <div class="text-5xl font-display font-light text-white tracking-tighter tabular-nums leading-none">
                    {{ now()->format('H:i') }}
                </div>
                <div class="text-primary mt-1 text-[10px] font-black uppercase tracking-[0.4em]">
                    {{ now()->format('l, d M Y') }}
                </div>
            </div>
            <div class="hidden xl:block">
                <div class="flex items-center gap-2 text-xs font-bold text-emerald-400 uppercase">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    System Online
                </div>
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT --}}
    <main class="flex-1 px-10 py-4 flex flex-col gap-4 overflow-hidden min-h-0">
        @foreach($vehicleData as $tipe)
            <section class="flex-1 flex flex-col min-h-0">
                {{-- HEADER TIPE: Menampilkan Progres Kumulatif Seluruh Area --}}
                <div class="flex items-center gap-6 mb-3 flex-none">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-[0.3em]">TIPE</span>
                        <h3 class="text-2xl font-display font-bold text-white tracking-tight uppercase">
                            <span class="text-primary">{{ $tipe['nama_tipe'] }}</span>
                        </h3>
                    </div>

                    <div class="flex-1 max-w-md ml-4">
                        <div class="flex justify-between items-end mb-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Kapasitas Total</span>
                            <span class="text-xs font-bold text-primary tabular-nums">{{ $tipe['used_slots'] }} / {{ $tipe['total_slots'] }}</span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-1.5 border border-white/10 overflow-hidden">
                            <div class="bg-primary h-full transition-all duration-1000" style="width: {{ $tipe['tipe_percent'] }}%"></div>
                        </div>
                    </div>
                    <div class="h-px bg-gradient-to-r from-white/10 to-transparent flex-1"></div>
                </div>

                {{-- GRID AREA --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 flex-1 min-h-0">
                    @foreach($tipe['areas'] as $area)
                        <div class="glass rounded-2xl p-5 flex flex-col justify-between hover:border-primary/40 transition-all duration-500 group relative overflow-hidden">
                            
                            {{-- Nama Area --}}
                            <div class="relative z-10">
                                <h2 class="text-xl font-display font-bold text-white group-hover:text-primary transition-colors">
                                    {{ $area['nama_area'] }}
                                </h2>
                            </div>

                            {{-- Angka Utama --}}
                            <div class="flex items-center justify-between relative z-10 my-4">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Slot Tersedia</span>
                                    <div class="text-6xl font-display font-bold text-white tabular-nums tracking-tighter group-hover:scale-105 transition-transform origin-left">
                                        {{ sprintf("%02d", $area['available']) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-display font-bold text-primary">{{ $area['percent'] }}%</div>
                                    <span class="text-[9px] text-gray-500 font-bold uppercase leading-none block">Terpakai</span>
                                </div>
                            </div>

                            {{-- PROGRESS BAR PER AREA (Contoh: Basement 2 - Motor) --}}
                            <div class="relative z-10 pt-2 border-t border-white/5">
                                <div class="flex justify-end text-[10px] font-bold tracking-tighter mb-2 text-gray-400 uppercase">
                                    <span>{{ $area['used_slots'] }} Terisi</span>
                                </div>
                                <div class="w-full bg-black/30 rounded-full h-3 p-0.5 border border-white/5">
                                    <div class="{{ $area['style']['color'] }} h-full rounded-full transition-all duration-1000 ease-out {{ $area['style']['glow'] }}" 
                                        style="width: {{ $area['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </main>
</div>