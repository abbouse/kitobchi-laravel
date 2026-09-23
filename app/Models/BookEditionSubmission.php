<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Do'kon katalogda topmagan kitobni qo'shish arizasi (moderatsiya navbati). */
class BookEditionSubmission extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_MERGED = 'merged';

    public const TYPE_NEW = 'new_book';
    public const TYPE_CORRECTION = 'correction';

    public const CHECK_MATCHED = 'matched';
    public const CHECK_MISMATCH = 'mismatch';
    public const CHECK_UNREADABLE = 'unreadable';
    public const CHECK_NO_ISBN = 'no_isbn';

    protected $fillable = [
        'seller_id', 'type', 'staff_id', 'edition_id', 'book_id', 'isbn13',
        'back_isbn_server', 'back_isbn_server_method', 'back_isbn_client',
        'isbn_check', 'payload', 'front_image', 'back_image', 'status',
        'reviewer_id', 'reviewed_at', 'reject_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function isCorrection(): bool
    {
        return $this->type === self::TYPE_CORRECTION;
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(BookEdition::class, 'edition_id')->withTrashed();
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Books::class, 'book_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
