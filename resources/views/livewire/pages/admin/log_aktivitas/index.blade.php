<?php

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('System Activity Log')]
class extends Component {
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $filterAction = '';
    public $filterUser = ''; // Ini akan menyimpan ID user

    public function updated($property)
    {
        if (in_array($property, ['search', 'filterCategory', 'filterAction', 'filterUser'])) {
            $this->resetPage();
        }
    }

    public function roleBadge($roleId): array
    {
        return match ((int) $roleId) {
            1 => [
                'label' => 'Admin',
                'class' => 'bg-purple-500/10 text-purple-400 border-purple-500/20'
            ],
            2 => [
                'label' => 'Petugas',
                'class' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
            ],
            3 => [
                'label' => 'Owner',
                'class' => 'bg-blue-500/10 text-blue-400 border-blue-500/20'
            ],
            default => [
                'label' => 'System',
                'class' => 'bg-slate-500/10 text-slate-400 border-slate-500/20'
            ],
        };
    }


    public function getLogsProperty()
    {
        return ActivityLog::with([
                'user:id,name,username,role_id,status'
            ])
            ->when($this->search, fn($q) =>
                $q->where(fn($sub) =>
                    $sub->where('description', 'like', "%{$this->search}%")
                        ->orWhere('target', 'like', "%{$this->search}%")
                        ->orWhere('action', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterCategory, fn($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterAction, fn($q) => $q->where('action', $this->filterAction))
            ->when($this->filterUser, fn($q) => $q->where('user_id', $this->filterUser))
            ->latest()
            ->paginate(10);
    }


    public function getUsersProperty()
    {
        // Mengambil name dan username untuk filter
        return User::select('id', 'name', 'username')->orderBy('name')->get();
    }
};
?>

<div class="flex-1 flex flex-col h-full">
    <div class="max-w-[1280px] mx-auto p-8 flex flex-col h-full">

        {{-- HEADER --}}
        <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
            <div>
                <h2 class="text-white text-3xl font-black">Log Aktivitas Sistem</h2>
                <p class="text-slate-400">Monitor dan lacak seluruh aktivitas sistem</p>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59] mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input wire:model.live.debounce.300ms="search"
                    class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg pl-4 py-2 text-white text-sm focus:border-primary outline-none transition-all"
                    placeholder="Cari deskripsi / target...">

                <select wire:model.live="filterCategory"
                    class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white text-sm outline-none focus:border-primary">
                    <option value="">Semua Kategori</option>
                    <option value="AUTH">AUTH</option>
                    <option value="TRANSAKSI">TRANSAKSI</option>
                    <option value="MASTER">MASTER</option>
                </select>

                <select wire:model.live="filterAction"
                    class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white text-sm outline-none focus:border-primary scrollbar-hide">
                    <option value="">Semua Aksi</option>
                    <optgroup label="Sistem & Keamanan" class="bg-surface-dark text-primary font-bold">
                        <option value="LOGIN_SUCCESS">LOGIN_SUCCESS</option>
                        <option value="LOGIN_FAILED">LOGIN_FAILED</option>
                        <option value="LOCKOUT">LOCKOUT</option>
                        <option value="LOGOUT">LOGOUT</option>
                        <option value="CREATE_USER">CREATE_USER</option>
                        <option value="UPDATE_USER">UPDATE_USER</option>
                        <option value="DELETE_USER">DELETE_USER</option>
                    </optgroup>
                    <optgroup label="Manajemen Member" class="bg-surface-dark text-primary font-bold">
                        <option value="CREATE_MEMBER">CREATE_MEMBER</option>
                        <option value="UPDATE_MEMBER">UPDATE_MEMBER</option>
                        <option value="DELETE_MEMBER">DELETE_MEMBER</option>
                        <option value="CREATE_TIER_MEMBER">CREATE_TIER_MEMBER</option>
                        <option value="UPDATE_TIER_MEMBER">UPDATE_TIER_MEMBER</option>
                        <option value="DELETE_TIER_MEMBER">DELETE_TIER_MEMBER</option>
                    </optgroup>
                    <optgroup label="Konfigurasi Parkir" class="bg-surface-dark text-primary font-bold">
                        <option value="CREATE_TIPE_KENDARAAN">CREATE_TIPE_KENDARAAN</option>
                        <option value="UPDATE_TIPE_KENDARAAN">UPDATE_TIPE_KENDARAAN</option>
                        <option value="DELETE_TIPE_KENDARAAN">DELETE_TIPE_KENDARAAN</option>
                        <option value="CREATE_TARIF">CREATE_TARIF</option>
                        <option value="UPDATE_TARIF">UPDATE_TARIF</option>
                        <option value="DELETE_TARIF">DELETE_TARIF</option>
                        <option value="CREATE_AREA">CREATE_AREA</option>
                        <option value="UPDATE_AREA">UPDATE_AREA</option>
                        <option value="DELETE_AREA">DELETE_AREA</option>
                    </optgroup>
                    <optgroup label="Operasional & Laporan" class="bg-surface-dark text-primary font-bold">
                        <option value="EXIT_PARKIR">EXIT_PARKIR</option>
                        <option value="CETAK_STRUK">CETAK_STRUK</option>
                        <option value="EXPORT">EXPORT</option>
                    </optgroup>
                </select>

                <select wire:model.live="filterUser"
                    class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white text-sm outline-none focus:border-primary">
                    <option value="">Semua User</option>
                    @foreach($this->users as $user)
                    <option value="{{ $user->id }}">{{ $user->username }} ({{ $user->name }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="flex-1 overflow-y-auto scrollbar-hide">
            <div class="bg-surface-dark border border-[#3E4C59] rounded-xl shadow-2xl min-h-[300px]">
                <table class="w-full">
                    <thead class="bg-gray-900 sticky top-0 z-10">
                        <tr>
                            <th
                                class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-500 border-b border-[#3E4C59]">
                                Waktu</th>
                            <th
                                class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-500 border-b border-[#3E4C59]">
                                User & Akun</th>
                            <th
                                class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-500 border-b border-[#3E4C59]">
                                Action</th>
                            <th
                                class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-500 border-b border-[#3E4C59]">
                                Target</th>
                            <th
                                class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-500 border-b border-[#3E4C59]">
                                Deskripsi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#3E4C59]">
                        @forelse($this->logs as $log)
                        @php
                        $role = $this->roleBadge($log->user?->role_id);
                        $isActive = ($log->user?->status === 'aktif');
                        $actionColor = match(true) {
                        str_contains($log->action, 'CREATE') => 'bg-emerald-500 text-emerald-950',
                        str_contains($log->action, 'DELETE') => 'bg-red-500 text-red-950',
                        str_contains($log->action, 'UPDATE') => 'bg-amber-400 text-amber-950',
                        str_contains($log->action, 'LOGIN') => 'bg-blue-400 text-blue-950',
                        default => 'bg-primary text-black',
                        };
                        @endphp
                        <tr class="hover:bg-surface-hover transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-white text-sm font-bold">{{ $log->created_at->format('d M Y') }}</p>
                                <p class="text-slate-500 text-[11px] font-mono tracking-tighter">
                                    {{ $log->created_at->format('H:i:s') }}</p>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div
                                            class="w-10 h-10 rounded-xl flex items-center justify-center border {{ $role['class'] }} shadow-lg">
                                            <span
                                                class="font-black text-sm uppercase">{{ substr($log->user?->name ?? 'S', 0, 1) }}</span>
                                        </div>
                                        <div
                                            class="absolute -bottom-1 -right-1 w-3.5 h-3.5 border-2 border-surface-dark rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-red-500' }}">
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-white text-sm font-bold italic leading-none">
                                                {{ $log->user?->name ?? 'SYSTEM' }}</p>
                                            <span
                                                class="text-slate-500 text-[11px] font-mono lowercase tracking-tighter">@
                                                {{ $log->user?->username ?? 'system' }}</span>
                                        </div>
                                        <p class="text-[9px] uppercase tracking-[0.2em] font-black mt-1 opacity-40">
                                            {{ $role['label'] }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tighter {{ $actionColor }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="text-[11px] text-slate-300 font-mono bg-gray-900/50 px-2 py-1 rounded border border-[#3E4C59]">
                                    {{ $log->target ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 max-w-xs">
                                <p
                                    class="text-xs text-slate-400 leading-relaxed group-hover:text-slate-200 transition-colors">
                                    {{ $log->description }}
                                </p>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-24 text-center">
                                <div class="flex flex-col items-center opacity-20">
                                    <span class="material-symbols-outlined text-6xl">history</span>
                                    <p class="mt-2 font-black uppercase tracking-widest text-xs">Data log kosong</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->logs->links() }}
        </div>

    </div>
</div>
