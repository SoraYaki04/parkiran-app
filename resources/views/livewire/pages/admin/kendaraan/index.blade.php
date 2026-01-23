<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\TipeKendaraan;

new #[Layout('layouts.app')]
#[Title('Vehicle Configuration')]
class extends Component {

    public $tipeId;
    public $kode_tipe;
    public $nama_tipe;

    public $search = '';
    public $isEdit = false;

    public function getTipeKendaraanProperty()
    {
        return TipeKendaraan::query()
            ->when($this->search, fn ($q) =>
                $q->where('nama_tipe', 'like', "%{$this->search}%")
                  ->orWhere('kode_tipe', 'like', "%{$this->search}%")
            )
            ->orderBy('nama_tipe')
            ->get();
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

        TipeKendaraan::updateOrCreate(
            ['id' => $this->tipeId],
            [
                'kode_tipe' => strtoupper($this->kode_tipe),
                'nama_tipe' => $this->nama_tipe,
            ]
        );

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    /* ========= DELETE ========= */
    public function delete($id)
    {
        TipeKendaraan::findOrFail($id)->delete();
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


<div class="flex-1 flex flex-col h-full overflow-hidden" x-data="{ open:false }" x-on:open-modal.window="open=true"
    x-on:close-modal.window="open=false">

    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-end">
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
    <div class="px-8 pt-6">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <input wire:model.live="search"
                class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                placeholder="Cari kode / tipe kendaraan">
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Kode</th>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Tipe Kendaraan</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->tipeKendaraan as $tipe)
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
                            <button wire:click="delete({{ $tipe->id }})"
                                onclick="return confirm('Hapus tipe kendaraan ini?')" class="text-red-400 p-2">
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

    {{-- MODAL --}}
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Tipe kendaraan' : 'Tambah Tipe Kendaraan' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-3">
                <input wire:model="kode_tipe"
                    class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                    placeholder="Code (M, C, B)">

                <input wire:model="nama_tipe"
                    class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                    placeholder="Vehicle Type Name">

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" x-on:click="open=false" class="text-gray-400">
                        Batal
                    </button>
                    <button class="bg-primary px-5 py-2 rounded-lg font-bold text-black">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
