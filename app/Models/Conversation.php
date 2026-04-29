<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'title',
        'avatar',
        'created_by_id',
        'user_id',
        'receiver_id',
        'shop_id',
        'last_message_at',
        'hidden_by',
        'messages_hidden_at',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function shop()
    {
        return $this->belongsTo(Seller::class, 'shop_id');
    }
    public function shopOwner()
{
    return $this->belongsTo(Seller::class, 'shop_id');
}

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function participantUsers()
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->withPivot(['role', 'muted_until', 'last_read_at', 'joined_at'])
            ->withTimestamps();
    }
}
