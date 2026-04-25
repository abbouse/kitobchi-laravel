<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class CourierBanLog extends Model
{
    use HasFactory, Notifiable;

    public const TYPE_WARNING = 'warning';
    public const TYPE_UNBAN = 'unban';

    protected $table = 'courier_ban_logs';

    protected $fillable = [
        'courier_id',
        'title',
        'message',
        'type',
        'is_read',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relationship with Courier
     */
    public function courier()
    {
        return $this->belongsTo(Couriers::class, 'courier_id');
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

    public static function getUnreadCount($courierId)
    {
        return self::where('courier_id', $courierId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Active warnings = warnings issued AFTER the most recent unban (or all warnings if never unbanned).
     * This way unblocking resets the counter automatically.
     */
    public static function activeWarningsQuery($courierId)
    {
        $lastUnbanAt = self::where('courier_id', $courierId)
            ->where('type', self::TYPE_UNBAN)
            ->max('created_at');

        return self::where('courier_id', $courierId)
            ->where('type', self::TYPE_WARNING)
            ->when($lastUnbanAt, fn ($query) => $query->where('created_at', '>', $lastUnbanAt));
    }

    public static function getWarningCount($courierId)
    {
        return self::activeWarningsQuery($courierId)->count();
    }

    public static function hasReachedBlockThreshold($courierId, int $threshold = 3): bool
    {
        return self::getWarningCount($courierId) >= $threshold;
    }
}