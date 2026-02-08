<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkirSessions extends Model
{
    protected $table = 'parkir_sessions';

    protected $fillable = [
        'token',
        'tipe_kendaraan_id',
        'plat_nomor',
        'slot_parkir_id',
        'status',
        'generated_at',
        'expired_at',
        'exit_token',
        'confirmed_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];


    public function slot()
    {
        return $this->belongsTo(SlotParkir::class, 'slot_parkir_id');
    }

    public function tipeKendaraan()
    {
        return $this->belongsTo(TipeKendaraan::class, 'tipe_kendaraan_id');
    }

    public function kendaraan()
    {
        return $this->hasOne(Kendaraan::class, 'plat_nomor', 'plat_nomor');
    }

}
