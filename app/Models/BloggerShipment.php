<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloggerShipment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'blogger_id',
        'scheduled_for',
        'status',
        'delivered_at',
        'note',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_color',
        'items_summary',
    ];

    public function blogger(): BelongsTo
    {
        return $this->belongsTo(Blogger::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BloggerShipmentItem::class)->orderBy('position')->orderBy('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_DELIVERED ? 'Yetkazildi' : 'Yetkazilmadi';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status === self::STATUS_DELIVERED ? 'badge-success' : 'badge-warning';
    }

    public function getItemsSummaryAttribute(): string
    {
        return $this->items->pluck('name')->filter()->implode(', ');
    }

    public function markDelivered(): void
    {
        $this->forceFill([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ])->save();
    }

    public function markPending(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PENDING,
            'delivered_at' => null,
        ])->save();
    }

    public function scheduledForDisplay(string $format = 'd.m.Y H:i'): string
    {
        return $this->scheduled_for instanceof CarbonInterface
            ? $this->scheduled_for->format($format)
            : '—';
    }
}
