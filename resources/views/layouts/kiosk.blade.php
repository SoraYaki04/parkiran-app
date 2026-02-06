<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kiosk Parkir</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Material Symbols -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>

        @keyframes sonar {
            0% {
                transform: scale(0.7);
                opacity: 0.45;
            }
            40% {
                opacity: 0.30;
            }
            70% {
                opacity: 0.20;
            }
            100% {
                transform: scale(1.12);
                opacity: 0;
            }
        }

        .sonar-ring {
            position: absolute;
            inset: -26px;
            border-radius: 1.6rem;
            border: 2px solid rgba(250, 204, 20, 0.35);
            animation: sonar 3.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            pointer-events: none;
            filter: blur(0.3px);
        }

        .sonar-ring.delay {
            animation-delay: 1.9s;
        }



        @keyframes pulse-soft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .animate-pulse-soft {
            animation: pulse-soft 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }

        .auto-shimmer::before {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(
                120deg,
                transparent 30%,
                rgba(255,255,255,0.35) 50%,
                transparent 70%
            );
            animation: shimmer 2.8s ease-in-out infinite;
        }

    </style>

    @livewireStyles
</head>
<body class="antialiased bg-gray-900 text-white font-display overflow-hidden">

    {{ $slot }}

    @livewireScripts
    @stack('scripts')

    <script>
        function updateClock() {
            const el = document.getElementById('clock');
            if (!el) return;

            const now = new Date();
            el.innerText =
                now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) +
                ' | ' +
                now.toLocaleDateString('id-ID');
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>

</body>
</html>
