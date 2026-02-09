<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipeKendaraan extends Model
{
    use SoftDeletes;

    protected $table = 'tipe_kendaraan';

    protected $fillable = [
        'kode_tipe',
        'nama_tipe',
    ];

    public function areaKapasitas()
    {
        return $this->hasMany(\App\Models\AreaKapasitas::class, 'tipe_kendaraan_id');
    }

    public function tarifParkir()
    {
        return $this->hasMany(\App\Models\TarifParkir::class, 'tipe_kendaraan_id');
    }

}



