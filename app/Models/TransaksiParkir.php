<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransaksiParkir extends Model
{
    use HasFactory;

    protected $table = 'transaksi_parkir';

    protected $fillable = [
        'kode_karcis',
        'kendaraan_id',
        'slot_parkir_id',
        'tipe_kendaraan_id',
        'waktu_masuk',
        'waktu_keluar',
        'durasi_menit',
        'total_bayar',
        'member_id',
        'operator',
    ];

    protected $casts = [
        'waktu_masuk' => 'datetime',
        'waktu_keluar' => 'datetime',
    ];

    // transaksi milik kendaraan
    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class);
    }

    public function slotParkir()
    {
        return $this->belongsTo(SlotParkir::class, 'slot_parkir_id');
    }

    // transaksi bisa punya member (opsional)
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // transaksi punya tipe kendaraan
    public function tipeKendaraan()
    {
        return $this->belongsTo(TipeKendaraan::class);
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'transaksi_parkir_id');
    }
}
