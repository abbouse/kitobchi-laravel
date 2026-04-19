<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerApplication extends Model
{
    public const TYPE_VACANCY = 'vacancy';

    public const TYPE_INQUIRY = 'inquiry';

    public const STATUS_NEW = 'new';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_REPLIED = 'replied';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'type',
        'vacancy_id',
        'full_name',
        'email',
        'telegram_username',
        'cover_message',
        'cv_path',
        'cv_original_name',
        'status',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CareerApplicationMessage::class)->orderBy('id');
    }

    public static function normalizeTelegram(?string $username): string
    {
        $u = trim((string) $username);
        $u = ltrim($u, '@');
        $u = preg_replace('#^https?://(www\.)?t\.me/#i', '', $u) ?? $u;

        return mb_substr($u, 0, 120);
    }

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }
}
