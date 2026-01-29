<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kendaraan extends Model
{
    use HasFactory;

    protected $table = 'kendaraan';

    protected $fillable = [
        'plat_nomor',
        'tipe_kendaraan_id',
        'nama_pemilik',
        'status',
        'slot_parkir_id'
    ];

    // Kendaraan punya 1 tipe
    public function tipeKendaraan()
    {
        return $this->belongsTo(TipeKendaraan::class);
    }

    // Kendaraan bisa menempati 1 slot (opsional)
    public function slotParkir()
    {
        return $this->belongsTo(SlotParkir::class, 'slot_parkir_id');
    }

    // Kendaraan punya banyak transaksi parkir
    public function transaksiParkir()
    {
        return $this->hasMany(TransaksiParkir::class);
    }

    // Kendaraan bisa punya 1 member
    public function member()
    {
        return $this->hasOne(Member::class);
    }
}
