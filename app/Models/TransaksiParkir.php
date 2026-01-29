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
        'tipe_kendaraan_id',
        'waktu_masuk',
        'waktu_keluar',
        'durasi_menit',
        'total_bayar',
        'member_id',
        'status',
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
}
