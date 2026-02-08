<?php

namespace App\Livewire;

use App\Models\Member;
use App\Models\TierMember;
use App\Models\Kendaraan;
use App\Models\TipeKendaraan;
use App\Models\ActivityLog;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('layouts.app')]
#[Title('Member Management')]
class extends Component
{
    public $memberId;
    public $kode_member;
    public $nama;
    public $no_hp;
    public $tier_member_id;
    public $tipe_kendaraan_id; // Pilihan tipe kendaraan
    public $tanggal_mulai;
    public $tanggal_berakhir;
    public $status = 'aktif';
    public $plat_search = '';
    public $isEdit = false;

    public string $search = '';
    public string $filterTier = '';
    public string $filterStatus = '';

    public function mount()
    {
        $this->autoExpireMember();
    }

    private function logActivity(string $action, string $description, string $target = null, string $category = 'MASTER')
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'category' => $category,
            'target' => $target,
            'description' => $description,
        ]);
    }

    private function autoExpireMember()
    {
        Member::where('status', 'aktif')
            ->whereNotNull('tanggal_berakhir')
            ->whereDate('tanggal_berakhir', '<', now())
            ->update(['status' => 'expired']);
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'filterTier', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function getMembersProperty()
    {
        return Member::with(['kendaraan', 'tier'])
            ->when($this->search, fn($q) =>
                $q->where('kode_member','like',"%{$this->search}%")
                  ->orWhere('nama','like',"%{$this->search}%")
                  ->orWhereHas('kendaraan', fn($k) => $k->where('plat_nomor','like',"%{$this->search}%"))
            )
            ->when($this->filterTier, fn($q)=> $q->where('tier_member_id', $this->filterTier))
            ->when($this->filterStatus, fn($q)=> $q->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getTiersProperty() 
    { 
        return TierMember::where('status','aktif')->get();
    }

    public function getTipeKendaraansProperty()
    {
        return TipeKendaraan::all();
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
            'memberId','tier_member_id','tipe_kendaraan_id',
            'plat_search','tanggal_berakhir','nama','no_hp'
        ]);

        $this->tanggal_mulai = now()->toDateString();
        $this->kode_member   = $this->generateKodeMember();
        $this->status        = 'aktif';
        $this->isEdit        = false;

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
        $this->nama             = $m->nama;
        $this->no_hp            = $m->no_hp;
        $this->plat_search      = $m->kendaraan->plat_nomor;
        $this->tipe_kendaraan_id= $m->kendaraan->tipe_kendaraan_id ?? null;
        $this->tier_member_id   = $m->tier_member_id;
        $this->tanggal_mulai    = $m->tanggal_mulai;
        $this->tanggal_berakhir = $m->tanggal_berakhir;
        $this->status           = $m->status;

        $this->isEdit = true;
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
    
    private function normalizePlat(string $input): string
    {
        $input = strtoupper(trim($input));
        $input = preg_replace('/[^A-Z0-9]/', '', $input);
    
        if (!preg_match('/^([A-Z]{1,2})(\d{1,5})([A-Z]{0,3})$/', $input, $m)) {
            throw new \Exception('Format plat tidak valid');
        }
    
        return trim($m[1] . ' ' . $m[2] . ' ' . ($m[3] ?? ''));
    }

    /* ===============================
        SAVE
    =============================== */

    public function save()
    {
        $this->validate([
            'nama'           => 'required|string|max:255',
            'no_hp'          => 'required|string|max:20',
            'plat_search'    => 'required|string|max:20',
            'tipe_kendaraan_id' => 'required|exists:tipe_kendaraan,id',
            'tier_member_id' => 'required|exists:tier_member,id',
        ]);

        try {
            $platDB = $this->normalizePlat($this->plat_search);
        } catch (\Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
            return;
        }

        $kendaraan = Kendaraan::where('plat_nomor', $platDB)->first();

        if (!$kendaraan) {
            $kendaraan = Kendaraan::create([
                'plat_nomor' => $platDB,
                'tipe_kendaraan_id' => $this->tipe_kendaraan_id,
            ]);

            $this->logActivity(
                'CREATE_KENDARAAN',
                'Menambahkan kendaraan baru dari form member',
                "ID {$kendaraan->id} ({$kendaraan->plat_nomor})"
            );
        } else {
            if ($kendaraan->tipe_kendaraan_id != $this->tipe_kendaraan_id) {
                $kendaraan->update(['tipe_kendaraan_id' => $this->tipe_kendaraan_id]);
            }
        }

        $member = Member::updateOrCreate(
            ['id' => $this->memberId],
            [
                'kode_member'      => $this->kode_member,
                'nama'             => $this->nama,
                'no_hp'            => $this->no_hp,
                'kendaraan_id'     => $kendaraan->id,
                'tier_member_id'   => $this->tier_member_id,
                'tanggal_mulai'    => $this->tanggal_mulai,
                'tanggal_berakhir' => $this->tanggal_berakhir,
                'status'           => $this->isEdit ? $this->status : 'aktif',
            ]
        );

        $this->logActivity(
            $this->isEdit ? 'UPDATE_MEMBER' : 'CREATE_MEMBER',
            $this->isEdit ? 'Update data member' : 'Menambahkan member baru',
            "ID {$member->id} ({$member->kode_member})"
        );

        $this->dispatch('notify', message: $this->isEdit ? 'Member berhasil diperbarui!' : 'Member baru berhasil ditambahkan!', type: 'success');

        $this->reset(['memberId', 'plat_search', 'nama', 'no_hp', 'tier_member_id', 'tanggal_berakhir']);
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

                {{-- NAMA --}}
                <div>
                    <label class="text-sm text-gray-400">Nama</label>
                    <input type="text" wire:model="nama"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                        placeholder="Masukkan nama member">
                </div>

                {{-- NOMOR HP --}}
                <div>
                    <label class="text-sm text-gray-400">No. HP</label>
                    <input type="text" wire:model="no_hp"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                        placeholder="Masukkan nomor HP">
                </div>

                {{-- PILIH TIPE KENDARAAN --}}
                <div>
                    <label class="text-sm text-gray-400">Tipe Kendaraan</label>
                    <select wire:model="tipe_kendaraan_id"
                            class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                        <option value="">Pilih Tipe Kendaraan</option>
                        @foreach($this->tipeKendaraans as $t)
                            <option value="{{ $t->id }}">{{ $t->nama_tipe }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-400">Plat Nomor</label>
                    <input 
                        wire:model.live="plat_search" 
                        id="plat-input"
                        class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 h-16 pl-5 pr-4 text-lg font-bold uppercase focus:border-primary-500 focus:ring-0 transition-all text-slate-900 dark:text-white" 
                        placeholder="INPUT PLAT"
                        oninput="formatPlatDash(this)"
                    />
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

@push('scripts')
<script>
    function formatPlatDash(el) {
        let value = el.value.toUpperCase();

        // buang semua kecuali huruf & angka
        value = value.replace(/[^A-Z0-9]/g, '');

        let depan = '';
        let nomor = '';
        let belakang = '';

        // 1–2 huruf depan
        depan = value.match(/^[A-Z]{1,2}/)?.[0] ?? '';
        value = value.slice(depan.length);

        // 1–5 angka tengah
        nomor = value.match(/^\d{1,5}/)?.[0] ?? '';
        value = value.slice(nomor.length);

        // 0–3 huruf belakang
        belakang = value.slice(0, 3);

        let hasil = depan;
        if (nomor) hasil += '-' + nomor;
        if (belakang) hasil += '-' + belakang;

        el.value = hasil;
    }
</script>

@endpush