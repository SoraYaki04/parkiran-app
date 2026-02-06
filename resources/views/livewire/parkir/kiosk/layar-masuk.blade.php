<div
    wire:poll.1s="checkStatus"
    class="relative flex min-h-screen w-full flex-col items-center
           {{ $state === 'qr_generated' ? 'justify-center' : 'justify-between' }}
           py-10 px-6 bg-kiosk-bg text-white overflow-hidden">
    {{-- HEADER --}}
    <header class="flex flex-col items-center gap-4 w-full max-w-4xl text-center z-10">
        <h1 class="text-primary text-5xl md:text-6xl font-black leading-tight tracking-tight drop-shadow-lg">
            MESIN PARKIR MASUK
        </h1>

        {{-- JAM & STATUS HANYA SAAT IDLE --}}
        {{-- @if ($state === 'idle')
            <div class="flex items-center justify-center gap-3 text-gray-300">
                <span class="material-symbols-outlined text-[28px]">schedule</span>
                <p id="clock" class="text-xl md:text-2xl font-medium tracking-wide"></p>
            </div>

        @endif --}}
    </header>

    {{-- MAIN --}}
    <main class="flex flex-col items-center justify-center w-full max-w-5xl flex-1 gap-12 my-8">

        {{-- Tombol awal --}}
        @if ($state === 'idle')
            {{-- Pilih kendaraan --}}
            <div class="flex flex-wrap justify-center gap-4 mt-6">
                <button wire:click="pilihKendaraan(1)"
                    class="flex items-center gap-3 rounded-lg border px-3 py-2 {{ $tipeKendaraanId === 1 ? 'bg-primary text-black' : 'bg-white/5 text-white' }}">
                    <span class="material-symbols-outlined text-primary text-[28px]">two_wheeler</span>
                    <p class="text-base font-bold">Motor</p>
                </button>

                <button wire:click="pilihKendaraan(2)"
                    class="flex items-center gap-3 rounded-lg border px-3 py-2 {{ $tipeKendaraanId === 2 ? 'bg-primary text-black' : 'bg-white/5 text-white' }}">
                    <span class="material-symbols-outlined text-primary text-[28px]">directions_car</span>
                    <p class="text-base font-bold">Mobil</p>
                </button>
            </div>

            {{-- Tombol utama --}}
            <div class="relative flex items-center justify-center w-full max-w-[800px] mt-6">
                <div class="sonar-ring"></div>
                <div class="sonar-ring delay"></div>

                <button wire:click="createSession"
                    class="group relative z-10 flex w-full cursor-pointer flex-col items-center justify-center overflow-hidden
                        rounded-2xl bg-primary py-12 px-8
                        shadow-[0_0_50px_-10px_rgba(250,204,20,0.3)]
                        transition-all duration-300
                        hover:scale-[1.02]
                        active:scale-[0.98]
                        auto-shimmer">
                    <div class="flex items-center gap-6">
                        <span class="material-symbols-outlined text-background-dark text-[80px]">touch_app</span>
                        <div class="flex flex-col items-start text-left">
                            <span class="text-background-dark text-5xl font-black leading-none tracking-tight">
                                TEKAN UNTUK
                            </span>
                            <span class="text-background-dark text-5xl font-black leading-none tracking-tight">
                                MASUK PARKIR
                            </span>
                        </div>
                    </div>

                    <p class="mt-4 text-background-dark/70 text-lg font-bold uppercase tracking-widest">
                        Press for Parking Entry
                    </p>

                </button>
            </div>
        @endif



        {{-- QR Generated --}}
        @if ($state === 'qr_generated')
        <div
            class="w-full min-h-[70vh] max-w-[600px] flex flex-col items-center gap-6">

            {{-- Main Instruction --}}
            <div class="text-center">
                <p class="text-primary text-2xl font-bold mb-2">SCAN QR CODE INI</p>
                <p class="text-gray-400 text-lg">Arahkan kamera HP Anda ke QR di bawah</p>
            </div>

            {{-- QR Code Display --}}
            <div
                class="relative flex w-full max-w-[400px] flex-col items-center justify-center gap-4
                    rounded-2xl border-2 border-primary/50
                    bg-gradient-to-b from-white/10 to-white/5 px-8 py-10
                    shadow-[0_0_60px_-10px_rgba(250,204,20,0.3)]">

                @if ($state === 'qr_generated' && $qrUrl)
                    <img src="{{ $qrUrl }}" alt="QR Parkir"
                        class="w-[280px] h-[280px] object-contain bg-white p-4 rounded-xl shadow-lg" />
                @endif

                {{-- Timer indicator --}}
                <div class="flex items-center gap-2 mt-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <p class="text-gray-300 text-sm font-medium">
                        Menunggu scan... (QR berlaku 5 menit)
                    </p>
                </div>

                {{-- Scanner decorative corners --}}
                <div class="absolute top-0 left-0 h-6 w-6 border-t-4 border-l-4 border-primary rounded-tl-lg"></div>
                <div class="absolute top-0 right-0 h-6 w-6 border-t-4 border-r-4 border-primary rounded-tr-lg"></div>
                <div class="absolute bottom-0 left-0 h-6 w-6 border-b-4 border-l-4 border-primary rounded-bl-lg"></div>
                <div class="absolute bottom-0 right-0 h-6 w-6 border-b-4 border-r-4 border-primary rounded-br-lg"></div>
            </div>

            {{-- Simple Instructions --}}
            <div class="w-full max-w-[500px] bg-gray-800/50 border border-gray-700/50 rounded-xl p-5">
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary/20 flex-shrink-0">
                        <span class="material-symbols-outlined text-primary text-2xl">photo_camera</span>
                    </div>
                    <div>
                        <p class="text-white font-semibold">Buka Kamera HP</p>
                        <p class="text-gray-400 text-sm">Arahkan ke QR Code → Browser akan terbuka otomatis</p>
                    </div>
                </div>
            </div>


        </div>
        @endif

        {{-- ================= WAITING INPUT ================= --}}
        @if ($state === 'waiting_input')
            <div class="flex flex-col items-center gap-8 text-center animate-in fade-in zoom-in duration-500">
                
                {{-- Visual Koneksi --}}
                <div class="relative flex items-center justify-center">
                    {{-- Efek Radar/Pulse di Belakang --}}
                    <div class="absolute h-32 w-32 animate-ping rounded-full bg-primary/20"></div>
                    <div class="absolute h-44 w-44 animate-pulse rounded-full bg-primary/10"></div>
                    
                    {{-- Icon Container --}}
                    <div class="relative z-10 flex h-28 w-28 items-center justify-center rounded-3xl bg-primary shadow-[0_0_40px_rgba(250,204,20,0.5)]">
                        <span class="material-symbols-outlined text-black text-[60px]">phonelink_setup</span>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="flex flex-col items-center gap-4">
                    <div class="inline-flex items-center gap-2 rounded-full border border-green-500/30 bg-green-500/10 px-4 py-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                        </span>
                        <span class="text-xs font-bold uppercase tracking-[0.2em] text-green-400">Smartphone Terkoneksi</span>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-4xl font-black tracking-tight text-white">
                            LANJUTKAN DI <span class="text-primary">PONSEL</span>
                        </h2>
                        <p class="mx-auto max-w-md text-xl text-gray-400">
                            Silakan selesaikan pengisian data kendaraan melalui halaman web yang terbuka di HP Anda.
                        </p>
                    </div>
                </div>

                {{-- Loader kecil --}}
                <div class="mt-4 flex flex-col items-center gap-2">
                    <div class="flex gap-1">
                        <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary"></div>
                        <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary [animation-delay:0.2s]"></div>
                        <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary [animation-delay:0.4s]"></div>
                    </div>
                    <p class="text-xs font-medium uppercase tracking-widest text-gray-600">Menunggu Konfirmasi</p>
                </div>
            </div>
        @endif

        {{-- ================= SUCCESS ================= --}}
        @if ($state === 'success')
            <div class="flex flex-col items-center justify-center gap-10 text-center animate-in zoom-in duration-500">
                
                {{-- Visual Success dengan Efek Cahaya --}}
                <div class="relative flex items-center justify-center">
                    {{-- Glow effect di belakang icon --}}
                    <div class="absolute h-48 w-48 animate-pulse rounded-full bg-green-500/20 blur-2xl"></div>
                    <div class="absolute h-36 w-36 animate-ping rounded-full bg-green-500/10"></div>
                    
                    {{-- Icon Centang Raksasa --}}
                    <div class="relative z-10 flex h-32 w-32 items-center justify-center rounded-full bg-green-500 shadow-[0_0_50px_rgba(34,197,94,0.6)]">
                        <span class="material-symbols-outlined text-white text-[80px] font-bold">check</span>
                    </div>
                </div>

                {{-- Pesan Utama --}}
                <div class="space-y-4">
                    <div class="space-y-1">
                        <h1 class="text-green-400 text-6xl md:text-7xl font-black leading-none tracking-tighter drop-shadow-sm">
                            SILAKAN MASUK
                        </h1>
                        <p class="text-white/60 text-xl font-medium tracking-wide">
                            ACCESS GRANTED • ENJOY YOUR PARKING
                        </p>
                    </div>

                    {{-- Instruksi Tambahan --}}
                    <div class="inline-flex items-center gap-3 rounded-2xl bg-white/5 border border-white/10 px-8 py-4">
                        <span class="material-symbols-outlined text-green-400">gate</span>
                        <p class="text-gray-300 text-lg">
                            Palang pintu akan terbuka otomatis
                        </p>
                    </div>
                </div>

                {{-- Auto-reset indicator --}}
                <div class="mt-4">
                    {{-- Auto reset --}}
                    <p class="text-gray-500 text-xs uppercase tracking-[0.3em]">
                        Kembali ke menu utama dalam {{ $successCountdown }} detik
                    </p>
                </div>
            </div>
        @endif

    </main>

    {{-- FOOTER --}}
    <footer class="w-full text-center">
        <div class="inline-flex items-center justify-center gap-2 rounded-full bg-black/20 px-6 py-3 border border-white/5">
            <span class="material-symbols-outlined text-primary text-[20px]">support_agent</span>
            <p class="text-gray-300 text-sm font-medium">Butuh bantuan? Hubungi petugas di pos keluar.</p>
        </div>
    </footer>

</div>