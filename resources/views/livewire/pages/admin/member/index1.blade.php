<?php

use App\Models\Member;
use App\Models\TierMember;
use App\Models\Kendaraan;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('layouts.app')]
#[Title('Member Management')]
class extends Component {

    public $memberId;

    public $kode_member;
    public $kendaraan_id;
    public $tier_member_id;
    public $tanggal_mulai;
    public $tanggal_berakhir;
    public $status = 'aktif';

    public $search = '';
    public $plat_search = '';
    public $isEdit = false;

    /* ===============================
        DATA
    =============================== */

    public function getMembersProperty()
    {
        return Member::with(['kendaraan','tier'])
            ->when($this->search, fn ($q) =>
                $q->where('kode_member', 'like', "%{$this->search}%")
                  ->orWhereHas('kendaraan', fn ($k) =>
                        $k->where('plat_nomor', 'like', "%{$this->search}%")
                  )
            )
            ->latest()
            ->get();
    }

    public function getTiersProperty()
    {
        return TierMember::where('status','aktif')->get();
    }

    public function getKendaraanListProperty()
    {
        return Kendaraan::where('plat_nomor','like',"%{$this->plat_search}%")
            ->limit(10)
            ->get();
    }

    /* ===============================
        ACTION
    =============================== */

    public function updatedTierMemberId($value)
    {
        if (!$this->tanggal_mulai) return;

        $tier = TierMember::find($value);
        if (!$tier) return;

        $this->tanggal_berakhir = Carbon::parse($this->tanggal_mulai)
            ->addDays($tier->masa_berlaku_hari)
            ->format('Y-m-d');
    }

    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;

        // AUTO GENERATE KODE MEMBER
        $this->kode_member = $this->generateKodeMember();

        $this->dispatch('open-modal');
    }


    public function edit($id)
    {
        $m = Member::findOrFail($id);

        $this->memberId         = $m->id;
        $this->kode_member      = $m->kode_member;
        $this->kendaraan_id     = $m->kendaraan_id;
        $this->tier_member_id   = $m->tier_member_id;
        $this->tanggal_mulai    = $m->tanggal_mulai;
        $this->tanggal_berakhir = $m->tanggal_berakhir;
        $this->status           = $m->status;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        $this->validate([
            'kode_member'      => 'required|unique:member,kode_member,' . $this->memberId,
            'kendaraan_id'     => 'required|exists:kendaraan,id',
            'tier_member_id'   => 'required|exists:tier_member,id',
            'tanggal_mulai'    => 'required|date',
            'tanggal_berakhir' => 'required|date|after:tanggal_mulai',
        ]);

        Member::updateOrCreate(
            ['id' => $this->memberId],
            [
                'kode_member'      => $this->kode_member,
                'kendaraan_id'     => $this->kendaraan_id,
                'tier_member_id'   => $this->tier_member_id,
                'tanggal_mulai'    => $this->tanggal_mulai,
                'tanggal_berakhir' => $this->tanggal_berakhir,
                'status'           => 'aktif',
            ]
        );

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    public function delete($id)
    {
        Member::findOrFail($id)->delete();
    }

    private function resetForm()
    {
        $this->reset([
            'memberId',
            'kode_member',
            'kendaraan_id',
            'tier_member_id',
            'tanggal_mulai',
            'tanggal_berakhir',
            'plat_search',
            'status',
        ]);
    }

    private function generateKodeMember()
    {
        $tahun = now()->year;

        $lastMember = Member::whereYear('created_at', $tahun)
            ->orderBy('id', 'desc')
            ->first();

        $urutan = 1;
        if ($lastMember) {
            $urutan = (int) substr($lastMember->kode_member, -4) + 1;
        }

        return 'MBR-' . $tahun . '-' . str_pad($urutan, 4, '0', STR_PAD_LEFT);
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
            <h2 class="text-3xl font-black text-white">Managemen List Member</h2>
            <p class="text-slate-400">Managemen member terdaftar</p>
        </div>

        <button wire:click="create"
            class="flex items-center gap-2 h-10 px-5 bg-primary text-black font-bold rounded-lg">
            <span class="material-symbols-outlined">add</span>
            Register Member
        </button>
    </header>

    {{-- SEARCH --}}
    <div class="px-8 pt-6">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <input wire:model.live="search"
                class="w-full md:w-1/3 bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                placeholder="Cari kode / plat kendaraan">
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-xs text-slate-400 text-left">Member ID</th>
                        <th class="px-6 py-4 text-xs text-slate-400 text-left">Plat Nomor</th>
                        <th class="px-6 py-4 text-xs text-slate-400">Tier</th>
                        <th class="px-6 py-4 text-xs text-slate-400">Tanggal Berakhir</th>
                        <th class="px-6 py-4 text-xs text-slate-400">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-border-dark">
                    @forelse($this->members as $m)
                    <tr class="group hover:bg-[#3E4C59]/30">
                        <td class="px-6 py-4 font-mono text-text-muted">{{ $m->kode_member }}</td>
                        <td class="px-6 py-4 text-white">{{ $m->kendaraan->plat_nomor }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full bg-primary/10 text-primary">
                                {{ $m->tier->nama }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-text-muted">
                            {{ \Carbon\Carbon::parse($m->tanggal_berakhir)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100">
                                <button wire:click="edit({{ $m->id }})" class="text-primary">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button wire:click="delete({{ $m->id }})" onclick="return confirm('Hapus member ini?')"
                                    class="text-red-400">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-slate-400">
                            Tidak ada member ditemukan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL MEMBER --}}
    <div x-show="open" x-transition
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">

            {{-- TITLE --}}
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Member' : 'Tambah Member' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-3">

                {{-- KODE MEMBER --}}
                <input wire:model="kode_member" readonly
                    class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-gray-400 cursor-not-allowed">


                {{-- CARI PLAT --}}
                <input wire:model.live="plat_search"
                    class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                    placeholder="Cari plat kendaraan">

                {{-- LIST KENDARAAN --}}
                <div class="border border-[#3E4C59] rounded-lg max-h-40 overflow-y-auto">
                    @forelse($this->kendaraanList as $k)
                        <div wire:click="$set('kendaraan_id', {{ $k->id }})"
                            class="px-3 py-2 cursor-pointer hover:bg-[#1f2933] text-white">
                            {{ $k->plat_nomor }}
                        </div>
                    @empty
                        <div class="px-3 py-2 text-gray-400">
                            Tidak ada kendaraan
                        </div>
                    @endforelse
                </div>

                {{-- TIER MEMBER --}}
                <select wire:model="tier_member_id"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    <option value="">Pilih Tier</option>
                    @foreach($this->tiers as $t)
                        <option value="{{ $t->id }}">{{ $t->nama }}</option>
                    @endforeach
                </select>

                {{-- TANGGAL MULAI --}}
                <input type="date"
                    wire:model="tanggal_mulai"
                    class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">

                {{-- TANGGAL BERAKHIR (AUTO) --}}
                <input type="date"
                    wire:model="tanggal_berakhir"
                    readonly
                    class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-gray-400">

                {{-- ACTION --}}
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
