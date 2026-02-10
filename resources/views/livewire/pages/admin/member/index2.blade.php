<?php

use App\Models\TierMember;
use App\Models\ActivityLog;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')]
#[Title('Tier Member')]
class extends Component {
    use WithPagination;

    public $tierId;
    public $nama;
    public $harga;
    public $periode;
    public $diskon_persen;
    public $status = 'aktif';

    public $search = '';
    public $isEdit = false;

    /* ===============================
        ACTIVITY LOGGER
    =============================== */
    private function logActivity(
        string $action,
        string $description,
        string $target = null,
        string $category = 'MASTER'
    ) {
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'category'    => $category,
            'target'      => $target,
            'description' => $description,
        ]);
    }

    /* ===============================
        DATA
    =============================== */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getTierMembersProperty()
    {
        return TierMember::query()
            ->when($this->search, fn ($q) =>
                $q->where('nama', 'like', "%{$this->search}%")
            )
            ->orderBy('harga')
            ->paginate(10);
    }

    /* ===============================
        ACTIONS
    =============================== */
    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $tier = TierMember::findOrFail($id);

        $this->tierId            = $tier->id;
        $this->nama              = $tier->nama;
        $this->harga             = $tier->harga;
        $this->periode           = $tier->periode;
        $this->diskon_persen     = $tier->diskon_persen;
        $this->status            = $tier->status;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    /* ===============================
        SAVE (CREATE / UPDATE / RESTORE)
    =============================== */
    public function save()
    {
        $this->validate([
            'nama'          => 'required|string|max:255',
            'harga'         => 'required|integer|min:0',
            'periode'       => 'required|in:bulanan,tahunan',
            'diskon_persen' => 'required|integer|min:0|max:100',
            'status'        => 'required|in:aktif,nonaktif',
        ]);

        DB::transaction(function () {

            // 🔥 Cari termasuk yang soft delete
            $tier = TierMember::withTrashed()
                ->where('nama', $this->nama)
                ->first();

            /* ===============================
                RESTORE
            =============================== */
            if ($tier && $tier->trashed()) {

                $tier->restore();
                $tier->update([
                    'harga'             => $this->harga,
                    'periode'           => $this->periode,
                    'diskon_persen'     => $this->diskon_persen,
                    'status'            => $this->status,
                ]);

                $this->logActivity(
                    'RESTORE_TIER_MEMBER',
                    'Memulihkan tier member yang sebelumnya dihapus',
                    "ID {$tier->id} ({$tier->nama})"
                );

                $this->dispatch('notify',
                    message: 'Tier member dipulihkan dari data lama',
                    type: 'success'
                );

                return;
            }

            /* ===============================
                UPDATE
            =============================== */
            if ($this->isEdit) {

                $tier = TierMember::findOrFail($this->tierId);
                $tier->update([
                    'nama'              => $this->nama,
                    'harga'             => $this->harga,
                    'periode'           => $this->periode,
                    'diskon_persen'     => $this->diskon_persen,
                    'status'            => $this->status,
                ]);

                $this->logActivity(
                    'UPDATE_TIER_MEMBER',
                    'Memperbarui tier member',
                    "ID {$tier->id} ({$tier->nama})"
                );

                $this->dispatch('notify',
                    message: 'Tier member berhasil diperbarui!',
                    type: 'success'
                );

                return;
            }

            /* ===============================
                CREATE BARU
            =============================== */
            $tier = TierMember::create([
                'nama'              => $this->nama,
                'harga'             => $this->harga,
                'periode'           => $this->periode,
                'diskon_persen'     => $this->diskon_persen,
                'status'            => $this->status,
            ]);

            $this->logActivity(
                'CREATE_TIER_MEMBER',
                'Menambahkan tier member baru',
                "ID {$tier->id} ({$tier->nama})"
            );

            $this->dispatch('notify',
                message: 'Tier member berhasil ditambahkan!',
                type: 'success'
            );
        });

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    /* ===============================
        DELETE (SOFT)
    =============================== */
    public function delete($id)
    {


        $tier = TierMember::findOrFail($id);

        if ($tier->members()->exists()) {
            $this->dispatch('notify',
                message: 'Tier member tidak bisa dihapus karena masih digunakan!',
                type: 'error'
            );
            return;
        }

        DB::transaction(function () use ($tier) {


            $tier->update([
                'status' => 'nonaktif',
            ]);


            $tier->delete();


            $this->logActivity(
                'DELETE_TIER_MEMBER',
                'Soft delete tier member (status mati)',
                "ID {$tier->id} ({$tier->nama})"
            );
        });

        $tier->delete();

        $this->logActivity(
            'DELETE_TIER_MEMBER',
            'Soft delete tier member',
            "ID {$tier->id} ({$tier->nama})"
        );

        $this->dispatch('notify',
            message: 'Tier member berhasil dihapus!',
            type: 'success'
        );
    }

    private function resetForm()
    {
        $this->reset([
            'tierId',
            'nama',
            'harga',
            'periode',
            'diskon_persen',
            'status',
            'isEdit',
        ]);
    }
};
?>



