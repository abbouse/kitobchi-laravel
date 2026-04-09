<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotTicket extends Model
{
    use HasFactory;
    
    public function operator()
    {
        return $this->belongsTo(BotOperator::class, 'operator_id', 'telegram_id');
    }
    public function attachments()
    {
        return $this->belongsTo(BotTicketAttachment::class, 'ticket_id', 'id');
    }
}
