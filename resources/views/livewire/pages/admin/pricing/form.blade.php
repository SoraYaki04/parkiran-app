<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\PricingRule;
use App\Models\TipeKendaraan;
use App\Models\ActivityLog;

new #[Layout('layouts.app')]
#[Title('Manage Pricing Rule')]
class extends Component {
    
    public ?PricingRule $rule = null;
    
    // Form Fields
    public $name;
    public $vehicle_type_id;
    public $priority = 0;
    public $start_date;
    public $end_date;
    public $days_of_week = [];
    public $type = 'TIME_BASED'; 
    public $is_active = true;

    // Config Fields
    public $config_time_based = [
        'first_hour' => 3000,
        'next_hour' => 2000,
        'max_daily' => 50000,
    ];

    public $config_flat = [
        'price' => 5000,
    ];

    public $config_tiers = [
        ['max' => 60, 'price' => 2000]
    ];

    public function mount($id = null)
    {
        if ($id) {
            $this->rule = PricingRule::findOrFail($id);
            $this->name = $this->rule->name;
            $this->vehicle_type_id = $this->rule->vehicle_type_id;
            $this->priority = $this->rule->priority;
            $this->start_date = $this->rule->start_date?->format('Y-m-d');
            $this->end_date = $this->rule->end_date?->format('Y-m-d');
            $this->days_of_week = $this->rule->days_of_week ?? [];
            $this->type = $this->rule->type;
            $this->is_active = $this->rule->is_active;

            // Load Config
            $config = $this->rule->config ?? [];
            if ($this->type === 'TIME_BASED') $this->config_time_based = array_merge($this->config_time_based, $config);
            if ($this->type === 'FLAT') $this->config_flat = array_merge($this->config_flat, $config);
            if ($this->type === 'DURATION_BASED') $this->config_tiers = $config['tiers'] ?? [['max' => 60, 'price' => 2000]];
        }
    }

    public function addTier()
    {
        $this->config_tiers[] = ['max' => 60, 'price' => 0];
    }

    public function removeTier($index)
    {
        unset($this->config_tiers[$index]);
        $this->config_tiers = array_values($this->config_tiers);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required',
            'priority' => 'required|integer',
        ]);

        $config = [];
        if ($this->type === 'TIME_BASED') $config = $this->config_time_based;
        if ($this->type === 'FLAT') $config = $this->config_flat;
        if ($this->type === 'DURATION_BASED') $config = ['tiers' => $this->config_tiers];

        $data = [
            'name' => $this->name,
            'vehicle_type_id' => $this->vehicle_type_id ?: null,
            'priority' => $this->priority,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'days_of_week' => !empty($this->days_of_week) ? $this->days_of_week : null,
            'type' => $this->type,
            'config' => $config,
            'is_active' => (bool)$this->is_active,
        ];

        if ($this->rule) {
            $this->rule->update($data);
            $action = 'UPDATE_RULE';
            $message = 'Rule updated successfully!';
        } else {
            PricingRule::create($data);
            $action = 'CREATE_RULE';
            $message = 'Rule created successfully!';
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => "Saved pricing rule: {$this->name} ({$this->type})",
            'category' => 'MASTER'
        ]);

        session()->flash('success', $message);
        return $this->redirect(route('admin.pricing.index'), navigate: true);
    }

    public function getVehicleTypesProperty()
    {
        return TipeKendaraan::all();
    }
};
?>

