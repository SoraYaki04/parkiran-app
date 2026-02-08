<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\TipeKendaraan;
use App\Models\ActivityLog;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('Vehicle Configuration')]
class extends Component {
    use WithPagination;
    
    public $tipeId;
    public $kode_tipe;
    public $nama_tipe;

    public $search = '';
    public $isEdit = false;

    /* ======================
        ACTIVITY LOGGER
    =======================*/
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

    /* ======================
        COMPUTED DATA
    =======================*/
    public function updated($property)
    {
        if (in_array($property, ['search'])) {
            $this->resetPage();
        }
    }

    public function getTipeKendaraanProperty()
    {
        return TipeKendaraan::query()
            ->when($this->search, fn ($q) =>
                $q->where('nama_tipe', 'like', "%{$this->search}%")
                ->orWhere('kode_tipe', 'like', "%{$this->search}%")
            )
            ->orderBy('nama_tipe')
            ->paginate(10); // <- pake paginate bukan get()
    }

    /* ========= CREATE ========= */
    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->dispatch('open-modal');
    }

    /* ========= EDIT ========= */
    public function edit($id)
    {
        $tipe = TipeKendaraan::findOrFail($id);

        $this->tipeId    = $tipe->id;
        $this->kode_tipe = $tipe->kode_tipe;
        $this->nama_tipe = $tipe->nama_tipe;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    /* ========= SAVE ========= */
    public function save()
    {
        $this->validate([
            'kode_tipe' => 'required|max:5|unique:tipe_kendaraan,kode_tipe,' . $this->tipeId,
            'nama_tipe' => 'required',
        ]);

        $tipe = TipeKendaraan::updateOrCreate(
            ['id' => $this->tipeId],
            [
                'kode_tipe' => strtoupper($this->kode_tipe),
                'nama_tipe' => $this->nama_tipe,
            ]
        );

        if ($this->isEdit) {
            // LOG UPDATE
            $this->logActivity(
                'UPDATE_TIPE_KENDARAAN',
                'Update tipe kendaraan',
                "ID {$tipe->id} ({$tipe->kode_tipe} - {$tipe->nama_tipe})"
            );

            $this->dispatch('notify',
                message: 'Tipe kendaraan berhasil diperbarui!',
                type: 'success'
            );
        } else {
            // LOG CREATE
            $this->logActivity(
                'CREATE_TIPE_KENDARAAN',
                'Menambahkan tipe kendaraan baru',
                "ID {$tipe->id} ({$tipe->kode_tipe} - {$tipe->nama_tipe})"
            );

            $this->dispatch('notify',
                message: 'Tipe kendaraan berhasil ditambahkan!',
                type: 'success'
            );
        }

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    /* ========= DELETE ========= */
    public function delete($id)
    {
        $tipe = TipeKendaraan::findOrFail($id);

        // CEK RELASI
        if ($tipe->areaKapasitas()->exists()) {
            $this->dispatch('notify',
                message: 'Tipe kendaraan tidak bisa dihapus karena sudah digunakan di area parkir!',
                type: 'error'
            );
            return;
        }

        // LOG SEBELUM DELETE
        $this->logActivity(
            'DELETE_TIPE_KENDARAAN',
            'Menghapus tipe kendaraan',
            "ID {$tipe->id} ({$tipe->kode_tipe} - {$tipe->nama_tipe})"
        );

        $tipe->delete();

        $this->dispatch('notify',
            message: 'Tipe kendaraan berhasil dihapus!',
            type: 'success'
        );
    }

    private function resetForm()
    {
        $this->reset([
            'tipeId',
            'kode_tipe',
            'nama_tipe',
        ]);
    }
};
?>



<div class="flex-1 flex flex-col h-full overflow-hidden" 
    x-data="{ open:false }" 
    x-on:open-modal.window="open=true"
    x-on:close-modal.window="open=false">

    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-end flex-shrink-0">
        <div>
            <h2 class="text-white text-3xl font-black">Konfigurasi Kendaraan</h2>
            <p class="text-slate-400">
                Atur tipe kendaraan
            </p>
        </div>

        <button wire:click="create"
            class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
            <span class="material-symbols-outlined">add</span>
            Tambah Tipe Kendaraan
        </button>
    </header>

    {{-- FILTER --}}
    <div class="px-8 pt-6 flex-shrink-0">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <input wire:model.live="search"
                class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                placeholder="Cari kode / tipe kendaraan">
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden min-h-[300px]">
            <table class="w-full">
                <thead class="bg-gray-900 sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Kode</th>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Tipe Kendaraan</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->tipeKendaraan as $tipe)
                    @php
                        $isUsed = $tipe->areaKapasitas()->exists(); 
                    @endphp
                    <tr class="hover:bg-surface-hover">
                        <td class="px-6 py-4 text-white font-bold">
                            {{ $tipe->kode_tipe }}
                        </td>
                        <td class="px-6 py-4 text-white">
                            {{ $tipe->nama_tipe }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="edit({{ $tipe->id }})" class="text-primary p-2">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <button
                                wire:click="delete({{ $tipe->id }})"
                                onclick="return confirm('Hapus tipe kendaraan ini?')"
                                @if($isUsed) disabled @endif
                                class="
                                    p-2
                                    {{ $isUsed 
                                        ? 'text-gray-500 cursor-not-allowed' 
                                        : 'text-red-400 hover:text-red-500' 
                                    }}
                                "
                                title="{{ $isUsed ? 'Tipe kendaraan sudah digunakan' : 'Hapus' }}"
                            >
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-10 text-center text-slate-500">
                            Tidak ada tipe kendaraan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $this->tipeKendaraan->links() }}
    </div>

    {{-- MODAL --}}
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Tipe kendaraan' : 'Tambah Tipe Kendaraan' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-3">
                <div>
                    <label class="text-sm text-gray-400">Kode Tipe</label>
                    <input wire:model="kode_tipe"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                        placeholder="Kode (M, C, B)">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Nama Tipe</label>
                    <input wire:model="nama_tipe"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                        placeholder="Nama (Motor, Mobil, Bus)">
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" x-on:click="open=false" class="text-gray-400">
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
</div>

