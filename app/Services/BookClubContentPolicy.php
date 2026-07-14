<?php

namespace App\Services;

use Illuminate\Support\Str;

class BookClubContentPolicy
{
    /** @return array<string, mixed> */
    public function inspect(?string $content): array
    {
        $text = trim((string) $content);
        $domains = $this->extractDomains($text);
        $platformMentions = $this->trustedPlatformMentions($text);
        $shorteners = array_values(array_filter($domains, fn (string $domain) => $this->matchesConfiguredDomain(
            $domain,
            (array) config('book_club_moderation.shortener_domains', []),
        )));
        $untrusted = array_values(array_filter($domains, fn (string $domain) => ! $this->isTrustedDomain($domain)));

        $signals = [];
        if ($text === '') {
            $signals[] = 'empty_text';
        }
        if ($shorteners !== []) {
            $signals[] = 'shortened_link';
        }
        if ($untrusted !== []) {
            $signals[] = 'untrusted_link';
        }
        if ($this->hasObfuscatedLink($text)) {
            $signals[] = 'obfuscated_link';
        }
        if ($this->hasIpAddressLink($text)) {
            $signals[] = 'ip_address_link';
        }
        if ($this->hasContactSolicitation($text) && $domains === []) {
            $signals[] = 'direct_contact_solicitation';
        }
        if ($this->hasRepeatedNoise($text)) {
            $signals[] = 'repeated_noise';
        }
        if ($this->hasObviousProfanity($text)) {
            $signals[] = 'profanity';
        }
        if ($this->hasScamPattern($text)) {
            $signals[] = 'scam_pattern';
        }

        return [
            'domains' => $domains,
            'trusted_domains' => array_values(array_diff($domains, $untrusted)),
            'trusted_platform_mentions' => $platformMentions,
            'untrusted_domains' => $untrusted,
            'shortener_domains' => $shorteners,
            'signals' => array_values(array_unique($signals)),
            'has_links' => $domains !== [],
            'has_trusted_ad_destination' => ($domains !== [] && $untrusted === [] && $shorteners === [])
                || $platformMentions !== [],
            'hard_risk' => $shorteners !== []
                || $untrusted !== []
                || array_intersect($signals, [
                    'obfuscated_link',
                    'ip_address_link',
                    'direct_contact_solicitation',
                    'repeated_noise',
                    'profanity',
                    'scam_pattern',
                ]) !== [],
            // severe_risk — deyarli aniq spam/scam. Faqat SHU holatda yangi kontent
            // darhol yashiriladi; oddiy tashqi link yoki so'kinish o'zi yashirmaydi
            // (ijtimoiy tarmoq uslubi — AI keyin ishonch bilan qaror qiladi).
            'severe_risk' => $shorteners !== []
                || array_intersect($signals, [
                    'obfuscated_link',
                    'ip_address_link',
                    'scam_pattern',
                ]) !== [],
        ];
    }

    /** @return array<string, mixed> */
    public function initialState(?string $content): array
    {
        $inspection = $this->inspect($content);

        return [
            // Optimistik: default ko'rinadi. Faqat hold_pending yoqilgan bo'lsa yoki
            // deyarli aniq spam/scam (severe_risk) bo'lsagina darhol yashiriladi.
            'is_hidden_by_ai' => (bool) config('book_club_moderation.hold_pending', false)
                || (bool) $inspection['severe_risk'],
            'ai_moderation_status' => 'pending',
            'ai_moderated_at' => null,
            'ai_moderation_note' => null,
            'ai_moderation_model' => null,
            'ai_moderation_meta' => $inspection,
        ];
    }

    public function isTrustedDomain(string $domain): bool
    {
        return $this->matchesConfiguredDomain(
            $this->normalizeDomain($domain),
            (array) config('book_club_moderation.trusted_domains', []),
        );
    }

    /** @return list<string> */
    private function extractDomains(string $text): array
    {
        preg_match_all(
            '~(?:https?://|www\.)[^\s<>()]+|(?<![@\w])(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:uz|com|org|net|io|me|ru|co|app|info|shop|online)(?:/[^\s<>()]*)?~iu',
            $text,
            $matches,
        );

        return collect($matches[0] ?? [])
            ->map(function (string $url): string {
                $candidate = preg_replace('~^[^:]+://~i', '', trim($url, " \t\n\r\0\x0B.,!?;:'\"[]{}"));
                $candidate = preg_replace('~^www\.~i', '', (string) $candidate);

                return $this->normalizeDomain((string) strtok((string) $candidate, '/'));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param list<string> $configured */
    private function matchesConfiguredDomain(string $domain, array $configured): bool
    {
        foreach ($configured as $allowed) {
            $allowed = $this->normalizeDomain((string) $allowed);
            if ($allowed !== '' && ($domain === $allowed || Str::endsWith($domain, '.'.$allowed))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDomain(string $domain): string
    {
        return Str::lower(trim($domain, ". \t\n\r\0\x0B"));
    }

    /** @return list<string> */
    private function trustedPlatformMentions(string $text): array
    {
        $normalized = Str::lower($text);

        return collect((array) config('book_club_moderation.trusted_platform_names', []))
            ->map(fn ($name) => Str::lower(trim((string) $name)))
            ->filter(fn (string $name) => $name !== '' && Str::contains($normalized, $name))
            ->unique()
            ->values()
            ->all();
    }

    private function hasObfuscatedLink(string $text): bool
    {
        return (bool) preg_match('/\b[a-z0-9-]+\s*(?:\[\s*\.\s*\]|\(\s*\.\s*\)|\s+dot\s+)\s*(?:com|uz|net|org|ru|io)\b/iu', $text);
    }

    private function hasIpAddressLink(string $text): bool
    {
        return (bool) preg_match('~(?:https?://)?(?:\d{1,3}\.){3}\d{1,3}(?::\d+)?(?:/|\b)~', $text);
    }

    private function hasContactSolicitation(string $text): bool
    {
        $contact = preg_match('/(?:\+?998[\s-]?)?(?:\d[\s-]?){9}\b|(?:telegram|whatsapp|телеграм|ватсап)\s*[:@]/iu', $text);
        $callToAction = preg_match('/\b(?:yozing|murojaat|bog[‘\x{2019}\x{02BC}]laning|sotaman|sotiladi|buyurtma|aksiya|chegirma|daromad|ish taklif|пишите|обращайтесь|продам|заказ)\b/iu', $text);

        return (bool) ($contact && $callToAction);
    }

    private function hasRepeatedNoise(string $text): bool
    {
        return (bool) preg_match('/(.)\1{11,}/u', $text)
            || (bool) preg_match('/(?:[!?$#*]{3,}\s*){3,}/u', $text);
    }

    private function hasObviousProfanity(string $text): bool
    {
        return (bool) preg_match('/\b(?:fuck|fucking|shit|bitch|сука|бля(?:дь)?|хуй|пизд\w*|еба\w*|haromi|dalbayob|jalab|fohisha)\b/iu', $text);
    }

    private function hasScamPattern(string $text): bool
    {
        return (bool) preg_match('/\b(?:100\s*%\s*(?:kafolat|daromad)|tez\s+boyib|oson\s+pul|pulni\s+ikki\s+baravar|kazino|casino|stavka|betting|kriptoga\s+qo[‘\x{2019}\x{02BC}]ying)\b/iu', $text);
    }
}