<div class="flex-1 flex flex-col h-full overflow-hidden">
    {{-- HEADER --}}
    <header class="px-8 py-6 border-b border-gray-800 flex justify-between items-center flex-shrink-0">
        <div>
            <h2 class="text-white text-3xl font-black">{{ $rule ? 'Edit Rule' : 'New Rule' }}</h2>
            <p class="text-slate-400">Configure pricing logic details</p>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-8 scrollbar-hide">
        <form wire:submit="save" class="max-w-4xl space-y-8 pb-20">
            
            {{-- BASIC INFO --}}
            <div class="bg-surface-dark p-6 rounded-2xl border border-[#3E4C59] space-y-6">
                <h3 class="text-lg font-bold text-white mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Rule Name</label>
                        <input wire:model="name" type="text" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white focus:border-primary focus:ring-1 focus:ring-primary" placeholder="e.g. Weekend Special">
                        @error('name') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Vehicle Type</label>
                        <select wire:model="vehicle_type_id" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                            <option value="">All Vehicles</option>
                            @foreach($this->vehicleTypes as $vt)
                                <option value="{{ $vt->id }}">{{ $vt->nama_tipe }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Pricing Type</label>
                        <select wire:model.live="type" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                            <option value="TIME_BASED">Time Based (Hourly)</option>
                            <option value="DURATION_BASED">Duration Based (Tiers)</option>
                            <option value="FLAT">Flat Rate</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Priority</label>
                        <input wire:model="priority" type="number" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white" placeholder="Higher number = Higher priority">
                        <p class="text-xs text-slate-500 mt-1">Rules with higher priority apply first.</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            <span class="ml-3 text-sm font-medium text-white">Active Status</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- CONDITIONS --}}
            <div class="bg-surface-dark p-6 rounded-2xl border border-[#3E4C59] space-y-6">
                <h3 class="text-lg font-bold text-white mb-4">Conditions (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Start Date</label>
                        <input wire:model="start_date" type="date" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">End Date</label>
                        <input wire:model="end_date" type="date" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Days of Week</label>
                        <div class="flex gap-2 flex-wrap">
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $idx => $day)
                                <label class="cursor-pointer">
                                    <input type="checkbox" wire:model="days_of_week" value="{{ $idx }}" class="sr-only peer">
                                    <div class="px-4 py-2 rounded-lg bg-gray-900 border border-[#3E4C59] text-slate-400 peer-checked:bg-primary peer-checked:text-black peer-checked:border-primary transition font-bold text-sm">
                                        {{ $day }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONFIGURATION --}}
            <div class="bg-surface-dark p-6 rounded-2xl border border-[#3E4C59] space-y-6">
                <h3 class="text-lg font-bold text-white mb-4">
                    Configuration: {{ str_replace('_', ' ', $type) }}
                </h3>

                @if($type === 'TIME_BASED')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">First Hour Price</label>
                            <input wire:model="config_time_based.first_hour" type="number" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">Next Hour Price</label>
                            <input wire:model="config_time_based.next_hour" type="number" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">Max Daily Cap</label>
                            <input wire:model="config_time_based.max_daily" type="number" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                        </div>
                    </div>
                @endif

                @if($type === 'FLAT')
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Flat Rate Price</label>
                        <input wire:model="config_flat.price" type="number" class="w-full bg-gray-900 border border-[#3E4C59] rounded-lg px-4 py-3 text-white">
                    </div>
                @endif

                @if($type === 'DURATION_BASED')
                    <div class="space-y-4">
                        @foreach($config_tiers as $index => $tier)
                            <div class="flex items-center gap-4 bg-gray-900 p-4 rounded-lg border border-[#3E4C59]">
                                <div class="flex-1">
                                    <label class="text-xs text-slate-500 uppercase font-bold">Max Duration (Minutes)</label>
                                    <input wire:model="config_tiers.{{ $index }}.max" type="number" class="w-full bg-transparent border-b border-slate-700 text-white focus:outline-none focus:border-primary py-2">
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs text-slate-500 uppercase font-bold">Price (Rp)</label>
                                    <input wire:model="config_tiers.{{ $index }}.price" type="number" class="w-full bg-transparent border-b border-slate-700 text-white focus:outline-none focus:border-primary py-2">
                                </div>
                                <button type="button" wire:click="removeTier({{ $index }})" class="p-2 text-red-400 hover:bg-red-500/10 rounded-full">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        @endforeach

                        <button type="button" wire:click="addTier" class="w-full py-3 border-2 border-dashed border-[#3E4C59] rounded-lg text-slate-400 hover:text-white hover:border-slate-500 transition flex items-center justify-center gap-2 font-bold">
                            <span class="material-symbols-outlined">add</span>
                            Add Tier
                        </button>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-4 pt-6 text-right">
                <a href="{{ route('admin.pricing.index') }}" wire:navigate class="px-6 py-3 rounded-lg border border-[#3E4C59] text-gray-300 hover:text-white font-bold">
                    Cancel
                </a>
                <button type="submit" class="px-8 py-3 rounded-lg bg-primary text-black font-bold hover:bg-primary-400 shadow-lg shadow-primary/25 transition-all">
                    Save Configuration
                </button>
            </div>
            
        </form>
    </div>
</div>
