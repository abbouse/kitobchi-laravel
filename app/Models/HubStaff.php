<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class HubStaff extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'hub_id',
        'staffable_type',
        'staffable_id',
        'username',
        'full_name',
        'phone_number',
        'password',
        'role',
        'is_active',
        'permissions',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        // Xodim o'chirilsa (yoki hubi almashtirilsa) — ilovadagi sessiyasi darhol yopiladi
        static::updated(function (HubStaff $staff) {
            if (($staff->wasChanged('is_active') && ! $staff->is_active) || $staff->wasChanged('hub_id') || $staff->wasChanged('password')) {
                $staff->tokens()->delete();
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
        'permissions' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function staffable()
    {
        return $this->morphTo();
    }
}
