<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';

    protected $fillable = [
        'kode_member',
        'kendaraan_id',
        'tier_member_id',
        'tanggal_mulai',
        'tanggal_berakhir',
        'status'
    ];

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class);
    }

    public function tier()
    {
        return $this->belongsTo(TierMember::class, 'tier_member_id');
    }
}

