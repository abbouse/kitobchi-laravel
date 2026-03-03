<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessageItem extends Model
{
    protected $fillable = [
        'chat_message_id',
        'product_id',
        'type',
    ];

    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function product()
    {
        return $this->belongsTo(Books::class, 'product_id');
    }
}
