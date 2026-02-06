<div class="w-full">
    @if($invalidSession)
        <div class="flex min-h-screen items-center justify-center bg-[#0f172a] p-6">
            <div class="bg-red-500/10 border border-red-500/50 rounded-2xl p-6 text-center">
                <span class="material-symbols-outlined text-red-400 text-5xl mb-2">error</span>
                <div class="text-red-400 font-bold text-lg">{{ $errorMessage }}</div>
            </div>
        </div>
    @else
        <main class="flex-1 w-full flex justify-center bg-[#0f172a] overflow-y-auto">
            <div class="w-full max-w-[600px] bg-[#1e293b] min-h-screen flex flex-col relative shadow-2xl">
    
                {{-- HEADER --}}
                <div class="px-5 pt-6 pb-4 sticky top-0 bg-[#1e293b]/90 backdrop-blur-md z-20 border-b border-slate-700/50">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="text-2xl font-extrabold text-white tracking-tight">
                                {{ !$selectedAreaId ? 'Pilih Lokasi' : 'Pilih Slot' }}
                            </h2>
                            <div class="flex items-center gap-1.5 text-slate-400 mt-1">
                                <span class="material-symbols-outlined text-base text-primary">location_on</span>
                                <span class="text-xs font-semibold uppercase tracking-wider">
                                    {{ $selectedAreaId ? $areas->firstWhere('id', $selectedAreaId)->nama_area : 'Area Parkir Tersedia' }}
                                </span>
                            </div>
                        </div>

                        {{-- Tombol Ganti Area (Hanya muncul jika area sudah dipilih) --}}
                        @if($selectedAreaId)
                            <button wire:click="$set('selectedAreaId', null)" class="flex items-center gap-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg border border-slate-700 transition-all">
                                <span class="material-symbols-outlined text-sm text-primary">sync</span>
                                <span class="text-[10px] font-bold text-white uppercase">Ganti</span>
                            </button>
                        @endif
                    </div>
    
                    @if($selectedAreaId)
                        {{-- INPUT PLAT NOMOR --}}
                        <div class="mb-4">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] ml-1 mb-2 block">
                                Input Plat Nomor
                            </label>
                            <div class="relative">
                                <input 
                                    type="text"
                                    wire:model.defer="platNomor"
                                    placeholder="B-1234-ABC"
                                    oninput="formatPlatDash(this)"
                                    maxlength="13"
                                    class="w-full bg-slate-900 border-2 border-slate-700 rounded-xl px-4 py-4 text-center text-2xl font-black text-white uppercase tracking-widest focus:border-primary focus:ring-0 transition-all placeholder:text-slate-700"
                                />


                                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-slate-600">transportation</span>
                                </div>
                            </div>
                        </div>
        
                        {{-- LEGEND --}}
                        <div class="flex items-center gap-4 overflow-x-auto pb-1 no-scrollbar">
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="size-2.5 rounded-full border border-slate-500"></div>
                                <span class="text-[11px] font-medium text-slate-400">Tersedia</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="size-2.5 rounded-full bg-slate-600"></div>
                                <span class="text-[11px] font-medium text-slate-400">Terisi</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="size-2.5 rounded-full bg-primary shadow-[0_0_8px_rgba(250,204,21,0.6)]"></div>
                                <span class="text-[11px] font-medium text-primary">Pilihan</span>
                            </div>
                        </div>
                    @endif
                </div>
    
                {{-- KONTEN UTAMA --}}
                <div class="flex-1 px-5 py-6 pb-44">
                    
                    {{-- TAMPILAN PILIH AREA --}}
                    @if(!$selectedAreaId)
                        <div class="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
                            @foreach($areas as $area)
                                <button 
                                    wire:click="selectArea({{ $area->id }})"
                                    class="w-full flex items-center justify-between p-5 rounded-2xl bg-slate-800/40 border-2 border-slate-700 hover:border-primary/50 transition-all group active:scale-[0.98]"
                                >
                                    <div class="flex items-center gap-4 text-left">
                                        <div class="h-12 w-12 rounded-xl bg-slate-900 flex items-center justify-center border border-slate-700 group-hover:bg-primary/10 transition-colors">
                                            <span class="material-symbols-outlined text-primary text-3xl">floor</span>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-black text-white leading-tight uppercase tracking-tight">{{ $area->nama_area }}</h3>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="material-symbols-outlined text-slate-600 group-hover:text-primary transition-colors">arrow_forward_ios</span>
                                    </div>
                                </button>
                            @endforeach

                            <div class="p-4 rounded-xl bg-blue-500/5 border border-blue-500/20">
                                <p class="text-[11px] text-blue-400 text-center font-medium leading-relaxed">
                                    Silakan pilih area parkir.
                                </p>
                            </div>
                        </div>
                    {{-- TAMPILAN PILIH SLOT --}}
                    @else
                        <div class="animate-in fade-in duration-500">
                            {{-- Penanda Jalur Masuk --}}
                            <div class="flex flex-col items-center mb-8">
                                <div class="w-full h-px bg-gradient-to-r from-transparent via-slate-600 to-transparent"></div>
                                <span class="px-4 py-1 bg-slate-800 rounded-full text-[9px] font-bold text-slate-500 tracking-[0.2em] -mt-3 uppercase">
                                    Pintu Masuk
                                </span>
                            </div>

                            <div class="grid grid-cols-4 gap-3">
                                @foreach($slots as $slot)
                                    @php
                                        $isSelected = $slotId === $slot->id;
                                        $isTerisi = $slot->status === 'terisi';
                                    @endphp

                                    <div wire:key="slot-{{ $slot->id }}" class="relative">
                                        {{-- SLOT TERISI --}}
                                        @if($isTerisi)
                                            <div
                                                class="relative w-full aspect-[3/4] flex flex-col items-center justify-center
                                                    rounded-xl bg-slate-700/40 border border-slate-600/40
                                                    text-slate-400 cursor-not-allowed"
                                            >
                                                <span class="text-sm font-black tracking-tight">
                                                    {{ $slot->kode_slot }}
                                                </span>
                                                <span class="material-symbols-outlined text-slate-500 text-sm mt-1">
                                                    block
                                                </span>

                                                {{-- badge kecil --}}
                                                <span class="absolute bottom-2 text-[8px] uppercase font-bold tracking-widest text-slate-500">
                                                    Terisi
                                                </span>
                                            </div>

                                        {{-- SLOT KOSONG --}}
                                        @else
                                            <button
                                                wire:click="selectSlot({{ $slot->id }})"
                                                class="relative w-full aspect-[3/4] flex flex-col items-center justify-center
                                                    rounded-xl border-2 transition-all duration-200 active:scale-90
                                                    {{ $isSelected
                                                        ? 'bg-primary border-primary shadow-[0_0_20px_rgba(250,204,21,0.35)] z-10'
                                                        : 'bg-slate-800/40 border-slate-700 hover:border-primary/50'
                                                    }}"
                                            >
                                                <span class="text-lg font-black tracking-tight
                                                    {{ $isSelected ? 'text-black' : 'text-white' }}">
                                                    {{ $slot->kode_slot }}
                                                </span>

                                                @if($isSelected)
                                                    <span class="text-[8px] font-black text-black/60 mt-0.5 tracking-widest">
                                                        TERPILIH
                                                    </span>
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
    
                {{-- FLOATING CONFIRMATION CARD (Hanya jika slot dipilih) --}}
                @if($selectedAreaId)
                    <div class="fixed bottom-0 left-0 right-0 w-full max-w-[600px] mx-auto p-4 z-30">
                        <div class="bg-slate-800/95 backdrop-blur-xl border border-slate-700 rounded-2xl shadow-[0_-10px_40px_rgba(0,0,0,0.5)] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0 flex-1"> 
                                    <div class="flex flex-col shrink-0">
                                        <span class="text-[9px] text-slate-400 uppercase font-bold tracking-tighter">Slot</span>
                                        <span class="text-xl font-black text-primary leading-none">
                                            {{ $slotId ? $slots->firstWhere('id', $slotId)->kode_slot : '-' }}
                                        </span>
                                    </div>
                                    <div class="w-px h-8 bg-slate-700 shrink-0"></div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[9px] text-slate-400 uppercase font-bold tracking-tighter">Plat</span>
                                        <span class="text-xl font-black text-white leading-none uppercase truncate">
                                            {{ $platNomor ?: '-' }}
                                        </span>
                                    </div>
                                </div>
            
                                <button
                                    wire:click="confirm"
                                    @disabled(!$slotId || !$platNomor)
                                    class="shrink-0 px-5 py-3.5
                                        bg-primary hover:bg-yellow-400 active:scale-95
                                        transition-all text-black font-extrabold text-xs sm:text-sm
                                        rounded-xl flex items-center justify-center gap-2
                                        shadow-[0_8px_20px_rgba(250,204,21,0.3)]
                                        disabled:opacity-20 disabled:grayscale"
                                >
                                    <span class="whitespace-nowrap">KONFIRMASI</span>
                                    <span class="material-symbols-outlined text-lg font-bold">arrow_forward</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </main>
    @endif
</div>

@push('scripts')
<script>
    function formatPlatDash(el) {
        let value = el.value.toUpperCase();

        // buang semua kecuali huruf & angka
        value = value.replace(/[^A-Z0-9]/g, '');

        let depan = '';
        let nomor = '';
        let belakang = '';

        // 1–2 huruf depan
        depan = value.match(/^[A-Z]{1,2}/)?.[0] ?? '';
        value = value.slice(depan.length);

        // 1–5 angka tengah
        nomor = value.match(/^\d{1,5}/)?.[0] ?? '';
        value = value.slice(nomor.length);

        // 0–3 huruf belakang
        belakang = value.slice(0, 3);

        let hasil = depan;
        if (nomor) hasil += '-' + nomor;
        if (belakang) hasil += '-' + belakang;

        el.value = hasil;
    }
</script>

@endpush
