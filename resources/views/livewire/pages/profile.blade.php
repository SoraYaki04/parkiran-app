<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Profile')] class extends Component {
    // Bisa tambahkan form fields untuk edit profile nanti
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-6 md:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 md:p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-6">Informasi Profil</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="material-symbols-outlined align-middle text-gray-400 mr-2">person</span>
                                    Nama Lengkap
                                </label>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-lg font-medium">{{ auth()->user()->name }}</p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="material-symbols-outlined align-middle text-gray-400 mr-2">badge</span>
                                    Username
                                </label>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-lg font-medium">{{ auth()->user()->username }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="material-symbols-outlined align-middle text-gray-400 mr-2">admin_panel_settings</span>
                                    Role
                                </label>
                                <div class="p-3">
                                    @if(auth()->user()->role_id == 1)
                                        <span class="px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-medium">Admin</span>
                                    @elseif(auth()->user()->role_id == 2)
                                        <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">Petugas Parkir</span>
                                    @else
                                        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">Owner</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="material-symbols-outlined align-middle text-gray-400 mr-2">check_circle</span>
                                    Status
                                </label>
                                <div class="p-3">
                                    @if(auth()->user()->status == 'aktif')
                                        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">Aktif</span>
                                    @else
                                        <span class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">Tidak Aktif</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="material-symbols-outlined align-middle text-gray-400 mr-2">calendar_today</span>
                                    Bergabung Sejak
                                </label>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-lg font-medium">{{ auth()->user()->created_at->format('d F Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>