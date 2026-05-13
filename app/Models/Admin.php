<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'permissions',
        'is_active',
        'last_ip',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'permissions'   => 'array',
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
        'password'      => 'hashed',
    ];

    // ── Role helpers ─────────────────────────────────
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    /**
     * Permission tekshirish
     * superadmin — hamma narsaga ruxsat
     * admin/moderator — permissions[] array ga qarab
     *
     * ESLATMA: Laravel'ning can() metodi bilan to'qnashuv bo'lgani uchun
     *          hasPermission() deb nomlandi.
     *          Middleware ichida: $admin->hasPermission('books')
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) return true;

        return in_array($permission, $this->permissions ?? []);
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'Super Admin',
            'admin'      => 'Admin',
            'moderator'  => 'Moderator',
            default      => $this->role,
        };
    }

    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'danger',
            'admin'      => 'accent',
            'moderator'  => 'warning',
            default      => 'muted',
        };
    }

    public function hubStaffRoles()
    {
        return $this->morphMany(HubStaff::class, 'staffable');
    }
}
