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
    
    // Password confirmation (untuk sensitive actions)
    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
    
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