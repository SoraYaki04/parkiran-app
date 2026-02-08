<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\PricingRule;
use App\Models\TipeKendaraan;
use App\Models\ActivityLog;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('Pricing Rules')]
class extends Component {
    use WithPagination;

    public $filterType = '';
    
    public function getRulesProperty()
    {
        return PricingRule::with('vehicleType')
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate(10);
    }

    public function delete($id)
    {
        $rule = PricingRule::findOrFail($id);
        $rule->delete();
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'DELETE_RULE',
            'description' => "Deleted pricing rule: {$rule->name}",
            'category' => 'MASTER'
        ]);

        $this->dispatch('notify', message: 'Rule removed!', type: 'success');
    }

    public function toggleActive($id)
    {
        $rule = PricingRule::findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);
        
        $this->dispatch('notify', message: 'Status updated!', type: 'success');
    }
};
?>

<div class="flex-1 flex flex-col h-full overflow-hidden">
    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-end flex-shrink-0">
        <div>
            <h2 class="text-white text-3xl font-black">Pricing Rules</h2>
            <p class="text-slate-400">Manage dynamic pricing logic</p>
        </div>

        <a href="{{ route('admin.pricing.create') }}" wire:navigate
                class="flex items-center gap-2 bg-primary text-black px-5 py-2.5 rounded-lg font-bold hover:bg-primary-400 transition">
            <span class="material-symbols-outlined">add</span>
            Add New Rule
        </a>
    </header>

    {{-- FILTER --}}
    <div class="px-8 pt-6 flex-shrink-0">
        <div class="bg-surface-dark p-5 rounded-xl border border-[#3E4C59] flex gap-4">
            <select wire:model.live="filterType"
                    class="bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-2 text-white">
                <option value="">All Types</option>
                <option value="TIME_BASED">Time Based</option>
                <option value="DURATION_BASED">Duration Based</option>
                <option value="FLAT">Flat Rate</option>
            </select>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="flex-1 overflow-y-auto px-8 py-6 scrollbar-hide">
        <div class="bg-surface-dark border border-[#3E4C59] rounded-xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-900 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Vehicle</th>
                        <th class="px-6 py-4">Priority</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#3E4C59]">
                    @forelse($this->rules as $rule)
                        <tr class="hover:bg-surface-hover transition group">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white">{{ $rule->name }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $rule->start_date?->format('d M') ?? 'Any' }} - {{ $rule->end_date?->format('d M') ?? 'Any' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase
                                    {{ match($rule->type) {
                                        'TIME_BASED' => 'bg-blue-500/20 text-blue-400',
                                        'DURATION_BASED' => 'bg-purple-500/20 text-purple-400',
                                        'FLAT' => 'bg-emerald-500/20 text-emerald-400',
                                        default => 'bg-slate-700 text-slate-400'
                                    } }}">
                                    {{ str_replace('_', ' ', $rule->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $rule->vehicleType?->nama_tipe ?? 'All Types' }}
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $rule->priority }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="toggleActive({{ $rule->id }})"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-gray-900 {{ $rule->is_active ? 'bg-primary' : 'bg-gray-700' }}">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $rule->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                                    <a href="{{ route('admin.pricing.edit', $rule->id) }}" wire:navigate
                                       class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/5">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <button wire:click="delete({{ $rule->id }})" 
                                            onclick="return confirm('Delete this rule?')"
                                            class="p-2 text-slate-400 hover:text-red-400 rounded-lg hover:bg-red-500/10">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">
                                No pricing rules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="px-8 pb-6">
        {{ $this->rules->links() }}
    </div>
</div>
