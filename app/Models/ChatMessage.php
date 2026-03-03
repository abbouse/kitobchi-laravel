<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'message', 'response', 'is_ai', 'is_read'];

    protected $casts = [
        'response' => 'array',  // JSON avto arrayga aylantiriladi
        'is_ai' => 'boolean',
        'is_read' => 'boolean',
    ];
    
    public function items()
    {
        return $this->hasMany(ChatMessageItem::class, 'chat_message_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}