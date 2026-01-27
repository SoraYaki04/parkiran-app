<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\TarifParkir;
use App\Models\TipeKendaraan;

new #[Layout('layouts.app')]
#[Title('Tariff Configuration')]
class extends Component {

    public $tarifId;
    public $tipe_kendaraan_id;
    public $durasi_min;
    public $durasi_max;
    public $tarif;

    public $filterTipe = '';
    public $isEdit = false;

    /* ========= DATA ========= */
    public function getTarifsProperty()
    {
        return TarifParkir::with('tipeKendaraan')
            ->when($this->filterTipe, function ($q) {
                $q->where('tipe_kendaraan_id', $this->filterTipe);
            })
            ->orderBy('tipe_kendaraan_id')
            ->orderBy('durasi_min')
            ->get();
    }

    
    public function getTipeKendaraanProperty()
    {
        return TipeKendaraan::orderBy('nama_tipe')->get();
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
        $tarif = TarifParkir::findOrFail($id);

        $this->tarifId = $tarif->id;
        $this->tipe_kendaraan_id = $tarif->tipe_kendaraan_id;
        $this->durasi_min = $tarif->durasi_min;
        $this->durasi_max = $tarif->durasi_max;
        $this->tarif = $tarif->tarif;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    /* ========= SAVE ========= */
    public function save()
    {
        $this->validate([
            'tipe_kendaraan_id' => 'required',
            'durasi_min' => 'required|integer|min:0',
            'durasi_max' => 'required|integer|gt:durasi_min',
            'tarif' => 'required|integer|min:0',
        ]);

        TarifParkir::updateOrCreate(
            ['id' => $this->tarifId],
            [
                'tipe_kendaraan_id' => $this->tipe_kendaraan_id,
                'durasi_min' => $this->durasi_min,
                'durasi_max' => $this->durasi_max,
                'tarif' => $this->tarif,
            ]
        );

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    /* ========= DELETE ========= */
    public function delete($id)
    {
        TarifParkir::findOrFail($id)->delete();
    }

    private function resetForm()
    {
        $this->reset([
            'tarifId',
            'tipe_kendaraan_id',
            'durasi_min',
            'durasi_max',
            'tarif',
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
            <h2 class="text-white text-3xl font-black">Tarif Parkir</h2>
            <p class="text-slate-400">Kelola tarif berdasarkan durasi & tipe kendaraan</p>
        </div>

        <button wire:click="create"
                class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
            <span class="material-symbols-outlined">add</span>
            Tambah Tarif
        </button>
    </header>

    {{-- FILTER --}}
    <div class="px-8 pt-6">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <div class="flex flex-col md:flex-row gap-4">

                <select wire:model.live="filterTipe"
                        class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    <option value="">Semua Tipe Kendaraan</option>
                    @foreach($this->tipeKendaraan as $tk)
                        <option value="{{ $tk->id }}">{{ $tk->nama_tipe }}</option>
                    @endforeach
                </select>

            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Tipe Kendaraan</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Durasi (Menit)</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Tarif</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->tarifs as $item)
                        <tr class="hover:bg-surface-hover">
                            <td class="px-6 py-4 text-white">
                                {{ $item->tipeKendaraan->nama_tipe }}
                            </td>
                            <td class="px-6 py-4 text-center text-slate-300">
                                {{ $item->durasi_min }} - {{ $item->durasi_max }} menit
                            </td>
                            <td class="px-6 py-4 text-center text-white">
                                Rp {{ number_format($item->tarif,0,',','.') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="edit({{ $item->id }})"
                                        class="text-primary p-2">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button wire:click="delete({{ $item->id }})"
                                        onclick="return confirm('Hapus tarif ini?')"
                                        class="text-red-400 p-2">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-slate-500">
                                Tidak Data Tarif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL --}}
    <div x-show="open" x-transition
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Tarif Parkir' : 'Tambah Tarif Parkir' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-3">

                <select wire:model="tipe_kendaraan_id"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    <option value="">Pilih Tipe Kendaraan</option>
                    @foreach($this->tipeKendaraan as $tk)
                        <option value="{{ $tk->id }}">{{ $tk->nama_tipe }}</option>
                    @endforeach
                </select>

                <input wire:model="durasi_min" type="number"
                       class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                       placeholder="Durasi Awal (menit)">

                <input wire:model="durasi_max" type="number"
                       class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                       placeholder="Durasi Akhir (menit)">

                <input wire:model="tarif" type="number"
                       class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                       placeholder="Tarif (Rp)">

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button"
                            x-on:click="open=false"
                            class="text-gray-400">
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


