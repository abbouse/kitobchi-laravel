<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'muted_until',
        'last_read_at',
        'joined_at',
    ];

    protected $casts = [
        'muted_until' => 'datetime',
        'last_read_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function getIsMutedAttribute(): bool
    {
        return $this->muted_until === null || $this->muted_until->isFuture();
    }
}
