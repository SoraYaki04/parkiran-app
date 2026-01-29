<?php

use App\Models\TierMember;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')]
#[Title('Tier Member')]
class extends Component {

    public $tierId;

    public $nama;
    public $harga;
    public $periode;
    public $diskon_persen;
    public $masa_berlaku_hari;
    public $status = 'aktif';

    public $search = '';
    public $isEdit = false;

    /* ===============================
        DATA
    =============================== */

    public function getTierMembersProperty()
    {
        return TierMember::query()
            ->when($this->search, fn ($q) =>
                $q->where('nama', 'like', "%{$this->search}%")
            )
            ->orderBy('harga')
            ->get();
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

        $this->tierId             = $tier->id;
        $this->nama               = $tier->nama;
        $this->harga              = $tier->harga;
        $this->periode            = $tier->periode;
        $this->diskon_persen      = $tier->diskon_persen;
        $this->masa_berlaku_hari  = $tier->masa_berlaku_hari;
        $this->status             = $tier->status;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        $this->validate([
            'nama'              => 'required|unique:tier_member,nama,' . $this->tierId,
            'harga'             => 'required|integer|min:0',
            'periode'           => 'required|in:bulanan,tahunan',
            'diskon_persen'     => 'required|integer|min:0|max:100',
            'status'            => 'required|in:aktif,nonaktif',
        ]);

        TierMember::updateOrCreate(
            ['id' => $this->tierId],
            [
                'nama'              => $this->nama,
                'harga'             => $this->harga,
                'periode'           => $this->periode,
                'diskon_persen'     => $this->diskon_persen,
                'status'            => $this->status,
            ]
        );

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    public function delete($id)
    {
        TierMember::findOrFail($id)->delete();
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

        <button wire:click="create"
                class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
            <span class="material-symbols-outlined">add</span>
            Tambah Tier
        </button>
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
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->tierMembers as $tier)
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

                            <td class="px-6 py-4 text-center">
                                <button wire:click="edit({{ $tier->id }})"
                                        class="text-primary p-2">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>

                                <button wire:click="delete({{ $tier->id }})"
                                        onclick="return confirm('Hapus tier ini?')"
                                        class="text-red-400 p-2">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
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
    </div>

    {{-- MODAL --}}
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Tier' : 'Tambah Tier' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-3">
                <input wire:model="nama"
                       class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                       placeholder="Tier name (Silver, Gold...)">

                <input wire:model="harga" type="number"
                       class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                       placeholder="Harga">

                <select wire:model="periode"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    <option value="">Select Periode</option>
                    <option value="bulanan">Bulanan</option>
                    <option value="tahunan">Tahunan</option>
                </select>

                <input wire:model="diskon_persen" type="number"
                       class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                       placeholder="Diskon (%)">

                <select wire:model="status"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>

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

