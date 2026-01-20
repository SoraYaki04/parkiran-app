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
@livewireScripts
</body>

</html>