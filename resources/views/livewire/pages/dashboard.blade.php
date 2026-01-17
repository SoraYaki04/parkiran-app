<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Dashboard')] class extends Component {
    // Anda bisa tambahkan properties dan methods di sini
    // Contoh: public $stats = [];
    
    // Mount method jika perlu
    public function mount(): void
    {
        // Inisialisasi data
    }
    
    // Contoh method
    public function refreshStats(): void
    {
        // Refresh data
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard Sistem Parkir') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Kendaraan Masuk -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <span class="material-symbols-outlined text-white">directions_car</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Kendaraan Masuk
                                    </dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                        24
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kendaraan Keluar -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <span class="material-symbols-outlined text-white">exit_to_app</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Kendaraan Keluar
                                    </dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                        18
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pendapatan -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <span class="material-symbols-outlined text-white">payments</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Pendapatan Hari Ini
                                    </dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                        Rp 450.000
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Info & Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- User Info Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                            Informasi Akun
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-gray-400 mr-3">person</span>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nama</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-gray-400 mr-3">badge</span>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Username</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ auth()->user()->username }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-gray-400 mr-3">admin_panel_settings</span>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Role</p>
                                    <p class="font-medium">
                                        @if(auth()->user()->role_id == 1)
                                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Admin</span>
                                        @elseif(auth()->user()->role_id == 2)
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Petugas</span>
                                        @else
                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Owner</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-gray-400 mr-3">check_circle</span>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                                    <p class="font-medium">
                                        @if(auth()->user()->status == 'aktif')
                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Aktif</span>
                                        @else
                                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Tidak Aktif</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                            Aksi Cepat
                        </h3>
                        
                        <div class="space-y-3">
                            @if(auth()->user()->role_id == 1)
                                <!-- Admin Actions -->
                                <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <span class="material-symbols-outlined text-blue-500 mr-3">group</span>
                                    <span class="font-medium">Kelola Petugas</span>
                                </a>
                                <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <span class="material-symbols-outlined text-green-500 mr-3">summarize</span>
                                    <span class="font-medium">Laporan Harian</span>
                                </a>
                                <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <span class="material-symbols-outlined text-purple-500 mr-3">settings</span>
                                    <span class="font-medium">Pengaturan Sistem</span>
                                </a>
                            @else
                                <!-- Petugas/Owner Actions -->
                                <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <span class="material-symbols-outlined text-blue-500 mr-3">add_circle</span>
                                    <span class="font-medium">Tambah Kendaraan Masuk</span>
                                </a>
                                <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <span class="material-symbols-outlined text-green-500 mr-3">logout</span>
                                    <span class="font-medium">Proses Kendaraan Keluar</span>
                                </a>
                                <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <span class="material-symbols-outlined text-yellow-500 mr-3">history</span>
                                    <span class="font-medium">Riwayat Parkir</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>