<div class="flex-1 flex flex-col h-full overflow-hidden"
     x-data="{ open: false }"
     x-on:open-modal.window="open = true"
     x-on:close-modal.window="open = false">

     
    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-end">
        <div>
            <h2 class="text-white text-3xl font-black">Tier Member</h2>
            <p class="text-slate-400">Managemen Tier</p>
        </div>

        @if(auth()->user()->role_id == 1)
        <button wire:click="create"
                class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
            <span class="material-symbols-outlined">add</span>
            Tambah Tier
        </button>
        @endif
    </header>

    {{-- FILTER --}}
    <div class="px-8 pt-6">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <input wire:model.live="search"
                   class="w-full md:w-1/3 bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                   placeholder="Cari nama tier">
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Tier</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Harga</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Diskon</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Periode</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Status</th>
                        @if(auth()->user()->role_id == 1)
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->tierMembers as $tier)
                    @php
                        $isUsed = $tier->members()->exists(); 
                    @endphp                    
                        <tr class="hover:bg-surface-hover">
                            <td class="px-6 py-4 text-white font-semibold">
                                {{ $tier->nama }}
                            </td>

                            <td class="px-6 py-4 text-center text-white">
                                Rp {{ number_format($tier->harga, 0, ',', '.') }}
                            </td>

                            <td class="px-6 py-4 text-center text-white">
                                {{ $tier->diskon_persen }}%
                            </td>

                            <td class="px-6 py-4 text-center text-slate-300">
                                {{ ucfirst($tier->periode) }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 text-xs rounded-full
                                    {{ $tier->status === 'aktif'
                                        ? 'bg-green-500/10 text-green-400'
                                        : 'bg-red-500/10 text-red-400' }}">
                                    {{ ucfirst($tier->status) }}
                                </span>
                            </td>

                            @if(auth()->user()->role_id == 1)
                            <td class="px-6 py-4 text-center">
                                <button wire:click="edit({{ $tier->id }})"
                                        class="text-primary p-2">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>

                                <button
                                    wire:click="delete({{ $tier->id }})"
                                    wire:confirm="Hapus tier ini?"
                                    @if($isUsed) disabled @endif
                                    class="
                                        p-2
                                        {{ $isUsed 
                                            ? 'text-gray-500 cursor-not-allowed' 
                                            : 'text-red-400 hover:text-red-500' 
                                        }}"
                                    title="{{ $isUsed ? 'Tier ini masih digunakan oleh member' : 'Hapus' }}"
                                >
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-500">
                                Tidak Ada Data Tier
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 px-8">
            {{ $this->tierMembers->links() }}
        </div>
    </div>
    @if(auth()->user()->role_id == 1)
    {{-- MODAL --}}
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Tier' : 'Tambah Tier' }}
            </h3>

            <form wire:submit.prevent="save" wire:confirm="Apakah anda yakin?" class="space-y-3">
                <div>
                    <label class="text-sm text-gray-400">Nama Tier</label>
                    <input wire:model="nama"
                           class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                           placeholder="Nama Tier (Silver, Gold...)">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Harga (Rupiah)</label>
                    <input wire:model="harga" type="number"
                           class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                           placeholder="Harga Member">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Periode</label>
                    <select wire:model="periode"
                            class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                            <option value="">Select Periode</option>
                            <option value="bulanan">Bulanan</option>
                            <option value="tahunan">Tahunan</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-400">Diskon (%)</label>
                    <input wire:model="diskon_persen" type="number"
                           class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                           placeholder="Diskon Member">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Status</label>
                    <select wire:model="status"
                            class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button"
                            x-on:click="open=false"
                            class="text-gray-400">
                        Batal
                    </button>

                    <button
                    
                        wire:loading.attr="disabled"
                        class="bg-primary px-4 py-2 rounded-lg font-bold text-black disabled:opacity-60">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

