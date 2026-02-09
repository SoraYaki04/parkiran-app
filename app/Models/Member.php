<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    protected $table = 'member';

    protected $fillable = [
        'kode_member',
        'nama',
        'no_hp',
        'tier_member_id',
        'tanggal_mulai',
        'tanggal_berakhir',
        'status'
    ];

    /**
     * Member punya banyak kendaraan (multi-plat)
     */
    public function kendaraan()
    {
        return $this->hasMany(Kendaraan::class);
    }

    public function tier()
    {
        return $this->belongsTo(TierMember::class, 'tier_member_id');
    }
}
