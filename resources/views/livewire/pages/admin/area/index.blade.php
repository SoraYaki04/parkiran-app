<?php

use App\Models\AreaParkir;
use App\Models\SlotParkir;
use App\Models\TipeKendaraan;
use App\Models\AreaKapasitas;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;


new #[Layout('layouts.app')]
#[Title('Parking Area Management')]
class extends Component {

    public $areaId;
    public $kode_area;
    public $nama_area;
    public $lokasi_fisik;
    public $kapasitas_total;
    public $selectedTipe = [];
    public $kapasitas = [];


    public $search = '';
    public $isEdit = false;

    /* ===============================
        DATA AREA PARKIR
    =============================== */

    public function getTipeKendaraanProperty()
    {
        return TipeKendaraan::orderBy('nama_tipe')->get();
    }

    public function getAreasProperty()
    {
        return AreaParkir::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('kode_area', 'like', "%{$this->search}%")
                        ->orWhere('nama_area', 'like', "%{$this->search}%")
                        ->orWhere('lokasi_fisik', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }


    /* ===============================
        STATUS AREA
    =============================== */
    public function getStatus($areaId)
    {
        $total = SlotParkir::where('area_id', $areaId)->count();
        $terisi = SlotParkir::where('area_id', $areaId)
                    ->where('status', 'terisi')
                    ->count();

        if ($total === 0) return 'Maintenance';
        if ($terisi >= $total) return 'Full';
        return 'Available';
    }

    /* ===============================
        CREATE
    =============================== */
    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->dispatch('open-modal');
    }

    /* ===============================
        EDIT
    =============================== */
    public function edit($id)
    {
        // 🔹 INI BARISNYA DITARUH DI SINI
        $area = AreaParkir::with('kapasitas')->findOrFail($id);

        $this->areaId = $area->id;
        $this->kode_area = $area->kode_area;
        $this->nama_area = $area->nama_area;
        $this->lokasi_fisik = $area->lokasi_fisik;
        $this->kapasitas_total = $area->kapasitas_total;

        // reset dulu
        $this->selectedTipe = [];
        $this->kapasitas = [];

        // 🔹 pakai relasi yang BENAR
        foreach ($area->kapasitas ?? [] as $item) {
            $this->selectedTipe[] = $item->tipe_kendaraan_id;
            $this->kapasitas[$item->tipe_kendaraan_id] = $item->kapasitas;
        }

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }



    /* ===============================
        SAVE
    =============================== */
    public function save()
    {
        $this->validate([
            'kode_area'    => 'required|unique:area_parkir,kode_area,' . $this->areaId,
            'nama_area'    => 'required',
            'lokasi_fisik' => 'required',
            'selectedTipe' => 'required|array|min:1',
        ]);


        if ($this->isEdit) {
            $terisi = SlotParkir::where('area_id', $this->areaId)
                ->where('status', 'terisi')
                ->exists();

            if ($terisi) {
                session()->flash('error', 'Area masih memiliki kendaraan terparkir');
                return;
            }
        }



        foreach ($this->selectedTipe as $tipeId) {
            if (empty($this->kapasitas[$tipeId]) || $this->kapasitas[$tipeId] <= 0) {
                $this->addError('kapasitas.' . $tipeId, 'Jumlah slot wajib diisi');
                return;
            }
        }


        DB::transaction(function () {

            // 1️⃣ Hitung kapasitas total
            $this->kapasitas_total = array_sum($this->kapasitas);

            // 2️⃣ Simpan area
            $area = AreaParkir::updateOrCreate(
                ['id' => $this->areaId],
                [
                    'kode_area'       => $this->kode_area,
                    'nama_area'       => $this->nama_area,
                    'lokasi_fisik'    => $this->lokasi_fisik,
                    'kapasitas_total' => $this->kapasitas_total,
                ]
            );

            // jika edit → reset slot lama
            SlotParkir::where('area_id', $area->id)->delete();
            AreaKapasitas::where('area_id', $area->id)->delete();

            // 3️⃣ Simpan kapasitas + generate slot
            foreach ($this->selectedTipe as $tipeId) {

                $jumlah = $this->kapasitas[$tipeId] ?? 0;
                if ($jumlah <= 0) continue;

                AreaKapasitas::create([
                    'area_id' => $area->id,
                    'tipe_kendaraan_id' => $tipeId,
                    'kapasitas' => $jumlah
                ]);

                $tipe = TipeKendaraan::find($tipeId);

                for ($i = 1; $i <= $jumlah; $i++) {
                    SlotParkir::create([
                        'area_id' => $area->id,
                        'kode_slot' => $tipe->kode_tipe . $i,
                        'baris' => $tipe->kode_tipe,
                        'kolom' => $i,
                        'tipe_kendaraan_id' => $tipeId,
                        'status' => 'kosong'
                    ]);
                }
            }
        });

        $this->resetForm();
        $this->dispatch('close-modal');
    }


    /* ===============================
        DELETE
    =============================== */
    public function delete($id)
    {
        AreaParkir::findOrFail($id)->delete();
    }

    private function resetForm()
    {
        $this->reset([
            'areaId',
            'kode_area',
            'nama_area',
            'lokasi_fisik',
            'kapasitas_total',
            'selectedTipe',
            'kapasitas',
        ]);
    }

    
};
?>

