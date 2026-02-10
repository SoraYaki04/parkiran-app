<?php

use App\Livewire\Parkir\Kiosk\LayarMasuk;
use App\Livewire\Parkir\DisplayParkir;
use App\Livewire\Parkir\Mobile\PilihSlot;
use App\Livewire\Parkir\Mobile\Karcis;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== REDIRECT ROOT =====
Route::redirect('/', '/login')->name('home');

Route::get('/display', DisplayParkir::class)->name('display');

// ===== PUBLIC (KIOSK & MOBILE) =====
Route::prefix('kiosk')->name('kiosk.')->group(function () {
    Route::get('/masuk', LayarMasuk::class)->name('masuk');
});

Route::get('/parkir/mobile/pilih-slot/{token}', PilihSlot::class)
    ->name('mobile-parkir.pilih-slot');

Route::get('/parkir/mobile/karcis/{token}', Karcis::class)
    ->name('mobile.karcis');

// ===== GUEST =====
Route::middleware('guest')->group(function () {
    Volt::route('login', 'pages.auth.login')->name('login');
    Volt::route('register', 'pages.auth.register')->name('register');
    Volt::route('forgot-password', 'pages.auth.forgot-password')->name('password.request');
    Volt::route('reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');
});

// ===== AUTH =====
Route::middleware('auth')->group(function () {

    // DASHBOARD (SEMUA ROLE)
    Volt::route('dashboard', 'pages.dashboard')->name('dashboard');

    Volt::route('profile', 'pages.profile')->name('profile');
    Volt::route('settings', 'pages.settings')->name('settings');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // ================= TRANSAKSI =================
    // ADMIN & PETUGAS
    Route::middleware('role:admin,petugas')->group(function () {
        Volt::route('transaksi', 'pages.exit.index')->name('exit');

        Volt::route('data_parkir', 'pages.data_parkir.index')
            ->name('data_parkir');
    });

    // ================= LAPORAN =================
    // ADMIN & OWNER
    Route::middleware('role:admin,owner')->group(function () {

        Volt::route('laporan', 'pages.admin.laporan.index')
            ->name('laporan');

        Route::get('export/laporan-harian/pdf', [\App\Http\Controllers\LaporanExportController::class, 'pdfHarian'])
            ->name('export.laporan-harian.pdf');

        Route::get('export/laporan-harian/excel', [\App\Http\Controllers\LaporanExportController::class, 'excelHarian'])
            ->name('export.laporan-harian.excel');

        Route::get('export/rentang/pdf', [\App\Http\Controllers\LaporanExportController::class, 'pdfRentang'])
            ->name('export.rentang.pdf');

        Route::get('export/rentang/excel', [\App\Http\Controllers\LaporanExportController::class, 'excelRentang'])
            ->name('export.rentang.excel');

        Route::get('export/occupancy/pdf', [\App\Http\Controllers\LaporanExportController::class, 'pdfOccupancy'])
            ->name('export.occupancy.pdf');

        Route::get('export/analytics/csv', [\App\Http\Controllers\LaporanExportController::class, 'csvAnalytics'])
            ->name('export.analytics.csv');
    });

    // ================= DATA MASTER (ADMIN & PETUGAS) =================
    Route::middleware('role:admin,petugas')->prefix('admin')->name('admin.')->group(function () {
        Volt::route('kendaraan', 'pages.admin.kendaraan.index')
            ->name('kendaraan');

        Volt::route('tarif', 'pages.admin.tarif.index')
            ->name('tarif');

        Volt::route('area', 'pages.admin.area.index')
            ->name('area');

        Volt::route('member', 'pages.admin.member.index1')
            ->name('member');

        Volt::route('tier_member', 'pages.admin.member.index2')
            ->name('tier_member');
    });

    // ================= ADMIN ONLY (SYSTEM) =================
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Volt::route('users', 'pages.admin.users.index')
            ->name('users');

        Volt::route('log_aktivitas', 'pages.admin.log_aktivitas.index')
            ->name('log_aktivitas');

        Volt::route('backup', 'pages.admin.backup.index')
            ->name('backup');
    });
});
