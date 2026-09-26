<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Haftalik / oylik e'lon videosi uchun kitoblar to'plami (boshqaruv → Kitob videolari).
 */
class BookVideoSet extends Model
{
    public const PERIOD_WEEKLY = 'weekly';
    public const PERIOD_MONTHLY = 'monthly';

    public const PERIODS = [self::PERIOD_WEEKLY, self::PERIOD_MONTHLY];

    public const TEMPLATES = ['carousel', 'countdown', 'grid'];

    public const MAX_BOOKS = 10;

    protected $fillable = [
        'period',
        'period_start',
        'period_end',
        'title',
        'template',
        'book_ids',
        'is_customized',
        'updated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'book_ids' => 'array',
        'is_customized' => 'boolean',
    ];
}
