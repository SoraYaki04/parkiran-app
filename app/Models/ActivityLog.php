<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'action',
        'category',
        'target',
        'description',
    ];

    /* ======================
        RELATIONS
    =======================*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ======================
        SCOPES (FILTER)
    =======================*/

    public function scopeAction($query, $action)
    {
        return $query->when($action, fn ($q) =>
            $q->where('action', $action)
        );
    }

    public function scopeCategory($query, $category)
    {
        return $query->when($category, fn ($q) =>
            $q->where('category', $category)
        );
    }

    public function scopeUser($query, $userId)
    {
        return $query->when($userId, fn ($q) =>
            $q->where('user_id', $userId)
        );
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->when($from && $to, fn ($q) =>
            $q->whereBetween('created_at', [$from, $to])
        );
    }

    /* ======================
        CONSTANTS (OPTIONAL)
    =======================*/

    public const ACTION_LOGIN            = 'LOGIN';
    public const ACTION_LOGOUT           = 'LOGOUT';
    public const ACTION_CREATE_USER      = 'CREATE_USER';
    public const ACTION_UPDATE_USER      = 'UPDATE_USER';
    public const ACTION_DELETE_USER      = 'DELETE_USER';

    public const ACTION_TRANSAKSI_MASUK   = 'TRANSAKSI_MASUK';
    public const ACTION_TRANSAKSI_KELUAR  = 'TRANSAKSI_KELUAR';
    public const ACTION_CETAK_STRUK      = 'CETAK_STRUK';

    public const CATEGORY_SYSTEM         = 'SYSTEM';
    public const CATEGORY_MASTER         = 'MASTER';
    public const CATEGORY_TRANSAKSI      = 'TRANSAKSI';
    public const CATEGORY_PEMBAYARAN     = 'PEMBAYARAN';
}
