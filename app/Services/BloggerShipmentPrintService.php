<?php

namespace App\Services;

use App\Models\BloggerShipment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class BloggerShipmentPrintService
{
    public function receiptData(BloggerShipment $shipment, ?string $forcedLocale = null): array
    {
        $shipment->loadMissing(['blogger', 'items']);
        $blogger = $shipment->blogger;
        $locale = $this->resolveLocale($forcedLocale);
        $socialLinks = $this->formatSocialLinks($blogger?->socialLinks() ?? []);
        $items = $shipment->items->values()->map(fn ($item, $index) => [
            'index' => $index + 1,
            'name' => $item->name,
        ])->all();
        $itemsPreview = collect($items)->take(3)->values()->all();

        return [
            'shipment_number' => '#BLG-' . $shipment->id,
            'locale' => $locale,
            'title' => $this->text($locale, 'title'),
            'items_label' => $this->text($locale, 'items_label'),
            'item_name_label' => $this->text($locale, 'item_name_label'),
            'scheduled_at_pretty' => $this->formatPrettyDateTime($shipment->scheduled_for, $locale),
            'scheduled_at_raw' => $shipment->scheduled_for?->format('d.m.Y H:i') ?: '—',
            'blogger_name' => $blogger?->full_name ?: $this->text($locale, 'blogger_fallback'),
            'phone' => $this->formatPhone($blogger?->phone_number),
            'address' => trim((string) $blogger?->address) ?: '—',
            'social_links' => $socialLinks,
            'social_primary' => isset($socialLinks[0])
                ? ($socialLinks[0]['label'] . ': ' . $socialLinks[0]['value'])
                : null,
            'items' => $items,
            'items_preview' => $itemsPreview,
            'items_remaining' => max(0, count($items) - count($itemsPreview)),
            'prepared_label' => $this->text($locale, 'prepared_label'),
            'delivered_at' => $shipment->delivered_at?->format('d.m.Y H:i'),
            'message' => $this->resolveDelightMessage($shipment, $locale),
        ];
    }

    private function formatPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '—';
        }

        if (Str::startsWith($digits, '998') && strlen($digits) === 12) {
            return sprintf(
                '+%s %s %s %s %s',
                substr($digits, 0, 3),
                substr($digits, 3, 2),
                substr($digits, 5, 3),
                substr($digits, 8, 2),
                substr($digits, 10, 2),
            );
        }

        if (strlen($digits) === 9) {
            return $this->formatPhone('998' . $digits);
        }

        return '+' . $digits;
    }

    private function formatSocialLinks(array $links): array
    {
        return collect($links)
            ->map(function ($url, $label) {
                $value = trim((string) $url);
                if ($value === '') {
                    return null;
                }

                $normalized = preg_replace('#^https?://#', '', $value);

                return [
                    'label' => $label,
                    'value' => rtrim((string) $normalized, '/'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveLocale(?string $locale): string
    {
        $normalized = strtolower(trim((string) $locale));
        return in_array($normalized, ['uz', 'ru', 'en', 'ja'], true) ? $normalized : 'uz';
    }

    private function formatPrettyDateTime(CarbonInterface|string|null $value, string $locale): string
    {
        if (! $value) {
            return '—';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);
        $month = $this->monthLabel($date->month, $locale);

        return match ($locale) {
            'ja' => $date->month . '月' . $date->day . '日 ' . $date->format('H:i'),
            default => $date->day . '-' . $month . ' ' . $date->format('H:i'),
        };
    }

    private function resolveDelightMessage(BloggerShipment $shipment, string $locale): string
    {
        $variants = match ($locale) {
            'ru' => [
                'Пусть эта коробка принесёт много тёплого отклика.',
                'Спасибо за сотрудничество, впереди красивый контент.',
                'Пусть каждая вещь в коробке найдёт своё яркое сторис.',
                'Немного заботы, немного вдохновения, и получится отличный обзор.',
            ],
            'en' => [
                'A thoughtful box for a thoughtful creator.',
                'Thank you for collaborating with Kitobchi.',
                'May this package turn into beautiful content and warm reactions.',
                'A small delivery with big storytelling energy.',
            ],
            'ja' => [
                'このボックスが、すてきな発信につながりますように。',
                'ご一緒いただき、ありがとうございます。',
                'やさしい気持ちと良いコンテンツが届きますように。',
                '小さな箱から、大きな物語が生まれますように。',
            ],
            default => [
                'Hamkorlik uchun rahmat, bu quti yaxshi kontentga aylansin.',
                'Kitobchi salomi yetib bordi, endi navbat chiroyli postlarda.',
                'Qutida mayda quvonchlar, storisda katta taassurotlar bo‘lsin.',
                'Bugungi jo‘natma ertangi yaxshi reels uchun xizmat qilsin.',
            ],
        };

        return $variants[((int) $shipment->id) % count($variants)];
    }

    private function monthLabel(int $month, string $locale): string
    {
        $months = [
            'uz' => [1 => 'yanvar', 2 => 'fevral', 3 => 'mart', 4 => 'aprel', 5 => 'may', 6 => 'iyun', 7 => 'iyul', 8 => 'avgust', 9 => 'sentyabr', 10 => 'oktyabr', 11 => 'noyabr', 12 => 'dekabr'],
            'ru' => [1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'],
            'en' => [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'],
            'ja' => [1 => '1月', 2 => '2月', 3 => '3月', 4 => '4月', 5 => '5月', 6 => '6月', 7 => '7月', 8 => '8月', 9 => '9月', 10 => '10月', 11 => '11月', 12 => '12月'],
        ];

        return $months[$locale][$month] ?? (string) $month;
    }

    private function text(string $locale, string $key): string
    {
        $map = [
            'uz' => [
                'title' => 'Kitobchi blogger cheki',
                'blogger_fallback' => 'Hamkor bloger',
                'prepared_label' => 'Tayyorlandi',
                'items_label' => 'Mahsulotlar',
                'item_name_label' => 'Nomi',
            ],
            'ru' => [
                'title' => 'Чек для блогера Kitobchi',
                'blogger_fallback' => 'Партнёр-блогер',
                'prepared_label' => 'Подготовлено',
                'items_label' => 'Товары',
                'item_name_label' => 'Название',
            ],
            'en' => [
                'title' => 'Kitobchi blogger receipt',
                'blogger_fallback' => 'Partner blogger',
                'prepared_label' => 'Prepared',
                'items_label' => 'Products',
                'item_name_label' => 'Name',
            ],
            'ja' => [
                'title' => 'Kitobchi ブロガー伝票',
                'blogger_fallback' => '提携ブロガー',
                'prepared_label' => '準備完了',
                'items_label' => '商品',
                'item_name_label' => '品名',
            ],
        ];

        return $map[$locale][$key] ?? $map['uz'][$key] ?? $key;
    }
}
