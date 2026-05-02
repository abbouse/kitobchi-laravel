<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotTicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'file_id',
        'file_type',
        'file_name',
        'file_size',
        'sent_by',
    ];

    public function ticket()
    {
        return $this->belongsTo(BotTicket::class, 'ticket_id');
    }
}
