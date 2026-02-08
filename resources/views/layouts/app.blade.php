<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Material Symbols -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased bg-gray-900 text-gray-100">
    <div class="h-screen flex overflow-hidden">

        @if(auth()->check())
            {{-- SIDEBAR DASHBOARD --}}
            <livewire:layout.navigation />
        @endif

        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- TOP BAR (optional) --}}
            {{-- @if(auth()->check() && !request()->routeIs('dashboard'))
                <livewire:layout.navigation />
            @endif --}}

            {{-- PAGE HEADER --}}
            {{-- @if (isset($header))
                <header class="border-b border-gray-800 px-8 py-6 bg-gray-900">
                    {{ $header }}
                </header>
            @endif --}}

            {{-- PAGE CONTENT --}}
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>

    </div>

    <!-- GLOBAL NOTIFICATION -->
    <div x-data="{ 
            show: false, 
            message: '', 
            type: 'success' 
        }"
        x-on:notify.window="
            show = true; 
            message = $event.detail.message; 
            type = $event.detail.type || 'success';
            setTimeout(() => show = false, 2500)
        "
        x-show="show"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 -translate-y-12 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-12"
        class="fixed top-8 left-0 right-0 z-[100] flex justify-center pointer-events-none p-4"
        style="display: none;">

        <div class="relative">
            <div :class="type === 'success' ? 'bg-primary/10' : 'bg-red-500/10'" 
                class="absolute inset-0 blur-3xl rounded-full"></div>

            <div class="relative bg-[#111827]/60 backdrop-blur-xl border border-white/5 px-6 py-4 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] flex items-center gap-4 min-w-[320px] pointer-events-auto">
                
                <div :class="type === 'success' ? 'bg-primary' : 'bg-red-500'" 
                    class="absolute left-0 top-1/4 bottom-1/4 w-0.5 rounded-r-full shadow-[0_0_10px_rgba(var(--color-primary),0.5)]"></div>

                <div :class="type === 'success' ? 'bg-primary/10 text-primary' : 'bg-red-500/10 text-red-400'" 
                    class="w-10 h-10 rounded-xl flex items-center justify-center border border-white/5">
                    <span class="material-symbols-outlined text-2xl"
                        x-text="type === 'success' ? 'check_circle' : 'error'"></span>
                </div>

                <div class="flex flex-col flex-1">
                    <h3 class="text-white font-black text-[10px] uppercase tracking-[0.2em] opacity-50"
                        x-text="type === 'success' ? 'Notifikasi Sistem' : 'Sistem Alert'"></h3>
                    <p class="text-slate-100 text-sm font-semibold tracking-tight mt-0.5" x-text="message"></p>
                </div>
            </div>
        </div>
    </div>


    @livewireScripts

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    @stack('scripts')
</body>

</html>