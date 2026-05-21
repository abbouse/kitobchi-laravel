<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogParserItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'external_id',
        'source_url',
        'title',
        'author',
        'isbn',
        'normalized_title',
        'normalized_author',
        'source_category',
        'publisher',
        'translator',
        'language',
        'script',
        'cover_type',
        'year',
        'pages',
        'price_uzs',
        'rating_value',
        'rating_count',
        'in_stock',
        'primary_image_url',
        'remote_image_urls',
        'description',
        'payload',
        'matched_book_id',
        'match_confidence',
        'match_reason',
        'suggested_category_id',
        'suggested_category_name',
        'category_ai_payload',
        'suggested_tag_ids',
        'suggested_tag_names',
        'tags_ai_payload',
        'imported_book_id',
        'last_synced_at',
        'imported_at',
        'last_import_error',
    ];

    protected $casts = [
        'remote_image_urls' => 'array',
        'payload' => 'array',
        'category_ai_payload' => 'array',
        'suggested_tag_ids' => 'array',
        'suggested_tag_names' => 'array',
        'tags_ai_payload' => 'array',
        'in_stock' => 'boolean',
        'last_synced_at' => 'datetime',
        'imported_at' => 'datetime',
        'rating_value' => 'decimal:2',
    ];

    public function importedBook()
    {
        return $this->belongsTo(Books::class, 'imported_book_id');
    }

    public function matchedBook()
    {
        return $this->belongsTo(Books::class, 'matched_book_id');
    }

    public function suggestedCategory()
    {
        return $this->belongsTo(BookCategories::class, 'suggested_category_id');
    }
}
