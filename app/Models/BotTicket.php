<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_type',
        'source_conversation_id',
        'username',
        'name',
        'operator_id',
        'status',
        'first_msg',
        'rating',
        'close_reason',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'rating' => 'integer',
        'source_conversation_id' => 'integer',
    ];
    
    public function operator()
    {
        return $this->belongsTo(BotOperator::class, 'operator_id', 'telegram_id');
    }

    public function attachments()
    {
        return $this->hasMany(BotTicketAttachment::class, 'ticket_id')->latest('id');
    }

    public function messages()
    {
        return $this->hasMany(BotTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(BotTicketMessage::class, 'ticket_id')->latestOfMany();
    }
}
