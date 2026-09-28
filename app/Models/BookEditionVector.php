<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kitob kartasining embeddingi (1:1, `edition_id` bo'yicha).
 *
 * Vektor kitobning MAZMUNINI ifodalaydi: nom, muallif, kategoriya, teglar,
 * til, yil, muqova, nashriyot, tavsif. Narx, sotuv soni va do'kon — saralash
 * va filtr signallari, ular SQL'da qo'llanadi va vektorga kirmaydi. Shu
 * sababli vektor faqat kitob matni o'zgarganda qayta yasaladi.
 */
class BookEditionVector extends Model
{
    protected $primaryKey = 'edition_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['edition_id', 'vector', 'text_hash'];

    protected $casts = [
        'vector' => 'array',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(BookEdition::class, 'edition_id');
    }

    /**
     * Kartalar vektorini "qayta hisoblash kerak" deb belgilaydi.
     *
     * Ommaviy yangilanishlar (`->update([...])` — masalan muallif nomi
     * o'zgarsa, uning barcha kitoblari) model hodisasini ishga tushirmaydi.
     * Hash tozalanadi — rejalashtirilgan `vectors:rebuild` ularni qayta
     * yasaydi. Vektorning o'zi o'chmaydi: shungacha qidiruv ishlayveradi.
     *
     * @param  iterable<int>|\Illuminate\Support\Collection<int,int>  $editionIds
     */
    public static function markStale(iterable $editionIds): int
    {
        $ids = collect($editionIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $marked = 0;

        foreach ($ids->chunk(1000) as $chunk) {
            $marked += static::query()->whereIn('edition_id', $chunk->all())->update(['text_hash' => null]);
        }

        return $marked;
    }

    /** JSON matn yoki massiv — ikkalasini ham massivga keltiradi. */
    public static function decode(mixed $value): ?array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) && $value !== [] ? $value : null;
    }
}
