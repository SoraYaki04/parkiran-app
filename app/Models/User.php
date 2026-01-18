<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // ===== RELASI =====
    
    /**
     * Relasi ke Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // ===== HELPER METHODS =====
    
    /**
     * Get role name
     */
    public function getRoleNameAttribute(): string
    {
        return $this->role ? $this->role->name : 'petugas';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role_id === 1;
    }

    /**
     * Check if user is petugas
     */
    public function isPetugas(): bool
    {
        return $this->role_id === 2;
    }

    /**
     * Check if user is owner
     */
    public function isOwner(): bool
    {
        return $this->role_id === 3;
    }

    /**
     * Check if user is active
     * NOTE: Status di database Anda 'aktif' bukan 'active'
     */
    public function isActive(): bool
    {
        return $this->status === 'aktif'; // Sesuai dengan database Anda
    }

    /**
     * Determine if the user's password has been confirmed recently.
     */
    public function isPasswordConfirmed(): bool
    {
        $confirmedAt = session('auth.password_confirmed_at');
        
        if (!$confirmedAt) {
            return false;
        }

        return (time() - $confirmedAt) < 10800; // 3 jam
    }

    // ===== SCOPES =====
    
    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Scope for admins
     */
    public function scopeAdmins($query)
    {
        return $query->where('role_id', 1);
    }

    /**
     * Scope for petugas
     */
    public function scopePetugas($query)
    {
        return $query->where('role_id', 2);
    }

    /**
     * Scope for owners
     */
    public function scopeOwners($query)
    {
        return $query->where('role_id', 3);
    }
}