<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaKapasitas extends Model
{
    protected $table = 'area_kapasitas';

    protected $fillable = [
        'area_id',
        'tipe_kendaraan_id',
        'kapasitas'
    ];

    public function tipeKendaraan()
    {
        return $this->belongsTo(TipeKendaraan::class);
    }

    public $timestamps = false;
}
