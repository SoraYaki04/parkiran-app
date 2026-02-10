<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Symfony\Component\HttpFoundation\Response;

class LogPageVisit
{
    /**
     * Mapping route name => label menu yang user-friendly.
     */
    private array $menuLabels = [
        'dashboard'         => 'Dashboard',
        'exit'              => 'Transaksi',
        'petugas.exit'      => 'Transaksi',
        'data_parkir'       => 'Data Parkir',
        'petugas.data_parkir' => 'Data Parkir',
        'admin.kendaraan'   => 'Tipe Kendaraan',
        'admin.tarif'       => 'Tarif Parkir',
        'admin.area'        => 'Area Parkir',
        'admin.member'      => 'Member',
        'admin.tier_member' => 'Tier Member',
        'admin.users'       => 'Manajemen User',
        'laporan'           => 'Laporan & Analytics',
        'admin.log_aktivitas' => 'Log Aktivitas',
        'admin.backup'      => 'Backup Database',
        'admin.exit'        => 'Transaksi',
        'profile'           => 'Profile',
        'settings'          => 'Settings',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya log untuk GET request (buka halaman), user yang login, dan bukan Livewire request
        if (
            $request->isMethod('GET') &&
            auth()->check() &&
            !$request->header('X-Livewire') && // Skip Livewire AJAX calls
            $request->route()
        ) {
            $routeName = $request->route()->getName();

            // Hanya log route yang terdaftar di menu
            if ($routeName && isset($this->menuLabels[$routeName])) {
                $menuLabel = $this->menuLabels[$routeName];
                $user = auth()->user();

                // Throttle: jangan log jika user baru saja membuka halaman yang sama (dalam 60 detik terakhir)
                $recentVisit = ActivityLog::where('user_id', $user->id)
                    ->where('action', 'PAGE_VISIT')
                    ->where('target', $menuLabel)
                    ->where('created_at', '>=', now()->subSeconds(60))
                    ->exists();

                if (!$recentVisit) {
                    ActivityLog::log(
                        action: 'PAGE_VISIT',
                        description: "Membuka halaman {$menuLabel}",
                        target: $menuLabel,
                        category: 'NAVIGATION',
                    );
                }
            }
        }

        return $response;
    }
}
