<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class CourierBanLog extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'seller_id',
        'title',
        'message',
        'type',
        'is_read',
        'data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relationship with Seller
     */
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Scope for unread ban logs
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read ban logs
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Mark ban log as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }
    public static function getUnreadCount($sellerId)
    {
        return self::where('courier_id', $sellerId)
                  ->where('is_read', false)
                  ->count();
    }
    public static function getWarningCount($sellerId)
    {
        return self::where('courier_id', $sellerId)
                  ->where('type', 'warning')
                  ->count();
    }
}