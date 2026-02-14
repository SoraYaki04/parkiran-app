<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Services\BackupService;
use App\Models\ActivityLog;

new #[Layout('layouts.app')]
#[Title('Database Backup')]
class extends Component {
    
    public bool $loading = false;
    public bool $showRestoreModal = false;
    public string $selectedBackup = '';
    
    protected BackupService $backupService;

    public function boot(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function getBackupsProperty()
    {
        return app(BackupService::class)->listBackups();
    }

    public function createBackup()
    {
        $this->loading = true;
        
        $service = app(BackupService::class);
        $result = $service->createBackup();

        ActivityLog::log(
            action: 'CREATE_BACKUP',
            description: $result['message'],
            category: 'SYSTEM',
        );

        $this->loading = false;

        if ($result['success']) {
            $this->dispatch('notify', message: $result['message'], type: 'success');
        } else {
            $this->dispatch('notify', message: $result['message'], type: 'error');
        }
    }

    public function confirmRestore(string $filename)
    {
        $this->selectedBackup = $filename;
        $this->showRestoreModal = true;
    }

    public function restore()
    {
        $this->loading = true;
        $this->showRestoreModal = false;

        $service = app(BackupService::class);
        $result = $service->restoreBackup($this->selectedBackup);

        ActivityLog::log(
            action: 'RESTORE_BACKUP',
            description: "Restored from: {$this->selectedBackup} - " . $result['message'],
            category: 'SYSTEM',
        );

        $this->loading = false;
        $this->selectedBackup = '';

        if ($result['success']) {
            $this->dispatch('notify', message: $result['message'], type: 'success');
        } else {
            $this->dispatch('notify', message: $result['message'], type: 'error');
        }
    }

    public function download(string $filename)
    {
        $service = app(BackupService::class);
        $path = $service->getBackupPath($filename);

        if ($path) {
            return response()->download($path);
        }

        $this->dispatch('notify', message: 'Backup not found', type: 'error');
    }

    public function delete(string $filename)
    {
        $service = app(BackupService::class);
        $result = $service->deleteBackup($filename);

        ActivityLog::log(
            action: 'DELETE_BACKUP',
            description: "Deleted backup: {$filename}",
            category: 'SYSTEM',
        );

        if ($result['success']) {
            $this->dispatch('notify', message: $result['message'], type: 'success');
        } else {
            $this->dispatch('notify', message: $result['message'], type: 'error');
        }
    }
};
?>

<div class="flex-1 flex flex-col h-full overflow-hidden">
    {{-- HEADER --}}
    <header class="px-4 md:px-8 py-4 md:py-6 border-b border-gray-800 flex flex-col sm:flex-row justify-between sm:items-end gap-3 flex-shrink-0">
        <div>
            <h2 class="text-white text-2xl md:text-3xl font-black">Database Backup</h2>
            <p class="text-slate-400 text-sm">Buat dan atur backup database</p>
        </div>

        <button wire:click="createBackup" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold text-sm w-fit hover:bg-primary-400 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="createBackup" class="material-symbols-outlined">backup</span>
            <span wire:loading wire:target="createBackup" class="material-symbols-outlined animate-spin">sync</span>
            <span wire:loading.remove wire:target="createBackup">Buat Backup</span>
            <span wire:loading wire:target="createBackup">Membuat...</span>
        </button>
    </header>

