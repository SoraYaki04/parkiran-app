<?php

use App\Models\AreaParkir;
use App\Models\SlotParkir;
use App\Models\TipeKendaraan;
use App\Models\AreaKapasitas;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('Parking Area Management')]
class extends Component {
    use WithPagination;

    public $areaId;
    public $kode_area;
    public $nama_area;
    public $lokasi_fisik;
    public $kapasitas_total;
    public $selectedTipe = [];
    public $kapasitas = [];
    public $status = 'aktif';
    public $lastSavedArea;
    public $hasTerisi = false;
    public $oldKapasitasTotal = 0;


    public $search = '';
    public $isEdit = false;

    /* ===============================
        ACTIVITY LOGGER
    =============================== */
    private function logActivity(
        string $action,
        string $description,
        string $target = null,
        string $category = 'MASTER',
        ?array $oldValues = null,
        ?array $newValues = null,
    ) {
        ActivityLog::log(
            action: $action,
            description: $description,
            target: $target,
            category: $category,
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }

    /* ===============================
        DATA
    =============================== */

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getTipeKendaraanProperty()
    {
        return TipeKendaraan::orderBy('nama_tipe')->get();
    }

    public function getAreasProperty()
    {
        return AreaParkir::query()
            ->when($this->search, function ($q) {
                $q->where('kode_area', 'like', "%{$this->search}%")
                  ->orWhere('nama_area', 'like', "%{$this->search}%")
                  ->orWhere('lokasi_fisik', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(10); // <-- gunakan paginate
    }

    /* ===============================
        STATUS AREA
    =============================== */
    public function getStatus($areaId)
    {
        $area = AreaParkir::find($areaId);

        if ($area->status === 'maintenance') {
            return 'Maintenance';
        }

        $total  = SlotParkir::where('area_id', $areaId)->count();
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
        $area = AreaParkir::with('kapasitas')->findOrFail($id);

        $terisi = SlotParkir::where('area_id', $id)
            ->where('status', 'terisi')
            ->exists();

        $this->hasTerisi = $terisi;

        $this->areaId              = $area->id;
        $this->kode_area           = $area->kode_area;
        $this->nama_area           = $area->nama_area;
        $this->lokasi_fisik        = $area->lokasi_fisik;
        $this->kapasitas_total     = $area->kapasitas_total;
        $this->oldKapasitasTotal   = $area->kapasitas_total;
        $this->status              = $area->status;

        $this->selectedTipe = [];
        $this->kapasitas = [];

        // JIKA BELUM ADA SLOT TERISI → BOLEH EDIT TIPE & KAPASITAS
        if (! $terisi) {
            foreach ($area->kapasitas as $item) {
                $this->selectedTipe[] = $item->tipe_kendaraan_id;
                $this->kapasitas[$item->tipe_kendaraan_id] = $item->kapasitas;
            }
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
            'kode_area'    => 'required',
            'nama_area'    => 'required',
            'lokasi_fisik' => 'required',
            'status'       => 'required|in:aktif,maintenance',
        ]);

        // VALIDASI TIPE & KAPASITAS
        if (! $this->hasTerisi) {

            if (empty($this->selectedTipe)) {
                $this->dispatch('notify',
                    message: 'Pilih minimal satu tipe kendaraan',
                    type: 'error'
                );
                return;
            }

            foreach ($this->selectedTipe as $tipeId) {
                if (
                    !isset($this->kapasitas[$tipeId]) ||
                    $this->kapasitas[$tipeId] <= 0
                ) {
                    $this->dispatch('notify',
                        message: 'Kapasitas tiap tipe wajib diisi',
                        type: 'error'
                    );
                    return;
                }
            }
        }

        DB::transaction(function () {

            // ===============================
            // CEK DATA TERMASUK SOFT DELETE
            // ===============================
            $area = AreaParkir::withTrashed()
                ->where('kode_area', $this->kode_area)
                ->first();

            $logAction = '';
            $logDesc   = '';
            $oldValues = null;
            $newValues = null;

            if ($area && $area->trashed()) {
                // ===============================
                // RESTORE DATA
                // ===============================
                $area->restore();

                $area->update([
                    'nama_area'    => $this->nama_area,
                    'lokasi_fisik' => $this->lokasi_fisik,
                    'status'       => $this->status,
                ]);

                $logAction = 'RESTORE_AREA';
                $logDesc   = 'Pulihkan (restore) area parkir';

            } elseif ($area) {
                // ===============================
                // UPDATE DATA AKTIF
                // ===============================
                // Capture old values before update
                $oldValues = [
                    'nama_area'       => $area->nama_area,
                    'lokasi_fisik'    => $area->lokasi_fisik,
                    'kapasitas_total' => $area->kapasitas_total,
                    'status'          => $area->status,
                ];

                $area->update([
                    'nama_area'    => $this->nama_area,
                    'lokasi_fisik' => $this->lokasi_fisik,
                    'status'       => $this->status,
                ]);

                $logAction = 'UPDATE_AREA';
                $logDesc   = 'Update data area parkir';

            } else {
                // ===============================
                // CREATE BARU
                // ===============================
                $area = AreaParkir::create([
                    'kode_area'    => $this->kode_area,
                    'nama_area'    => $this->nama_area,
                    'lokasi_fisik' => $this->lokasi_fisik,
                    'status'       => $this->status,
                    'kapasitas_total' => 0,
                ]);

                $logAction = 'CREATE_AREA';
                $logDesc   = 'Tambah area parkir baru';
            }

            // ===============================
            // SLOT & KAPASITAS
            // ===============================
            if (! $this->hasTerisi) {

                AreaKapasitas::where('area_id', $area->id)->delete();
                SlotParkir::where('area_id', $area->id)->delete();

                $totalSlot = 0;

                foreach ($this->selectedTipe as $tipeId) {

                    $jumlah = (int) $this->kapasitas[$tipeId];
                    $tipe   = TipeKendaraan::findOrFail($tipeId);

                    AreaKapasitas::create([
                        'area_id'           => $area->id,
                        'tipe_kendaraan_id' => $tipeId,
                        'kapasitas'         => $jumlah,
                    ]);

                    for ($i = 1; $i <= $jumlah; $i++) {
                        SlotParkir::create([
                            'area_id'           => $area->id,
                            'kode_slot'         => $tipe->kode_tipe . $i,
                            'baris'             => $tipe->kode_tipe,
                            'kolom'             => $i,
                            'tipe_kendaraan_id' => $tipeId,
                            'status'            => 'kosong',
                        ]);
                    }

                    $totalSlot += $jumlah;
                }

                $area->update([
                    'kapasitas_total' => $totalSlot
                ]);
            }

            // Build new values for logging (after slot/kapasitas update)
            if ($logAction === 'UPDATE_AREA' && $oldValues) {
                $newValues = [
                    'nama_area'       => $area->nama_area,
                    'lokasi_fisik'    => $area->lokasi_fisik,
                    'kapasitas_total' => $area->kapasitas_total,
                    'status'          => $area->status,
                ];

                // Only keep changed fields
                foreach ($oldValues as $key => $val) {
                    if ($val == ($newValues[$key] ?? null)) {
                        unset($oldValues[$key], $newValues[$key]);
                    }
                }
                if (empty($oldValues)) $oldValues = null;
                if (empty($newValues)) $newValues = null;
            } elseif ($logAction === 'CREATE_AREA') {
                $newValues = [
                    'kode_area'       => $area->kode_area,
                    'nama_area'       => $area->nama_area,
                    'lokasi_fisik'    => $area->lokasi_fisik,
                    'kapasitas_total' => $area->kapasitas_total,
                    'status'          => $area->status,
                ];
            }

            // ===============================
            // ACTIVITY LOG
            // ===============================
            $this->logActivity(
                $logAction,
                $logDesc,
                "Area ID {$area->id} ({$area->nama_area})",
                'MASTER',
                $oldValues,
                $newValues
            );
        });

        $this->dispatch('notify',
            message: 'Area parkir berhasil disimpan',
            type: 'success'
        );

        $this->resetForm();
        $this->dispatch('close-modal');
    }





    /* ===============================
        DELETE
    =============================== */
    public function delete($id)
    {

        $terisi = SlotParkir::where('area_id', $id)
            ->where('status', 'terisi')
            ->exists();

        if ($terisi) {
            $this->dispatch('notify',
                message: 'Area tidak bisa dihapus karena masih ada slot terisi',
                type: 'error'
            );
            return;
        }

        $area = AreaParkir::findOrFail($id);
        $area->delete(); // ← SOFT DELETE

        $this->logActivity(
            'DELETE_AREA',
            'Soft delete area parkir',
            "Area ID {$area->id} ({$area->nama_area})"
        );

        $this->dispatch('notify',
            message: 'Area parkir berhasil dihapus',
            type: 'success'
        );
    }



    /* ===============================
        RESET FORM
    =============================== */
    private function resetForm()
    {
        $this->reset([
            'areaId',
            'kode_area',
            'nama_area',
            'lokasi_fisik',
            'kapasitas_total',
            'oldKapasitasTotal',
            'selectedTipe',
            'kapasitas',
            'isEdit',
            'hasTerisi',
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
            <h2 class="text-white text-3xl font-black">Manajemen Area Parkir</h2>
            <p class="text-slate-400">Atur area parkir</p>
        </div>

        @if(auth()->user()->role_id == 1)
        <button wire:click="create"
            class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
            <span class="material-symbols-outlined">add</span>
            Tambah Area
        </button>
        @endif
    </header>

    <!-- SEARCH -->
    <div class="px-8 pt-6 flex-shrink-0">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <input
                wire:model.live="search"
                class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                placeholder="Cari kode / nama / lokasi area">
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden min-h-[300px]">
            <table class="w-full">
                <thead class="bg-gray-900 sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Kode Area</th>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Nama Area</th>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Lokasi</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Kapasitas</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Status</th>
                        @if(auth()->user()->role_id == 1)
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse ($this->areas as $area)
                        @php
                            $isUsed = \App\Models\SlotParkir::where('area_id', $area->id)
                                        ->where('status', 'terisi')
                                        ->exists();
                            $status = $area->status;
                        @endphp

                        <tr class="hover:bg-surface-hover">
                            <td class="px-6 py-4 text-white font-bold">{{ $area->kode_area }}</td>
                            <td class="px-6 py-4 text-white">{{ $area->nama_area }}</td>
                            <td class="px-6 py-4 text-slate-300">{{ $area->lokasi_fisik }}</td>
                            <td class="px-6 py-4 text-center text-white font-bold">{{ $area->kapasitas_total ?? 0 }}</td>
                            <td class="px-6 py-4 text-center">
                                @if ($status === 'aktif')
                                    <span class="text-xs font-bold text-green-400">Aktif</span>
                                @elseif ($status === 'maintenance')
                                    <span class="text-xs font-bold text-yellow-400">Maintenance</span>
                                @else
                                    <span class="text-xs font-bold text-gray-400">Tidak Diketahui</span>
                                @endif
                            </td>
                            @if(auth()->user()->role_id == 1)
                            <td class="px-6 py-4 text-center">
                                <button wire:click="edit({{ $area->id }})" class="text-primary p-2">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>

                                <button
                                    wire:click="delete({{ $area->id }})"
                                    wire:confirm="Hapus area ini?"
                                    @if($isUsed) disabled @endif
                                    class="p-2 {{ $isUsed ? 'text-gray-500 cursor-not-allowed' : 'text-red-400 hover:text-red-500' }}"
                                    title="{{ $isUsed ? 'Area masih digunakan, tidak bisa dihapus' : 'Hapus' }}">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
                            @endif
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
    </div>

    {{-- PAGINATION --}}
    <div class="px-8 py-2 flex-shrink-0">
        {{ $this->areas->links() }}
    </div>

    @if(auth()->user()->role_id == 1)
    <!-- MODAL -->
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div @click.away="open=false"
            class="bg-card-dark w-full max-w-lg rounded-xl max-h-[90vh] flex flex-col">

            <div class="p-6 border-b border-gray-700">
                <h3 class="text-white font-bold">
                    {{ $isEdit ? 'Edit Area Parkir' : 'Tambah Area Parkir' }}
                </h3>
            </div>

            <form wire:submit.prevent="save" wire:confirm="Apakah anda yakin?" class="flex-1 overflow-y-auto px-6 py-4 space-y-4 scrollbar-hide">

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

                <div>
                    <label class="text-sm text-gray-400">Status Area</label>
                    <select
                        wire:model="status"
                        @if($hasTerisi) disabled @endif
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white
                            {{ $hasTerisi ? 'opacity-60 cursor-not-allowed' : '' }}">
                        <option value="aktif">Aktif</option>
                        <option value="maintenance">Maintenance</option>
                    </select>

                    @if($hasTerisi)
                        <p class="text-xs text-yellow-400 mt-1">
                            Status tidak dapat diubah karena sudah ada kendaraan parkir
                        </p>
                    @endif
                </div>


                @if($hasTerisi)
                    <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm p-3 rounded-lg">
                        Area ini sudah memiliki kendaraan parkir.
                        <br>
                        Pengaturan tipe kendaraan, jumlah slot, dan status tidak dapat diubah.
                    </div>
                @endif

                <hr class="border-gray-700">
                <h3 class="text-sm font-semibold text-gray-300">Tipe Kendaraan</h3>

                @foreach ($this->tipeKendaraan ?? [] as $tipe)
                <div class="space-y-1">

                    <label class="flex items-center gap-2 text-gray-300">
                    <input
                        type="checkbox"
                        wire:model.live="selectedTipe"
                        value="{{ $tipe->id }}"
                        @if($hasTerisi) disabled @endif
                        class="rounded border-gray-600 bg-gray-800
                            {{ $hasTerisi ? 'opacity-60 cursor-not-allowed' : '' }}">

                        {{ $tipe->nama_tipe }}
                    </label>

                    @if (in_array($tipe->id, $selectedTipe))
                    <input
                        type="number"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'');"
                        min="1"
                        step="1"
                        wire:model.defer="kapasitas.{{ $tipe->id }}"
                        placeholder="Jumlah slot untuk {{ $tipe->nama_tipe }}"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white
                            {{ $hasTerisi ? 'opacity-60 cursor-not-allowed' : '' }}">

                            
                    @endif


                </div>
                @endforeach

                <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                    <button type="button" @click="open=false" class="text-gray-400 hover:text-white">
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

