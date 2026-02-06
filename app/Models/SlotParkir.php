<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlotParkir extends Model
{
    protected $table = 'slot_parkir';

    protected $fillable = [
        'area_id',
        'kode_slot',
        'baris',
        'kolom',
        'tipe_kendaraan_id',
        'status'
    ];

    public function area()
    {
        return $this->belongsTo(AreaParkir::class, 'area_id');
    }
}