<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'transaksi_parkir_id',
        'tarif_dasar',
        'total_bayar',
        'metode_pembayaran',
        'jumlah_bayar',
        'kembalian',
        'tanggal_bayar',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
        'tarif_dasar' => 'integer',
        'total_bayar' => 'integer',
    ];

    /**
     * Relasi ke Transaksi Parkir
     */
    public function transaksiParkir(): BelongsTo
    {
        return $this->belongsTo(TransaksiParkir::class, 'transaksi_parkir_id');
    }
}