<div x-data="{ open: false }" x-on:open-modal.window="open = true" x-on:close-modal.window="open = false" class="p-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">Managemen Area Parkiran</h1>

        <button wire:click="create" class="bg-primary px-4 py-2 rounded-lg font-bold text-black hover:opacity-90">
            + Tambah Area
        </button>
    </div>

    <!-- SEARCH -->
    <div class="mb-4">
        <input wire:model.live="search" placeholder="Cari area..."
            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none">
    </div>

    <!-- TABLE -->
    <div class="overflow-hidden rounded-xl border border-gray-800 bg-header-dark shadow-xl">
        <table class="w-full text-left text-sm text-gray-400">
            <thead class="bg-gray-800/50 text-xs uppercase font-semibold text-gray-300 tracking-wider">
                <tr>
                    <th class="px-6 py-4">Kode Area</th>
                    <th class="px-6 py-4">Nama Area</th>
                    <th class="px-6 py-4">Lokasi Fisik</th>
                    <th class="px-6 py-4 text-center">Kapasitas</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-800 bg-[#111827]">

                @forelse ($this->areas as $area)
                <tr class="hover:bg-gray-800/60 transition">
                    <td class="px-6 py-4 font-semibold text-white">
                        {{ $area->kode_area }}
                    </td>

                    <td class="px-6 py-4 text-gray-300">
                        {{ $area->nama_area }}
                    </td>

                    <td class="px-6 py-4 text-gray-400">
                        {{ $area->lokasi_fisik }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        <span class="font-bold text-gray-200">
                            {{ $area->kapasitas_total ?? 0 }}
                        </span>
                        <span class="text-xs text-gray-500 ml-1">Slot</span>
                    </td>

                    <!-- STATUS -->
                    <td class="px-6 py-4 text-center">
                       @php $status = $area->status; @endphp

                        @if ($status === 'Full')
                        <span
                            class="inline-flex items-center rounded-full bg-red-500/10 border border-red-500/20 px-2.5 py-0.5 text-xs font-bold text-red-400">
                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            Full
                        </span>
                        @elseif ($status === 'Available')
                        <span
                            class="inline-flex items-center rounded-full bg-green-500/10 border border-green-500/20 px-2.5 py-0.5 text-xs font-bold text-green-400">
                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            Available
                        </span>
                        @else
                        <span
                            class="inline-flex items-center rounded-full bg-yellow-500/10 border border-yellow-500/20 px-2.5 py-0.5 text-xs font-bold text-yellow-400">
                            Maintenance
                        </span>
                        @endif
                    </td>

                    <!-- ACTION -->
                    <td class="px-6 py-4 text-center ">
                        <div class="flex justify-center gap-1">

                            <!-- EDIT -->
                            <button wire:click="edit({{ $area->id }})"
                                class="rounded-lg p-2 text-gray-400 hover:bg-yellow-500/10 hover:text-primary"
                                title="Edit">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>

                            <!-- DELETE -->
                            <button wire:click="delete({{ $area->id }})" wire:confirm="Yakin ingin menghapus area ini?"
                                class="rounded-lg p-2 text-gray-400 hover:bg-red-500/10 hover:text-red-400"
                                title="Delete">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-6 text-center text-gray-500">
                        Data area parkir belum tersedia
                    </td>
                </tr>
                @endforelse

            </tbody>
        </table>
    </div>

    {{-- <div class="mt-4 flex items-center justify-between">
        <p class="text-sm text-gray-400">Showing <span class="font-medium text-white">1</span> to <span
                class="font-medium text-white">5</span> of <span class="font-medium text-white">12</span> results</p>
        <div class="flex items-center gap-2">
            <button
                class="rounded-lg border border-gray-700 p-2 text-gray-400 hover:bg-gray-800 hover:text-white disabled:opacity-50">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>
            <button class="rounded-lg border border-gray-700 p-2 text-gray-400 hover:bg-gray-800 hover:text-white">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>
        </div>
    </div> --}}

    <!-- MODAL -->
    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">

        <div @click.away="open=false" class="bg-gray-900 rounded-xl w-full max-w-lg p-6 border border-gray-700">

            <h2 class="text-xl font-bold text-white mb-4">
                {{ $isEdit ? 'Edit Area' : 'Add Area' }}
            </h2>

            <form wire:submit.prevent="save" class="space-y-4">

                <div>
                    <label class="text-sm text-gray-400">Kode Area</label>
                    <input wire:model="kode_area"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Nama Area</label>
                    <input wire:model="nama_area"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Lokasi Fisik</label>
                    <input wire:model="lokasi_fisik"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white">
                </div>

                <hr class="border-gray-700">
                <h3 class="text-sm font-semibold text-gray-300">Tipe Kendaraan</h3>

                @foreach ($this->tipeKendaraan ?? [] as $tipe)
                <div class="space-y-1">

                    <label class="flex items-center gap-2 text-gray-300">
                        <input type="checkbox"
                            wire:model="selectedTipe"
                            value="{{ $tipe->id }}"
                            class="rounded border-gray-600 bg-gray-800">
                        {{ $tipe->nama_tipe }}
                    </label>

                    @if (in_array($tipe->id, $selectedTipe))
                        <input type="number"
                            min="1"
                            wire:model.defer="kapasitas.{{ $tipe->id }}"
                            placeholder="Jumlah slot {{ $tipe->nama_tipe }}"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white">
                    @endif

                </div>
                @endforeach

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="open=false" class="text-gray-400 hover:text-white">
                        Cancel
                    </button>
                    <button class="bg-primary px-4 py-2 rounded-lg font-bold text-black">
                        Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
