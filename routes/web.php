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
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ===== 1. REDIRECT ROOT TO LOGIN =====
Route::redirect('/', '/login')->name('home');

Route::get('/display', DisplayParkir::class)->name('display');

// ===== 2. PUBLIC ROUTES (Kiosk & Mobile - Tanpa Login) =====
Route::prefix('kiosk')->name('kiosk.')->group(function () {
    Route::get('/masuk', LayarMasuk::class)
        ->name('masuk');
});

Route::get(
    '/parkir/mobile/pilih-slot/{token}',
    PilihSlot::class
)->name('mobile-parkir.pilih-slot');

Route::get('/parkir/mobile/karcis/{token}', Karcis::class)
    ->name('mobile.karcis');





// ===== 3. GUEST ROUTES (Untuk user belum login) =====
Route::middleware('guest')->group(function () {
    // Auth routes menggunakan Livewire Volt
    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('register', 'pages.auth.register')
        ->name('register');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

// ===== 4. AUTHENTICATED ROUTES (Untuk user sudah login) =====
Route::middleware('auth')->group(function () {
    // Dashboard dan Profile
    Volt::route('dashboard', 'pages.dashboard')
        ->name('dashboard');
    
    Volt::route('profile', 'pages.profile')
        ->name('profile');

    Volt::route('settings', 'pages.settings')
            ->name('settings');
    
    // Password confirmation (untuk sensitive actions)
    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
    
    // ===== ADMIN ROUTES (Hanya untuk admin dan owner) =====
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

        // ?  Dashboard Admin
        Volt::route('dashboard', 'pages.admin.dashboard')
            ->name('dashboard');

        Volt::route('exit', 'pages.admin.exit.index')
            ->name('exit.index');

        Volt::route('data_parkir', 'pages.admin.data_parkir.index')
            ->name('data_parkir.index');

        // ? Manajemen Kendaraan
        Volt::route('kendaraan', 'pages.admin.kendaraan.index')
            ->name('kendaraan.index');;

        // ? Manajemen Tarif
        Volt::route('tarif', 'pages.admin.tarif.index')
            ->name('tarif.index');

        // ? Manajemen Area
        Volt::route('area', 'pages.admin.area.index')
            ->name('area.index');

        // ? Manajemen Pricing Rules
        Volt::route('pricing', 'pages.admin.pricing.index')->name('pricing.index');
        Volt::route('pricing/create', 'pages.admin.pricing.form')->name('pricing.create');
        Volt::route('pricing/{id}/edit', 'pages.admin.pricing.form')->name('pricing.edit');
        
        // ? Manajemen Member
        Volt::route('member', 'pages.admin.member.index1')
            ->name('member.index1');
        Volt::route('tier_member', 'pages.admin.member.index2')
            ->name('member.index2');

        //  ? Manajemen User
        Volt::route('users', 'pages.admin.users.index')
            ->name('users.index');
        
        // ? Laporan dan Statistik
        Volt::route('laporan', 'pages.admin.laporan.index')
            ->name('laporan.index');

        // ? Export Laporan
        Route::get('export/harian/pdf', [\App\Http\Controllers\LaporanExportController::class, 'pdfHarian'])->name('export.harian.pdf');
        Route::get('export/harian/excel', [\App\Http\Controllers\LaporanExportController::class, 'excelHarian'])->name('export.harian.excel');
        Route::get('export/rentang/pdf', [\App\Http\Controllers\LaporanExportController::class, 'pdfRentang'])->name('export.rentang.pdf');
        Route::get('export/rentang/excel', [\App\Http\Controllers\LaporanExportController::class, 'excelRentang'])->name('export.rentang.excel');
        Route::get('export/occupancy/pdf', [\App\Http\Controllers\LaporanExportController::class, 'pdfOccupancy'])->name('export.occupancy.pdf');

        // ? Loh Aktivitas
        Volt::route('log_aktivitas', 'pages.admin.log_aktivitas.index')
            ->name('log_aktivitas.index');
        
    });
    
    // Tambahkan route lainnya untuk sistem parkir di sini
    // Contoh:
    // Volt::route('parking-in', 'pages.parking.in')
    //     ->name('parking.in');
    // 
    // Volt::route('parking-out', 'pages.parking.out')
    //     ->name('parking.out');
    // 
    // Volt::route('reports', 'pages.reports')
    //     ->name('reports');
});



// ===== 4. OPTIONAL: API atau route lain =====
// Route::middleware('api')->prefix('api')->group(function () {
//     // API routes jika diperlukan
// });