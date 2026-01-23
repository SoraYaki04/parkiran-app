<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    protected $table = 'kendaraan';

    protected $fillable = [
        'plat_nomor',
        'tipe_kendaraan_id',
        'nama_pemilik',
        'status',
        'slot_parkir_id'
    ];

    public function tipeKendaraan()
    {
        return $this->belongsTo(TipeKendaraan::class, 'tipe_kendaraan_id');
    }

    public function slot()
    {
        return $this->belongsTo(SlotParkir::class, 'slot_parkir_id');
    }
}
