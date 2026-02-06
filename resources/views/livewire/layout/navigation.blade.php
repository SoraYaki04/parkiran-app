<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside class="w-64 bg-sidebar-dark border-r border-gray-800 flex flex-col shrink-0">

    {{-- LOGO --}}
    <div class="p-6 flex items-center gap-3">
        <div class="size-10 rounded-xl bg-gradient-to-br from-primary to-yellow-600
                    flex items-center justify-center text-sidebar-dark shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-[24px]">local_parking</span>
        </div>
        <div class="flex flex-col">
            <h1 class="text-white text-lg font-bold leading-tight">
                Parking Command
            </h1>
            <p class="text-primary text-xs font-medium">
                {{ auth()->user()->role_id == 1 ? 'Admin Console' : 'Petugas Panel' }}
            </p>
        </div>
    </div>

    {{-- NAV --}}
    <nav class="flex-1 px-4 py-4 flex flex-col gap-2 overflow-y-auto scrollbar-hide">

    {{-- DASHBOARD --}}
    <a href="{{ route('dashboard') }}" wire:navigate
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
       {{ request()->routeIs('dashboard')
            ? 'bg-primary/10 border-l-4 border-primary text-white'
            : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
        <span class="material-symbols-outlined
            {{ request()->routeIs('dashboard') ? 'text-primary' : '' }}">
            dashboard
        </span>
        <span class="text-sm font-medium">Dashboard</span>
    </a>

    @if(auth()->user()->role_id == 1)

        <a href="{{ route('admin.exit.index') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.exit.*')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">garage_check</span>
            Transaksi
        </a>       

        {{-- ADMIN MENU --}}
        <div class="pt-4 mt-4 border-t border-gray-800">

        {{-- TIPE KENDARAAN --}}
        <a href="{{ route('admin.kendaraan.index') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.kendaraan.*')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">directions_car</span>
            Tipe Kendaraan
        </a>        

        {{-- TARIF --}}
        <a href="{{ route('admin.tarif.index') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.tarif.*')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">attach_money</span>
            Tarif
        </a>

        {{-- AREA PARKIR --}}
        <a href="{{ route('admin.area.index') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.area.*')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">local_parking</span>
            Area Parkir
        </a>

        <div class="pt-4 mt-4 border-t border-gray-800">

        {{-- TIER MEMBER --}}
        <a href="{{ route('admin.member.index2') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.member.index2')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">badge</span>
            Tier Member
        </a>

        {{-- MEMBER --}}
        <a href="{{ route('admin.member.index1') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.member.index1')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">group</span>
            Member
        </a>

        {{-- USER MANAGEMENT --}}
        <a href="{{ route('admin.users.index') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.users.*')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">manage_accounts</span>
            Manajemen User
        </a>

        {{-- LAPORAN --}}
        <a href="{{ route('admin.laporan.index') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('admin.laporan.*')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">assessment</span>
            Laporan
        </a>

        <div class="pt-4 mt-4 border-t border-gray-800">
        <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">System</p>

        <a href="{{ route('settings') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('settings')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">settings</span>
            Settings
        </a>

    @else
        {{-- PETUGAS MENU --}}

        {{-- <a href="{{ route('petugas.scan') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('petugas.scan')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">qr_code_scanner</span>
            Scan Kendaraan
        </a>

        <a href="{{ route('petugas.out') }}" wire:navigate
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
           {{ request()->routeIs('petugas.out')
                ? 'bg-primary/10 border-l-4 border-primary text-white'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            <span class="material-symbols-outlined">logout</span>
            Kendaraan Keluar
        </a> --}}
    @endif

</nav>


    {{-- LOGOUT --}}
    <div class="p-4 border-t border-gray-800">
        <button wire:click="logout"
                class="w-full flex items-center justify-center gap-2 rounded-lg h-10
                    bg-gray-800 text-gray-300 hover:text-white hover:bg-gray-700
                    text-sm font-medium transition-colors">
            <span class="material-symbols-outlined text-[18px]">logout</span>
            <span>Sign Out</span>
        </button>
    </div>


</aside>

