<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipeKendaraan extends Model
{
    protected $table = 'tipe_kendaraan';

    protected $fillable = [
        'kode_tipe',
        'nama_tipe',
    ];

    public $timestamps = false;
}

