<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifParkir extends Model
{
    protected $table = 'tarif_parkir';

    protected $fillable = [
        'tipe_kendaraan_id',
        'durasi_min',
        'durasi_max',
        'tarif',
    ];

    /* ========= RELATION ========= */
    public function tipeKendaraan()
    {
        return $this->belongsTo(TipeKendaraan::class);
    }
}
