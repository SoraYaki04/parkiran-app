<?php

use App\Models\Member;
use App\Models\TierMember;
use App\Models\Kendaraan;
use App\Models\ActivityLog;
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
    public $plat_search = '';
    public $isEdit = false;
    public $showDropdown = false;

    public string $search = '';
    public string $filterTier = '';
    public string $filterStatus = '';

    public function mount()
    {
        $this->autoExpireMember();
    }

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
        AUTO EXPIRED
    =============================== */
    private function autoExpireMember()
    {
        Member::where('status', 'aktif')
            ->whereNotNull('tanggal_berakhir')
            ->whereDate('tanggal_berakhir', '<', now())
            ->update(['status' => 'expired']);
    }

    /* ===============================
        COMPUTED DATA
    =============================== */

   public function updated($property)
    {
        if (in_array($property, [
            'search',
            'filterTier',
            'filterStatus',
        ])) {
            $this->resetPage();
        }
    }


    public function getMembersProperty()
    {
        return Member::with(['kendaraan', 'tier'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('kode_member', 'like', "%{$this->search}%")
                        ->orWhereHas('kendaraan', function ($k) {
                            $k->where('plat_nomor', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterTier, function ($q) {
                $q->where('tier_id', $this->filterTier);
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }


    public function getTiersProperty()
    {
        return TierMember::where('status','aktif')->get();
    }

    public function getKendaraanListProperty()
    {
        if (!$this->plat_search || !$this->showDropdown) {
            return collect();
        }

        return Kendaraan::where('plat_nomor','like',"%{$this->plat_search}%")
            ->limit(7)
            ->get();
    }

    /* ===============================
        SEARCH HANDLER
    =============================== */
    public function updatedPlatSearch()
    {
        $this->showDropdown = strlen($this->plat_search) >= 2;
    }

    public function selectKendaraan($id, $plat)
    {
        $this->kendaraan_id = $id;
        $this->plat_search = $plat;
        $this->showDropdown = false;
    }

    /* ===============================
        LOGIC TANGGAL
    =============================== */
    public function updatedTierMemberId() { $this->hitungTanggalBerakhir(); }
    public function updatedTanggalMulai() { $this->hitungTanggalBerakhir(); }

    private function hitungTanggalBerakhir()
    {
        if (!$this->tier_member_id || !$this->tanggal_mulai) return;

        $tier = TierMember::find($this->tier_member_id);
        if (!$tier) return;

        $mulai = Carbon::parse($this->tanggal_mulai);

        match ($tier->periode) {
            'bulanan' => $akhir = $mulai->copy()->addMonth(),
            'tahunan' => $akhir = $mulai->copy()->addYear(),
            default   => $akhir = $mulai->copy()->addDays($tier->masa_berlaku_hari ?? 0),
        };

        $this->tanggal_berakhir = $akhir->format('Y-m-d');
    }

    /* ===============================
        CREATE
    =============================== */
    public function create()
    {
        $this->reset([
            'memberId', 'kendaraan_id', 'tier_member_id',
            'plat_search','tanggal_berakhir'
        ]);

        $this->tanggal_mulai = now()->toDateString();
        $this->kode_member   = $this->generateKodeMember();
        $this->status        = 'aktif';
        $this->isEdit        = false;
        $this->showDropdown  = false;

        $this->dispatch('open-modal');
    }

    /* ===============================
        EDIT
    =============================== */
    public function edit($id)
    {
        $m = Member::with('kendaraan')->findOrFail($id);

        $this->memberId         = $m->id;
        $this->kode_member      = $m->kode_member;
        $this->kendaraan_id     = $m->kendaraan_id;
        $this->plat_search      = $m->kendaraan->plat_nomor;
        $this->tier_member_id   = $m->tier_member_id;
        $this->tanggal_mulai    = $m->tanggal_mulai;
        $this->tanggal_berakhir = $m->tanggal_berakhir;
        $this->status           = $m->status;

        $this->isEdit        = true;
        $this->showDropdown = false;

        $this->dispatch('open-modal');
    }

    /* ===============================
        DELETE
    =============================== */
    public function delete($id)
    {
        $member = Member::findOrFail($id);

        // LOG SEBELUM DELETE
        $this->logActivity(
            'DELETE_MEMBER',
            'Menghapus member',
            "ID {$member->id} ({$member->kode_member})"
        );

        $member->delete();

        $this->dispatch('notify',
            message: 'Member berhasil dihapus!',
            type: 'success'
        );
    }

    /* ===============================
        SAVE
    =============================== */
    public function save()
    {
        $rules = [
            'kendaraan_id'   => 'required|exists:kendaraan,id',
            'tier_member_id' => 'required|exists:tier_member,id',
        ];

        if (!$this->isEdit) {
            $rules['kendaraan_id'] .= '|unique:member,kendaraan_id';
        }

        $this->validate($rules);

        $member = Member::updateOrCreate(
            ['id' => $this->memberId],
            [
                'kode_member'      => $this->kode_member,
                'kendaraan_id'     => $this->kendaraan_id,
                'tier_member_id'   => $this->tier_member_id,
                'tanggal_mulai'    => $this->tanggal_mulai,
                'tanggal_berakhir' => $this->tanggal_berakhir,
                'status' => $this->isEdit ? $this->status : 'aktif',
            ]
        );

        // LOG & NOTIFIKASI
        if ($this->isEdit) {
            $this->logActivity(
                'UPDATE_MEMBER',
                'Update data member',
                "ID {$member->id} ({$member->kode_member})"
            );
            $this->dispatch('notify', message: 'Member berhasil diperbarui!', type: 'success');
        } else {
            $this->logActivity(
                'CREATE_MEMBER',
                'Menambahkan member baru',
                "ID {$member->id} ({$member->kode_member})"
            );
            $this->dispatch('notify', message: 'Member baru berhasil ditambahkan!', type: 'success');
        }

        $this->reset([
            'memberId','kendaraan_id','tier_member_id','plat_search','tanggal_berakhir'
        ]);
        $this->dispatch('close-modal');
    }

    /* ===============================
        GENERATE KODE MEMBER
    =============================== */
    private function generateKodeMember()
    {
        $tahun = now()->year;

        $last = Member::whereYear('created_at',$tahun)
            ->orderByDesc('id')
            ->first();

        $urutan = $last
            ? ((int) substr($last->kode_member, -4)) + 1
            : 1;

        return 'MBR-'.$tahun.'-'.str_pad($urutan,4,'0',STR_PAD_LEFT);
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
    <div class="px-8 pt-6 flex-shrink-0">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <div class="flex flex-col md:flex-row gap-4">
                <input wire:model.live="search"
                    class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                    placeholder="Cari kode / plat kendaraan">

                <select wire:model.live="filterTier"
                        class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    <option value="">Semua Tier</option>
                    @foreach($this->tiers as $t)
                        <option value="{{ $t->id }}">{{ $t->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden min-h-[300px]">
            <table class="w-full">
                <thead class="bg-gray-900 sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-4 text-xs text-slate-400 text-left">Member ID</th>
                        <th class="px-6 py-4 text-xs text-slate-400 text-left">Plat Nomor</th>
                        <th class="px-6 py-4 text-xs text-slate-400 text-center">Tier</th>
                        <th class="px-6 py-4 text-xs text-slate-400 text-center">Tanggal Berakhir</th>
                        <th class="px-6 py-4 text-xs text-slate-400 text-center">Status</th>
                        <th class="px-6 py-4 text-xs text-slate-400 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->members as $m)
                        <tr class="group hover:bg-[#3E4C59]/30">
                            <td class="px-6 py-4 font-mono text-slate-400 text-left">
                                {{ $m->kode_member }}
                            </td>

                            <td class="px-6 py-4 text-white text-left">
                                {{ $m->kendaraan->plat_nomor }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 text-xs rounded-full bg-primary/10 text-primary">
                                    {{ $m->tier->nama }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-slate-400 text-center">
                                {{ \Carbon\Carbon::parse($m->tanggal_berakhir)->format('d M Y') }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if($m->status === 'aktif')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500/10 text-green-400">
                                        Aktif
                                    </span>
                                @elseif($m->status === 'nonaktif')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500/10 text-gray-400">
                                        Nonaktif
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-500/10 text-red-400">
                                        Expired
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 flex justify-center">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $m->id }})" class="text-primary">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button wire:click="delete({{ $m->id }})"
                                        onclick="return confirm('Hapus member ini?')"
                                        class="text-red-400">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">
                                Tidak ada member ditemukan
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $this->members->links() }}
    </div>

    {{-- MODAL MEMBER --}}
    <div x-show="open"
        x-transition
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">

            {{-- TITLE --}}
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit Member' : 'Tambah Member' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-3">

                {{-- KODE MEMBER --}}
                <div>
                    <label class="text-sm text-gray-400">Kode Member</label>
                    <input wire:model="kode_member"
                        readonly
                        class="w-full bg-gray-700 border border-gray-600
                            rounded-lg px-4 py-2 text-gray-300 cursor-not-allowed"
                </div>

                {{-- SEARCH PLAT --}}
                <div class="relative">
                    <label class="text-sm text-gray-400">Search Plat</label>
                    <input wire:model.live="plat_search"
                        placeholder="Cari plat kendaraan..."
                        oninput="formatPlatLivewire(this)"
                        class="w-full bg-[#161e25] border border-[#3E4C59]
                                rounded-lg px-4 py-2 text-white">

                    @if($plat_search && $this->kendaraanList->count())
                        <div class="absolute z-50 w-full mt-1 bg-[#020617]
                                    border border-gray-700 rounded-lg
                                    max-h-40 overflow-y-auto">

                            @foreach($this->kendaraanList as $k)
                                <div
                                    wire:click="selectKendaraan({{ $k->id }}, '{{ $k->plat_nomor }}')"
                                    class="px-4 py-2 hover:bg-gray-700 cursor-pointer text-white">
                                    {{ $k->plat_nomor }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>


                {{-- TIER --}}
                <div>
                    <label class="text-sm text-gray-400">Pilih Tier</label>
                    <select wire:model="tier_member_id"
                            class="w-full bg-[#161e25] border border-[#3E4C59]
                                rounded-lg px-4 py-2 text-white">
                        <option value="">Pilih Tier</option>
                        @foreach($this->tiers as $t)
                            <option value="{{ $t->id }}">{{ $t->nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- TANGGAL MULAI --}}
                <div>
                    <label class="text-sm text-gray-400">Tanggal Mulai (Sekarang)</label>
                    <input type="date"
                        wire:model="tanggal_mulai"
                        readonly
                        class="w-full bg-gray-700 border border-gray-600
                            rounded-lg px-4 py-2 text-gray-300 cursor-not-allowed"
                </div>

                {{-- STATUS (HANYA SAAT EDIT) --}}
                @if($isEdit)
                    <label class="text-sm text-gray-400">Status</label>
                    <select wire:model="status"
                            class="w-full bg-[#161e25] border border-[#3E4C59]
                                rounded-lg px-4 py-2 text-white">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                        <option value="expired">Expired</option>
                    </select>
                @endif

                {{-- ACTION --}}
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

</div>

