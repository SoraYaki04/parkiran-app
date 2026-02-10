<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use App\Models\ActivityLog;

new class extends Component
{
    public function logout()
    {
        $user = auth()->user();

        if ($user) {
            ActivityLog::log(
                action: 'LOGOUT',
                description: "User {$user->username} berhasil logout",
                target: $user->username,
                category: 'AUTH',
            );
        }

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
};
?>

<aside class="w-64 bg-sidebar-dark border-r border-gray-800 flex flex-col shrink-0">

    {{-- LOGO --}}
    <div class="p-6 flex items-center gap-3">
        <div class="size-10 rounded-xl bg-gradient-to-br from-primary to-yellow-600
                    flex items-center justify-center text-sidebar-dark shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-[24px]">local_parking</span>
        </div>
        <div class="flex flex-col">
            <h1 class="text-white text-lg font-bold leading-tight">
                Parkiran
            </h1>
            <p class="text-primary text-xs font-medium">
                @if(auth()->user()->role_id == 1)
                    Admin Panel
                @elseif(auth()->user()->role_id == 2)
                    Petugas Panel
                @else
                    Owner Panel
                @endif
            </p>
        </div>
    </div>

    {{-- NAV --}}
    <nav
        x-data
        x-ref="nav"
        @scroll.debounce.100ms="sessionStorage.setItem('sidebarScroll', $refs.nav.scrollTop)"
        x-init="if (sessionStorage.getItem('sidebarScroll')) { $refs.nav.scrollTop = sessionStorage.getItem('sidebarScroll') }"
        class="flex-1 px-4 py-4 flex flex-col gap-2 overflow-y-auto scrollbar-hide"
    >

        {{-- DASHBOARD (SEMUA ROLE) --}}
        <a href="{{ route('dashboard') }}" wire:navigate.preserve-scroll
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('dashboard')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined {{ request()->routeIs('dashboard') ? 'text-primary' : '' }}">
                dashboard
            </span>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        {{-- TRANSAKSI (ADMIN & PETUGAS) --}}
        @if(in_array(auth()->user()->role_id, [2]))
            <a href="{{ route('exit') }}" wire:navigate.preserve-scroll
               class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
               {{ request()->routeIs('exit')
                    ? 'bg-primary/10 border-l-4 border-primary text-white'
                    : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <span class="material-symbols-outlined">garage_check</span>
                Transaksi
            </a>
            
            <a href="{{ route('data_parkir') }}" wire:navigate.preserve-scroll
               class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
               {{ request()->routeIs('data_parkir')
                    ? 'bg-primary/10 border-l-4 border-primary text-white'
                    : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <span class="material-symbols-outlined">check_in_out</span>
                Data Parkir
            </a>

        @endif

        {{-- ================= DATA MASTER (ADMIN & PETUGAS) ================= --}}
        @if(in_array(auth()->user()->role_id, [1,2]))

            <div class="pt-4 mt-4 border-t border-gray-800">
            <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Data Master</p>

                <a href="{{ route('admin.kendaraan') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.kendaraan')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">directions_car</span>
                    Tipe Kendaraan
                </a>

                <a href="{{ route('admin.tarif') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.tarif')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">attach_money</span>
                    Tarif
                </a>

                <a href="{{ route('admin.area') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.area')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">local_parking</span>
                    Area Parkir
                </a>
            </div>

            <div class="pt-4 mt-4 border-t border-gray-800">

                <a href="{{ route('admin.tier_member') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.tier_member')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">badge</span>
                    Tier Member
                </a>

                <a href="{{ route('admin.member') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.member')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">group</span>
                    Member
                </a>

        @endif

        {{-- ================= ADMIN ONLY ================= --}}
        @if(auth()->user()->role_id == 1)

                <a href="{{ route('admin.users') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.users')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">manage_accounts</span>
                    Manajemen User
                </a>

                <div class="pt-4 mt-4 border-t border-gray-800">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Report</p>

                {{-- LAPORAN (ADMIN SAJA DI SINI) --}}
                <a href="{{ route('laporan') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('laporan')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">assessment</span>
                    Laporan & Analytics
                </a>

                
                <div class="pt-4 mt-4 border-t border-gray-800">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">System</p>

                <a href="{{ route('admin.log_aktivitas') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.log_aktivitas')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">search_activity</span>
                    Log Aktivitas
                </a>

                <a href="{{ route('admin.backup') }}" wire:navigate.preserve-scroll
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   {{ request()->routeIs('admin.backup')
                        ? 'bg-primary/10 border-l-4 border-primary text-white'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <span class="material-symbols-outlined">backup</span>
                    Backup Database
                </a>
            </div>
        @endif

        {{-- LAPORAN (OWNER ONLY) --}}
        @if(auth()->user()->role_id == 3)
            <a href="{{ route('laporan') }}" wire:navigate.preserve-scroll
               class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
               {{ request()->routeIs('laporan')
                    ? 'bg-primary/10 border-l-4 border-primary text-white'
                    : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <span class="material-symbols-outlined">assessment</span>
                Laporan
            </a>
        @endif

    </nav>

    {{-- USER PROFILE --}}
    <div class="px-4 pt-4 pb-2 border-t border-gray-800">
        <div class="flex items-center gap-3 px-2">
            {{-- Avatar Initials --}}
            <div class="size-10 rounded-full bg-gradient-to-br from-primary to-yellow-600
                        flex items-center justify-center text-sm font-black text-black shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->name, strpos(auth()->user()->name, ' ') + 1, 1)) }}
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-white text-sm font-bold truncate">{{ auth()->user()->name }}</span>
                @php
                    $roleLabel = match(auth()->user()->role_id) {
                        1 => 'Admin',
                        2 => 'Petugas',
                        3 => 'Owner',
                        default => 'User',
                    };
                @endphp
                <span class="text-xs text-primary font-medium">{{ $roleLabel }}</span>
            </div>
        </div>
    </div>

    {{-- LOGOUT --}}
    <div class="px-4 pb-4 pt-2">
        <div x-data="{ loading: false }">
            <button
                @click="
                    if (confirm('Yakin ingin logout?')) {
                        loading = true;
                        $wire.logout();
                    }
                "
                :disabled="loading"
                class="w-full flex items-center justify-center gap-2 rounded-lg h-10
                    bg-gray-800 text-gray-300 hover:text-white hover:bg-gray-700
                    text-sm font-medium transition-colors disabled:opacity-60">

                <!-- ICON -->
                <span x-show="!loading" class="material-symbols-outlined text-[18px]">
                    logout
                </span>

                <!-- TEXT NORMAL -->
                <span x-show="!loading">
                    Sign Out
                </span>

                <!-- LOADING -->
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"/>
                    </svg>
                    Signing out...
                </span>

            </button>
        </div>
    </div>


</aside>
