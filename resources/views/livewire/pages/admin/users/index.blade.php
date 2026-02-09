<?php

use App\Models\User;
use App\Models\ActivityLog;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('User Management')]
class extends Component {
    use WithPagination;

    /* ======================
        STATE
    =======================*/
    public $userId;
    public $username;
    public $name;
    public $password;
    public $status;
    public $role_id;

    public $search = '';
    public $filterRole = '';
    public $isEdit = false;

    /* ======================
        PAGINATION RESET
        (KONSISTEN DENGAN LOG)
    =======================*/
    public function updated($property)
    {
        if (in_array($property, ['search', 'filterRole'])) {
            $this->resetPage();
        }
    }

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
        COMPUTED USERS
        (SINGLE SOURCE + PAGINATION)
    =======================*/
    #[Computed]
    public function getUsersProperty()
    {
        return User::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%")
                        ->orWhere('id', $this->search);
                });
            })
            ->when($this->filterRole, fn ($q) =>
                $q->where('role_id', $this->filterRole)
            )
            ->latest()
            ->paginate(10);
    }


    /* ======================
        CREATE
    =======================*/
    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->dispatch('open-modal');
    }

    /* ======================
        EDIT
    =======================*/
    public function edit($id)
    {
        $user = User::findOrFail($id);

        $this->userId   = $user->id;
        $this->username = $user->username;
        $this->name     = $user->name;
        $this->status   = $user->status;
        $this->role_id  = $user->role_id;

        $this->password = null;
        $this->isEdit   = true;

        $this->dispatch('open-modal');
    }

    /* ======================
        SAVE
    =======================*/
    public function save()
    {
        $rules = [
            'username' => 'required|unique:users,username,' . $this->userId,
            'name'     => 'required',
            'status'   => 'required',
            'role_id'  => 'required',
        ];

        if (!$this->isEdit) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'username' => $this->username,
            'name'     => $this->name,
            'status'   => $this->status,
            'role_id'  => $this->role_id,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        if ($this->isEdit) {
            $user = User::findOrFail($this->userId);
            $user->update($data);

            $this->logActivity(
                'UPDATE_USER',
                'Update data user',
                "User ID: {$user->id} ({$user->username})"
            );

            $this->dispatch('notify', message: 'User berhasil diperbarui!', type: 'success');
        } else {
            $user = User::create($data);

            $this->logActivity(
                'CREATE_USER',
                'Menambahkan user baru',
                "User ID: {$user->id} ({$user->username})"
            );

            $this->dispatch('notify', message: 'User baru berhasil ditambahkan!', type: 'success');
        }

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    /* ======================
        DELETE
    =======================*/
    public function delete($id)
    {
        $user = User::findOrFail($id);

        $this->logActivity(
            'DELETE_USER',
            'Menghapus user',
            "User ID: {$user->id} ({$user->username})"
        );

        $user->delete();

        $this->dispatch('notify', message: 'User berhasil dihapus!', type: 'success');
    }

    private function resetForm()
    {
        $this->reset([
            'userId',
            'username',
            'name',
            'password',
            'status',
            'role_id',
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
            <h2 class="text-white text-3xl font-black">Manajemen User</h2>
            <p class="text-slate-400">Buat dan Atur User</p>
        </div>

        <button wire:click="create"
                class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold">
            <span class="material-symbols-outlined">add</span>
            Tambah User
        </button>
    </header>

    {{-- FILTER --}}
    <div class="px-8 pt-6">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59]">
            <div class="flex flex-col md:flex-row gap-4">

                <input wire:model.live="search"
                       class="flex-1 bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                       placeholder="Search name / username / ID">

                <select wire:model.live="filterRole"
                        class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                    <option value="">Semua Role</option>
                    <option value="1">Admin</option>
                    <option value="2">Petugas</option>
                    <option value="3">Owner</option>
                </select>

            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Nama</th>
                        <th class="px-6 py-4 text-left text-slate-400 text-xs">Username</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Status</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Role</th>
                        <th class="px-6 py-4 text-center text-slate-400 text-xs">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->users as $user)
                        <tr class="hover:bg-surface-hover">
                            <td class="px-6 py-4 text-white">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-white">{{ $user->username }}</td>
                            <td class="px-6 py-4 text-center text-slate-300">{{ ucfirst($user->status) }}</td>
                            <td class="px-6 py-4 text-center">

                            @php([$label, $class] = $user->role_badge)

                            <span class="px-3 py-1 text-xs rounded-full {{ $class }}">
                                {{ $label }}
                            </span>
                            
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="edit({{ $user->id }})" class="text-primary p-2">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button wire:click="delete({{ $user->id }})"
                                        onclick="return confirm('Hapus user ini?')"
                                        class="text-red-400 p-2">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-500">
                                TIdak Ada Data User
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $this->users->links() }}
    </div>

    {{-- MODAL --}}
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-card-dark w-full max-w-md p-6 rounded-xl">
            <h3 class="text-white font-bold mb-4">
                {{ $isEdit ? 'Edit User' : 'Tambah User' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="text-sm text-gray-400">Nama Anda</label>
                    <input wire:model="name" class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white" placeholder="Nama">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Username (digunakan saat login)</label>
                    <input wire:model="username" class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white" placeholder="Username">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Password {{ $isEdit ? '(Isi jika ingin mengganti)' : '' }}</label>
                    <input wire:model="password" type="password"
                        class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white"
                        placeholder="Password">
                </div>

                <div>
                    <label class="text-sm text-gray-400">Status</label>
                    <select wire:model="status" class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                        <option value="">Pilih Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-400">Role</label>
                    <select wire:model="role_id" class="w-full bg-[#161e25] border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                        <option value="">Select Role</option>
                        <option value="1">Admin</option>
                        <option value="2">Petugas</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" x-on:click="open=false" class="text-gray-400">Cancel</button>
                    <button class="bg-primary px-5 py-2 rounded-lg font-bold text-black">Save</button>
                </div>
            </form>
        </div>
    </div>

</div>
