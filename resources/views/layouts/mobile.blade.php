<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Mobile Parking' }}</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .bg-[#0f172a] { background: white !important; }
        }
    </style>
    
    @livewireStyles
</head>
<body class="bg-background-light dark:bg-background-dark font-display flex flex-col items-center justify-center min-h-screen text-white">
    {{ $slot }}
    
    @livewireScripts
    @stack('scripts')
</body>
</html>