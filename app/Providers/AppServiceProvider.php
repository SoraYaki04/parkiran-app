<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Memaksa Laravel menggunakan APP_URL untuk semua URL generation
        // Ini memastikan QR code dan URL lainnya menggunakan IP yang benar
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        // Pastikan HTTPS digunakan jika APP_URL dimulai dengan https://
        if (env('APP_URL') && str_starts_with(env('APP_URL'), 'https')) {
            URL::forceScheme('https');
        }
    }
}
