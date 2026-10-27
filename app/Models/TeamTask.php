<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamTask extends Model
{
    public const STATUSES = [
        'todo' => 'Bajarilishi kerak',
        'doing' => 'Jarayonda',
        'done' => 'Bajarildi',
        'cancelled' => 'Bekor qilindi',
    ];

    public const PRIORITIES = [
        'low' => 'Past',
        'normal' => "O'rta",
        'high' => 'Shoshilinch',
    ];

    protected $fillable = [
        'title', 'description', 'status', 'priority', 'due_at',
        'assignee_admin_id', 'created_by_admin_id', 'order_id', 'source',
        'completed_at', 'reminded_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assignee_admin_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['todo', 'doing'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->completed_at = $status === 'done' ? now() : null;
        $this->save();
    }
}
