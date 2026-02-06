<div class="flex min-h-screen items-center justify-center bg-[#0f172a] p-6 text-white font-sans">
    
    <div class="relative w-full max-w-sm overflow-hidden rounded-3xl bg-[#1e293b] shadow-2xl">
        
        {{-- Sisi Atas (Ticket Header) --}}
        <div class="bg-primary p-6 text-center text-black">
            <h2 class="text-xs font-black uppercase tracking-[0.3em] opacity-70">Entry Ticket</h2>
            <h1 class="text-2xl font-black"> {{ $session->slot->area->nama_area ?? '-' }}</h1>
        </div>

        {{-- Notch (Lekukan Tiket) --}}
        <div class="absolute top-[88px] -left-3 h-6 w-6 rounded-full bg-[#0f172a]"></div>
        <div class="absolute top-[88px] -right-3 h-6 w-6 rounded-full bg-[#0f172a]"></div>

        {{-- Konten Tiket --}}
        <div class="p-8 space-y-6">
            
            {{-- Info Utama --}}
            <div class="grid grid-cols-2 gap-4 border-b border-dashed border-slate-700 pb-6">
                <div>
                    <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Nomor Plat</p>
                    <p class="text-xl font-black text-white uppercase">{{ $session->plat_nomor }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Slot Parkir</p>
                    <p class="text-xl font-black text-primary">{{ $session->slot->kode_slot }}</p>
                </div>
            </div>

            {{-- Detail Waktu & Kendaraan --}}
            <div class="space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400">Jenis Kendaraan</span>
                        <span class="font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-primary">
                                transportation
                            </span>
                            {{ $session->tipeKendaraan->nama_tipe ?? '-' }}
                        </span>

                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400">Waktu Masuk</span>
                    <span class="font-bold">{{ $session->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400">Ref ID</span>
                    <span class="font-mono text-xs text-slate-500">#{{ strtoupper($session->kode_session) }}</span>
                </div>
            </div>

            {{-- QR CODE AREA --}}
            <div class="relative flex flex-col items-center justify-center rounded-2xl bg-white p-6 shadow-inner">
                <img src="{{ $qrExit }}" class="w-48 h-48 object-contain">
                <p class="mt-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Scan to Exit</p>
            </div>

            {{-- Warning / Footer --}}
            <div class="text-center space-y-2">
                <p class="text-[11px] text-slate-400 italic">
                    Jangan hilangkan karcis ini. Scan QR di atas pada gate keluar untuk pembayaran.
                </p>
            </div>

            {{-- Action Button --}}
            <div class="pt-4 no-print">
                <button onclick="window.print()"
                    class="group w-full flex items-center justify-center gap-2 rounded-xl bg-primary py-4 font-black text-black transition-all hover:bg-yellow-400 active:scale-95 shadow-[0_10px_20px_rgba(250,204,21,0.2)]">
                    <span class="material-symbols-outlined">download</span>
                    SIMPAN KARCIS
                </button>
            </div>

        </div>

        {{-- Bagian Bawah Dekorasi --}}
        <div class="flex justify-center gap-2 pb-4">
            @for($i = 0; $i < 10; $i++)
                <div class="h-1 w-1 rounded-full bg-slate-800"></div>
            @endfor
        </div>

    </div>
</div>
