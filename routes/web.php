<?php

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

// ===== 2. GUEST ROUTES (Untuk user belum login) =====
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

// ===== 3. AUTHENTICATED ROUTES (Untuk user sudah login) =====
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

        // ? Manajemen Area
        Volt::route('area', 'pages.admin.area.index')
            ->name('area.index');
        // Volt::route('area/create', 'pages.admin.area.create')
        //     ->name('area.create');
        // Volt::route('area/{area}/edit', 'pages.admin.area.edit')
        //     ->name('area.edit');

        // ? Manajemen Kendaraan
        Volt::route('kendaraan', 'pages.admin.kendaraan.index')
            ->name('kendaraan.index');
        // Volt::route('kendaraan/create', 'pages.admin.kendaraan.create')
        //     ->name('area.create');
        // Volt::route('kendaraan/{kendaraan}/edit', 'pages.admin.kendaraan.edit')
        //     ->name('kendaraan.edit');

        // ? Manajemen Tarif
        Volt::route('tarif', 'pages.admin.tarif.index')
            ->name('tarif.index');
        // Volt::route('tarif/create', 'pages.admin.tarif.create')
        //     ->name('area.create');
        // Volt::route('tarif/{tarif}/edit', 'pages.admin.tarif.edit')
        //     ->name('tarif.edit');
        
        //  ? Manajemen User
        Volt::route('users', 'pages.admin.users.index')
            ->name('users.index');
        
        // ? Laporan dan Statistik
        Volt::route('reports', 'pages.admin.reports')
            ->name('reports');
        
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