<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SellerBanLog extends Model
{
    use HasFactory, Notifiable;

    public const TYPE_WARNING = 'warning';
    public const TYPE_UNBAN = 'unban';

    protected $table = 'seller_ban_logs';

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
        return self::where('seller_id', $sellerId)
                  ->where('is_read', false)
                  ->count();
    }

    public static function activeWarningsQuery($sellerId)
    {
        $lastUnbanAt = self::where('seller_id', $sellerId)
            ->where('type', self::TYPE_UNBAN)
            ->max('created_at');

        return self::where('seller_id', $sellerId)
            ->where('type', self::TYPE_WARNING)
            ->when($lastUnbanAt, fn ($query) => $query->where('created_at', '>', $lastUnbanAt));
    }

    public static function getWarningCount($sellerId)
    {
        return self::activeWarningsQuery($sellerId)->count();
    }

    public static function hasReachedBlockThreshold($sellerId, int $threshold = 3): bool
    {
        return self::getWarningCount($sellerId) >= $threshold;
    }
}
