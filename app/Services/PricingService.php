<?php

namespace App\Services;

use App\Models\ParkirSessions;
use App\Models\PricingRule;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate parking fee based on active rules.
     */
    public function calculate(ParkirSessions $session): array
    {
        $start = Carbon::parse($session->confirmed_at ?? $session->created_at);
        $end = now();
        $durationMinutes = max(1, $start->diffInMinutes($end));

        // 1. Find the best applicable rule
        $rule = $this->findApplicableRule($session, $start);

        $basePrice = 0;
        $appliedRuleName = 'Default Rate';

        if ($rule) {
            $basePrice = $this->applyRule($rule, $durationMinutes);
            $appliedRuleName = $rule->name;
        } else {
            // Fallback to legacy or simple calculation if no rule found
            // For now, let's assume a default flat rate if no rule matches
            $basePrice = 3000; 
        }

        // 2. Calculate Member Discount
        $discountData = $this->calculateMemberDiscount($session, $basePrice);
        $finalPrice = max(0, $basePrice - $discountData['nominal']);

        return [
            'base_price' => $basePrice,
            'discount_percent' => $discountData['percent'],
            'discount_nominal' => $discountData['nominal'],
            'final_price' => $finalPrice,
            'duration_minutes' => $durationMinutes,
            'applied_rule' => $appliedRuleName,
        ];
    }

    private function findApplicableRule(ParkirSessions $session, Carbon $date): ?PricingRule
    {
        // Fetch active rules sorted by priority (higher first)
        $rules = PricingRule::where('is_active', true)
            ->where(function ($q) use ($session) {
                $q->whereNull('vehicle_type_id')
                  ->orWhere('vehicle_type_id', $session->tipe_kendaraan_id);
            })
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            // Check Date Range
            if ($rule->start_date && $date->lt($rule->start_date)) continue;
            if ($rule->end_date && $date->gt($rule->end_date)) continue;

            // Check Day of Week
            if ($rule->days_of_week && !in_array($date->dayOfWeek, $rule->days_of_week)) continue;

            return $rule;
        }

        return null;
    }

    private function applyRule(PricingRule $rule, int $minutes): int
    {
        $config = $rule->config ?? [];

        switch ($rule->type) {
            case 'FLAT':
                return (int) ($config['price'] ?? 0);

            case 'DURATION_BASED':
                // expect config: [{'max': 60, 'price': 5000}, {'max': 120, 'price': 8000}, ...]
                // Sort by max duration
                $tiers = collect($config['tiers'] ?? [])->sortBy('max');
                
                foreach ($tiers as $tier) {
                    if ($minutes <= $tier['max']) {
                        return (int) $tier['price'];
                    }
                }
                // If exceeds all tiers, take the last one or a max daily cap
                return (int) ($tiers->last()['price'] ?? 0);

            case 'TIME_BASED':
                // Simple implementation: hourly rate
                // config: {'first_hour': 5000, 'next_hour': 3000, 'max_daily': 25000}
                $firstHourPrice = (int) ($config['first_hour'] ?? 3000);
                $nextHourPrice = (int) ($config['next_hour'] ?? 2000);
                $maxDaily = (int) ($config['max_daily'] ?? 100000);

                $hours = ceil($minutes / 60);
                
                if ($hours <= 1) {
                    $total = $firstHourPrice;
                } else {
                    $total = $firstHourPrice + (($hours - 1) * $nextHourPrice);
                }

                return min($total, $maxDaily);

            default:
                return 0;
        }
    }

    private function calculateMemberDiscount(ParkirSessions $session, int $basePrice): array
    {
        // Fetch vehicle with member relation
        $vehicle = $session->kendaraan; // Assumption: Accessing relation via model
        
        // Safety check if relation is not loaded or null
        if (!$vehicle) return ['percent' => 0, 'nominal' => 0];

        $member = $vehicle->member;
        
        if (!$member || $member->status !== 'aktif') {
            return ['percent' => 0, 'nominal' => 0];
        }

        // Check if member is within active period
        if (!now()->between($member->tanggal_mulai, $member->tanggal_berakhir)) {
            return ['percent' => 0, 'nominal' => 0];
        }

        $tier = $member->tier;
        if (!$tier) return ['percent' => 0, 'nominal' => 0];

        $percent = $tier->diskon_persen;
        $nominal = round($basePrice * ($percent / 100));

        return [
            'percent' => $percent,
            'nominal' => $nominal,
        ];
    }
}
