<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'sender_type', 'reply_to_id', 'message', 'is_read', 'is_deleted', 'is_edited', 'hidden_by'];
    
    protected $casts = [
        'is_read' => 'boolean',
        'is_deleted' => 'boolean',
        'is_edited' => 'boolean'
    ];

    public function sender()
    {
        return $this->morphTo();
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
    public function replyTo()
{
    return $this->belongsTo(Message::class, 'reply_to_id');
}
}