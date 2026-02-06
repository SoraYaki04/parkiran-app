<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaParkir extends Model
{
    protected $table = 'area_parkir';

    protected $fillable = [
        'kode_area',
        'nama_area',
        'lokasi_fisik',
        'kapasitas_total'
    ];

    public function kapasitas()
    {
        return $this->hasMany(AreaKapasitas::class, 'area_id');
    }

    public function slots()
    {
        return $this->hasMany(SlotParkir::class, 'area_id');
    }

    public function getStatusAttribute()
    {
        $total = $this->slots()->count();
        $terisi = $this->slots()->where('status', 'terisi')->count();

        if ($total === 0) return 'Maintenance';
        if ($terisi >= $total) return 'Full';
        return 'Available';
    }

}
