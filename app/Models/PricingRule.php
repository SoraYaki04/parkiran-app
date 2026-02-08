<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tier',
        'vehicle_type_id',
        'priority',
        'start_date',
        'end_date',
        'days_of_week',
        'type',
        'config',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vehicleType()
    {
        return $this->belongsTo(TipeKendaraan::class, 'vehicle_type_id');
    }
}
