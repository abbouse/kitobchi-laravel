<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotTicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'sent_by',
        'operator_id',
        'admin_id',
        'telegram_actor_id',
        'message_type',
        'message',
        'telegram_message_id',
        'is_delivered',
        'delivery_error',
    ];

    protected $casts = [
        'telegram_message_id' => 'integer',
        'telegram_actor_id' => 'integer',
        'operator_id' => 'integer',
        'admin_id' => 'integer',
        'is_delivered' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(BotTicket::class, 'ticket_id');
    }

    public function operator()
    {
        return $this->belongsTo(BotOperator::class, 'operator_id', 'telegram_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
