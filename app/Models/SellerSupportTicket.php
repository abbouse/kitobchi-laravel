<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerSupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'admin_id',
        'subject',
        'status',
        'last_message_at',
        'seller_unread_count',
        'admin_unread_count',
        'closed_at',
        'close_reason',
        'first_response_at',
        'feedback',
        'feedback_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'first_response_at' => 'datetime',
        'feedback_at' => 'datetime',
        'seller_unread_count' => 'integer',
        'admin_unread_count' => 'integer',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SellerSupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(SellerSupportTicketMessage::class, 'ticket_id')->latestOfMany();
    }
}
