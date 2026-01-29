<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TierMember extends Model
{
    use HasFactory;

    protected $table = 'tier_member';

    protected $fillable = [
        'nama',
        'harga',
        'periode',
        'diskon_persen',
        'masa_berlaku_hari',
        'status',
    ];

    // 1 tier dipakai banyak member
    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
