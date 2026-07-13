<?php

namespace App\Services;

class ProductModerationPolicy
{
    /** @return list<array{code:string,severity:string,note:string}> */
    public function deterministicIssues(string $type, array $product): array
    {
        $issues = [];
        $add = static function (string $code, string $severity, string $note) use (&$issues): void {
            $issues[] = compact('code', 'severity', 'note');
        };

        if (mb_strlen(trim((string) ($product['name'] ?? ''))) < 2) {
            $add('missing_name', 'block', 'Mahsulot nomi kiritilmagan.');
        }
        if (mb_strlen(trim((string) ($product['description'] ?? ''))) < 10) {
            $add('insufficient_description', 'block', 'Tavsif mahsulotni anglash uchun yetarli emas.');
        }
        if ((float) ($product['price'] ?? 0) <= 0) {
            $add('invalid_price', 'block', 'Narx noldan katta bo‘lishi kerak.');
        }

        $discount = (float) ($product['discount_price'] ?? 0);
        if ($discount < 0 || ($discount > 0 && $discount >= (float) ($product['price'] ?? 0))) {
            $add('invalid_discount', 'block', 'Chegirma narxi asosiy narxdan kichik bo‘lishi kerak.');
        }
        if (empty($product['category_id'])) {
            $add('missing_category', 'block', 'Kategoriya tanlanmagan.');
        }
        if (empty($product['seller_id'])) {
            $add('missing_seller', 'block', 'Sotuvchi aniqlanmadi.');
        }
        if (($product['images'] ?? []) === []) {
            $add('missing_image', 'block', 'Kamida bitta mahsulot rasmi kerak.');
        }

        $combinedText = implode(' ', [
            (string) ($product['name'] ?? ''),
            (string) ($product['description'] ?? ''),
        ]);
        if (preg_match('/(?:https?:\/\/|www\.|t\.me\/|telegram\.me\/|wa\.me\/|@\w{4,})/iu', $combinedText)) {
            $add('external_contact_or_link', 'block', 'Listingda tashqi havola yoki aloqa manzili bor.');
        }
        $phonePattern = '/(?<!\d)(?:(?:\+?998)?(?:90|91|93|94|95|97|98|99|88|77|71)\d{7}|(?:\+?998[\s\-]*)?\(?(?:90|91|93|94|95|97|98|99|88|77|71)\)?[\s\-]+\d{3}[\s\-]+\d{2}[\s\-]+\d{2})(?!\d)/u';
        if (preg_match($phonePattern, $combinedText)) {
            $add('phone_number_in_listing', 'block', 'Listing matnida telefon raqami bor.');
        }

        if ($type === 'book') {
            if (mb_strlen(trim((string) ($product['author'] ?? ''))) < 2) {
                $add('missing_author', 'block', 'Kitob muallifi kiritilmagan.');
            }
            if ((int) ($product['pages'] ?? 0) < 1) {
                $add('invalid_pages', 'block', 'Sahifalar soni noto‘g‘ri.');
            }
        }

        return $issues;
    }

    public function hasBlockingIssues(array $issues): bool
    {
        return collect($issues)->contains(fn ($issue) => ($issue['severity'] ?? null) === 'block');
    }
}
