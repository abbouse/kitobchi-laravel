<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BookCategories extends Model
{
    use HasFactory;
    protected $fillable = ['name_ru', 'name_en', 'name_uz', 'name_ja', 'slug', 'icon', 'is_active', 'ofd_ikpu_code', 'ofd_package_code'];

    /** Kategoriya o'zgarsa allCategories keshini tozalaymiz. */
    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget('api:all_categories:v1');
        static::saved($forget);
        static::deleted($forget);
    }

    public function tags()
    {
        return $this->belongsToMany(BookTag::class, 'category_tag_relations', 'category_id', 'tag_id');
    }

    public function books()
    {
        return $this->hasMany(Books::class, 'category_id');
    }

    /**
     * Joriy tilga mos nom — jadvalda `name` ustuni yo'q, faqat
     * name_uz/name_ru/name_en/name_ja bor. Veb (Blade) kod avval
     * to'g'ridan-to'g'ri $cat->name'ga murojaat qilardi — bu doim
     * null qaytarardi (kategoriya nomlari hech qayerda ko'rinmasdi).
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        foreach (["name_{$locale}", 'name_uz', 'name_ru', 'name_en', 'name_ja'] as $field) {
            if (! empty($this->attributes[$field] ?? null)) {
                return $this->attributes[$field];
            }
        }

        return '';
    }
}
