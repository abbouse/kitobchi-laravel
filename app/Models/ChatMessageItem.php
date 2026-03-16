<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessageItem extends Model
{
    protected $fillable = [
        'chat_message_id',
        'product_id',
        'product_type',
        'type',
    ];

    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function product()
    {
        return $this->morphTo('product', 'product_type', 'product_id');
    }
}
