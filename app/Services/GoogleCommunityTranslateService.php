<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stichoza\GoogleTranslate\GoogleTranslate;

class GoogleCommunityTranslateService
{
    /**
     * @param  array<string, string>  $texts
     * @param  array<int, string>  $targetLocales
     * @return array<string, array<string, ?string>>
     */
    public function translateTexts(array $texts, string $sourceLocale, array $targetLocales): array
    {
        $translations = [];

        foreach ($targetLocales as $locale) {
            $translated = [];

            foreach ($texts as $field => $value) {
                $value = is_string($value) ? trim($value) : '';

                if ($value === '') {
                    $translated[$field] = null;
                    continue;
                }

                try {
                    $translated[$field] = $this->looksLikeHtml($value)
                        ? $this->translateHtml($value, $sourceLocale, $locale)
                        : $this->translatePlainText($value, $sourceLocale, $locale);
                } catch (\Throwable $e) {
                    Log::error('Google community translate failed', [
                        'field' => $field,
                        'source_locale' => $sourceLocale,
                        'target_locale' => $locale,
                        'error' => $e->getMessage(),
                    ]);

                    throw ValidationException::withMessages([
                        'texts' => "Google community tarjimasi vaqtincha ishlamadi. Yana bir bor urinib ko'ring.",
                    ]);
                }
            }

            $translations[$locale] = $translated;
        }

        return $translations;
    }

    private function translatePlainText(string $text, string $sourceLocale, string $targetLocale): string
    {
        $translator = $this->makeTranslator($sourceLocale, $targetLocale);

        return trim((string) $translator->translate($text));
    }

    private function translateHtml(string $html, string $sourceLocale, string $targetLocale): string
    {
        libxml_use_internal_errors(true);

        $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>';

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//text()[normalize-space() != "" and not(ancestor::script) and not(ancestor::style)]');

        if ($nodes) {
            foreach ($nodes as $node) {
                $original = (string) $node->nodeValue;
                $trimmed = trim($original);

                if ($trimmed === '') {
                    continue;
                }

                preg_match('/^\s*/u', $original, $leadingMatch);
                preg_match('/\s*$/u', $original, $trailingMatch);

                $leading = $leadingMatch[0] ?? '';
                $trailing = $trailingMatch[0] ?? '';

                $translated = $this->translatePlainText($trimmed, $sourceLocale, $targetLocale);
                $node->nodeValue = $leading.$translated.$trailing;
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $output = '';

        if ($body) {
            foreach ($body->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
        }

        libxml_clear_errors();

        return trim($output);
    }

    private function looksLikeHtml(string $text): bool
    {
        return $text !== strip_tags($text);
    }

    private function makeTranslator(string $sourceLocale, string $targetLocale): GoogleTranslate
    {
        return (new GoogleTranslate(
            $this->mapLocale($targetLocale),
            $this->mapLocale($sourceLocale),
            [
                'timeout' => 25,
                'connect_timeout' => 10,
            ],
            null,
            '/\{[^}]+\}|:\w+/'
        ))->setClient('gtx');
    }

    private function mapLocale(string $locale): string
    {
        return match ($locale) {
            'ja' => 'ja',
            'ru' => 'ru',
            'en' => 'en',
            'uz' => 'uz',
            default => $locale,
        };
    }
}
