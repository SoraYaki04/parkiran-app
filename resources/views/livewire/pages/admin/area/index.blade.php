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

<div class="flex-1 flex flex-col h-full overflow-hidden"
     x-data="{ open:false }"
     x-on:open-modal.window="open=true"
     x-on:close-modal.window="open=false">

    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-end">
        <div>
            <h2 class="text-white text-3xl font-black">Manajemen Area Parkir</h2>
            <p class="text-slate-400">
                Atur area parkir
            </p>
        </div>

        <button wire:click="create"
            class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
            <span class="material-symbols-outlined">add</span>
            Tambah Area
        </button>
    </header>


    <!-- SEARCH -->
    <div class="px-8 pt-6">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <input
                wire:model.live="search"
                class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                placeholder="Cari kode / nama / lokasi area">
        </div>
    </div>


    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Kode Area</th>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Nama Area</th>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Lokasi</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Kapasitas</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Status</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">

                    @forelse ($this->areas as $area)
                    <tr class="hover:bg-surface-hover">
                        <td class="px-6 py-4 text-white font-bold">
                            {{ $area->kode_area }}
                        </td>

                        <td class="px-6 py-4 text-white">
                            {{ $area->nama_area }}
                        </td>

                        <td class="px-6 py-4 text-slate-300">
                            {{ $area->lokasi_fisik }}
                        </td>

                        <td class="px-6 py-4 text-center text-white font-bold">
                            {{ $area->kapasitas_total ?? 0 }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            @php $status = $area->status; @endphp

                            @if ($status === 'Full')
                                <span class="text-xs font-bold text-red-400">Full</span>
                            @elseif ($status === 'Available')
                                <span class="text-xs font-bold text-green-400">Available</span>
                            @else
                                <span class="text-xs font-bold text-yellow-400">Maintenance</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center">
                            <button wire:click="edit({{ $area->id }})" class="text-primary p-2">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <button wire:click="delete({{ $area->id }})"
                                onclick="return confirm('Hapus area ini?')"
                                class="text-red-400 p-2">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-500">
                            Tidak ada area parkir
                        </td>
                    </tr>
                    @endforelse

            </tbody>
        </table>
    </div>

    <!-- MODAL -->
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div @click.away="open=false" class="bg-card-dark w-full max-w-lg p-6 rounded-xl">

            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Area Parkir' : 'Tambah Area Parkir' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-4">


                <!-- KODE AREA -->
                <div>
                    <label class="text-sm text-gray-400">Kode Area</label>
                    <input
                        wire:model="kode_area"
                        placeholder="Contoh: A01, B02, VIP-1"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white placeholder-gray-500">
                </div>

                <!-- NAMA AREA -->
                <div>
                    <label class="text-sm text-gray-400">Nama Area</label>
                    <input
                        wire:model="nama_area"
                        placeholder="Contoh: Area Basement, Area VIP"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white placeholder-gray-500">
                </div>

                <!-- LOKASI FISIK -->
                <div>
                    <label class="text-sm text-gray-400">Lokasi Fisik</label>
                    <input
                        wire:model="lokasi_fisik"
                        placeholder="Contoh: Lantai B1 dekat lift"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white placeholder-gray-500">
                </div>

                <hr class="border-gray-700">
                <h3 class="text-sm font-semibold text-gray-300">Tipe Kendaraan</h3>

                @foreach ($this->tipeKendaraan ?? [] as $tipe)
                <div class="space-y-1">

                    <label class="flex items-center gap-2 text-gray-300">
                        <input
                            type="checkbox"
                            wire:model="selectedTipe"
                            value="{{ $tipe->id }}"
                            class="rounded border-gray-600 bg-gray-800">
                        {{ $tipe->nama_tipe }}
                    </label>

                    @if (in_array($tipe->id, $selectedTipe))
                        <input
                            type="number"
                            min="1"
                            wire:model.defer="kapasitas.{{ $tipe->id }}"
                            placeholder="Jumlah slot untuk {{ $tipe->nama_tipe }}"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white placeholder-gray-500">
                    @endif

                </div>
                @endforeach

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="open=false" class="text-gray-400 hover:text-white">
                        Batal
                    </button>
                    <button class="bg-primary px-4 py-2 rounded-lg font-bold text-black">
                        Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