    {{-- INFO BANNER --}}
    <div class="px-4 md:px-8 pt-4 md:pt-6">
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 md:p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-400 shrink-0 mt-0.5">info</span>
            <div class="text-sm text-slate-300">
                <p class="font-bold text-white">Backup & Restore</p>
                <p>Backups are stored locally. <strong class="text-yellow-400">Restoring will overwrite current data.</strong> Make sure to create a backup before restoring.</p>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-4 md:py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[550px]">
                <thead class="bg-gray-900 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 md:px-6 py-3 md:py-4">Filename</th>
                        <th class="px-4 md:px-6 py-3 md:py-4 hidden sm:table-cell">Ukuran</th>
                        <th class="px-4 md:px-6 py-3 md:py-4">Tanggal</th>
                        <th class="px-4 md:px-6 py-3 md:py-4 hidden sm:table-cell">Age</th>
                        <th class="px-4 md:px-6 py-3 md:py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->backups as $backup)
                        <tr class="hover:bg-surface-hover transition group">
                            <td class="px-4 md:px-6 py-3 md:py-4">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <span class="material-symbols-outlined text-primary">archive</span>
                                    <span class="font-medium text-white text-xs md:text-sm truncate max-w-[150px] md:max-w-none">{{ $backup['filename'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-slate-300 text-sm hidden sm:table-cell">{{ $backup['size'] }}</td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-slate-300 text-sm">{{ $backup['date'] }}</td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-slate-400 text-sm hidden sm:table-cell">{{ $backup['age'] }}</td>
                            <td class="px-4 md:px-6 py-3 md:py-4 text-right">
                                <div class="flex items-center justify-end gap-1 md:gap-2 md:opacity-0 md:group-hover:opacity-100 transition">
                                    <button wire:click="download('{{ $backup['filename'] }}')"
                                            class="p-2 text-slate-400 hover:text-blue-400 rounded-lg hover:bg-blue-500/10" title="Download">
                                        <span class="material-symbols-outlined text-[20px]">download</span>
                                    </button>
                                    <button wire:click="confirmRestore('{{ $backup['filename'] }}')"
                                            class="p-2 text-slate-400 hover:text-yellow-400 rounded-lg hover:bg-yellow-500/10" title="Restore">
                                        <span class="material-symbols-outlined text-[20px]">settings_backup_restore</span>
                                    </button>
                                    <button wire:click="delete('{{ $backup['filename'] }}')"
                                            wire:confirm="Delete this backup?"
                                            class="p-2 text-slate-400 hover:text-red-400 rounded-lg hover:bg-red-500/10" title="Delete">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center">
                                <div class="flex flex-col items-center text-slate-500">
                                    <span class="material-symbols-outlined text-5xl mb-3">cloud_off</span>
                                    <p class="font-bold">Tidak ada backup ditemukan</p>
                                    <p class="text-sm">Buat backup pertamamu sebelum mulai.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- RESTORE CONFIRMATION MODAL --}}
    @if($showRestoreModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm" wire:click.self="$set('showRestoreModal', false)">
            <div class="bg-surface-dark border border-[#3E4C59] rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex items-center gap-4 mb-6">
                    <div class="p-3 rounded-full bg-yellow-500/20">
                        <span class="material-symbols-outlined text-yellow-400 text-3xl">warning</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Konfirmasi Restore</h3>
                        <p class="text-slate-400 text-sm">Aksi ini tidak bisa dibatalkan</p>
                    </div>
                </div>

                <p class="text-slate-300 mb-6">
                    ini akan restore database dari : 
                    <span class="font-mono text-primary">{{ $selectedBackup }}</span>
                </p>

                <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 mb-6">
                    <p class="text-sm text-red-300">
                        <strong>⚠️ Warning:</strong> Ini akan menimpa semua data. Pastikan punya backup sebelum dijalankan.
                    </p>
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('showRestoreModal', false)" 
                            class="flex-1 px-4 py-3 rounded-lg border border-[#3E4C59] text-gray-300 hover:text-white font-bold transition">
                        Cancel
                    </button>
                    <button wire:click="restore"
                            class="flex-1 px-4 py-3 rounded-lg bg-yellow-600 text-white font-bold hover:bg-yellow-500 transition">
                        Restore Sekarang
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- LOADING OVERLAY --}}
    <div wire:loading.flex wire:target="createBackup, restore" class="fixed inset-0 z-50 items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="flex flex-col items-center gap-4">
            <span class="material-symbols-outlined text-primary text-6xl animate-spin">sync</span>
            <p class="text-white text-xl font-bold">Memproses...</p>
            <p class="text-slate-400">Harap tunggu beberapa saat.</p>
        </div>
    </div>
</div>
