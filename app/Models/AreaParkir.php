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
        'kapasitas_total',
        'status', // <-- WAJIB ditambahkan
    ];

    /* ==========================
     | RELATIONS
     ========================== */

    public function kapasitas()
    {
        return $this->hasMany(AreaKapasitas::class, 'area_id');
    }

    public function slots()
    {
        return $this->hasMany(SlotParkir::class, 'area_id');
    }
}
