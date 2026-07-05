<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiWebhook extends Model
{
    protected $fillable = [
        'api_client_id',
        'url',
        'events',
        'secret',
        'is_active',
        'last_delivered_at',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_delivered_at' => 'datetime',
        'failure_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApiWebhook $hook) {
            $hook->secret = $hook->secret ?: Str::random(48);
        });
    }

    public function client()
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }

    /** Ushbu webhook berilgan hodisaga obuna bo'lganmi ("*" — barchasi). */
    public function subscribedTo(string $event): bool
    {
        $events = $this->events ?? [];

        return in_array('*', $events, true) || in_array($event, $events, true);
    }
}
