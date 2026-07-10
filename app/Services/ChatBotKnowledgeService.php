<?php

namespace App\Services;

use App\Models\CashbackSetting;
use App\Models\MyCart;
use App\Models\ProjectSetting;
use App\Models\SplitPlan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * AI chatbot uchun "bilim to'plami" (knowledge pack).
 *
 * Uch qatlam:
 *   1. UMUMIY jonli ma'lumot (10 daqiqa cache) — tariflar, to'lov usullari,
 *      cashback, nasiya, kontaktlar. Hammasi DB'dagi REAL sozlamalardan
 *      olinadi, shuning uchun hech qachon eskirmaydi.
 *   2. FOYDALANUVCHIGA XOS kontekst (cache'lanmaydi) — asosiy manzili
 *      bo'yicha haqiqiy yetkazish narxi/muddati, naqd to'lov ruxsati,
 *      keshbek balansi.
 *   3. Boshqaruvdan tahrir qilinadigan qo'shimcha qo'llanma
 *      (project_settings.ai_bot_extra_notes).
 *
 * MUHIM: bu matn AI system promptiga kiradi — AI faqat shu yerda yozilgan
 * faktlarga tayanishi kerak. Qoidalar ChatBotController promptida.
 */
class ChatBotKnowledgeService
{
    private const CACHE_KEY = 'chatbot:knowledge:v1';
    private const CACHE_TTL = 600; // 10 daqiqa

    public function __construct(
        private readonly DeliveryZoneResolverService $deliveryResolver,
    ) {
    }

    /**
     * To'liq bilim to'plami: umumiy (cache) + foydalanuvchiga xos (jonli).
     */
    public function buildContext(User $user): string
    {
        $general = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildGeneralKnowledge());

        return $general . "\n" . $this->buildUserContext($user);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ─── 1-qatlam: umumiy jonli ma'lumot ─────────────────────────────────────

    private function buildGeneralKnowledge(): string
    {
        $parts = [];
        $settings = ProjectSetting::query()->first();

        $parts[] = "=== KITOBCHI HAQIDA MA'LUMOT (faqat shundan foydalan!) ===";
        $parts[] = "Kitobchi — kitoblar va kanselyariya mahsulotlari onlayn marketpleysi (O'zbekiston).";

        // ── To'lov usullari ──────────────────────────────────────────────
        $parts[] = "TO'LOV USULLARI:";
        $parts[] = "- Karta orqali onlayn to'lov (Uzcard/Humo, Paylov tizimi orqali xavfsiz).";
        $parts[] = "- Naqd to'lov (buyurtma yetkazilganda kuryer qo'lida) — faqat kuryer xizmati bilan va hududga bog'liq: ba'zi hududlarda naqd yopiq bo'lishi mumkin. Checkout'da naqd varianti ko'rinsa — mumkin. Avvalgi naqd buyurtma qaytarilgan bo'lsa, naqd vaqtincha yopilishi mumkin.";
        $parts[] = "- Keshbek balansidan to'lovda foydalanish mumkin.";

        // ── Yetkazish (umumiy tariflar) ──────────────────────────────────
        try {
            if (Schema::hasTable('delivery_services')) {
                $services = \App\Models\DeliveryService::query()->active()->get();
                if ($services->isNotEmpty()) {
                    $parts[] = "YETKAZISH XIZMATLARI (umumiy tariflar):";
                    foreach ($services as $s) {
                        $free = (int) $s->freePriceFrom > 0
                            ? sprintf(", %s so'mdan yuqori xaridda BEPUL", number_format((int) $s->freePriceFrom))
                            : '';
                        $parts[] = sprintf(
                            "- %s: taxminan %s so'm, muddati ~%d kun%s",
                            $s->name,
                            number_format((int) $s->priceKg),
                            (int) $s->muddat,
                            $free
                        );
                    }
                    $parts[] = "Eslatma: aniq narx manzil va sotuvchilar soniga bog'liq — bir buyurtmada bir nechta sotuvchi bo'lsa ozgina qo'shimcha bo'ladi. Aniq summa checkout'da ko'rsatiladi.";
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ChatBot knowledge delivery failed: ' . $e->getMessage());
        }

        // ── Qadoqlash ────────────────────────────────────────────────────
        if ($settings) {
            $small = (int) ($settings->packaging_price_small ?? 0);
            $large = (int) ($settings->packaging_price_large ?? 0);
            $threshold = (int) ($settings->packaging_threshold ?? 0);
            if ($small > 0) {
                $parts[] = sprintf(
                    "QADOQLASH: kichik qadoq %s so'm, katta qadoq (%d tadan ko'p kitob) %s so'm.",
                    number_format($small),
                    $threshold,
                    number_format($large)
                );
            }
        }

        // ── Keshbek ──────────────────────────────────────────────────────
        try {
            $tiers = CashbackSetting::query()->orderBy('fromUzs')->get();
            if ($tiers->isNotEmpty()) {
                $lines = $tiers->map(fn ($t) => sprintf(
                    "%s–%s so'm: %d%%%s",
                    number_format((int) $t->fromUzs),
                    number_format((int) $t->toUzs),
                    (int) $t->cashback,
                    $t->type === CashbackSetting::TYPE_PICKUP ? " (do'kondan olib ketish)" : ''
                ))->implode('; ');
                $parts[] = "KESHBEK: har bir xariddan keshbek beriladi. Darajalar: {$lines}.";
            }
        } catch (\Throwable) {
        }

        // Izoh uchun keshbek
        if ($settings && ($settings->review_cashback_enabled ?? true)) {
            $amount = (int) ($settings->review_cashback_amount ?? 100);
            if ($amount > 0) {
                $parts[] = sprintf(
                    "IZOH BONUSI: mijoz o'zi sotib olgan mahsulotga izoh qoldirsa +%s so'm keshbek oladi (har mahsulot uchun 1 marta).",
                    number_format($amount)
                );
            }
        }

        // ── Nasiya (split) ───────────────────────────────────────────────
        try {
            if ($settings && ($settings->split_enabled ?? false) && ($settings->split_public_enabled ?? false)) {
                $planLines = '';
                if (Schema::hasTable('split_plans')) {
                    $planLines = SplitPlan::query()->where('enabled', true)
                        ->orderBy('months')
                        ->get()
                        ->map(fn ($p) => sprintf(
                            "%s (%d oy, oyiga %s%%)",
                            $p->name,
                            (int) $p->months,
                            rtrim(rtrim(number_format((float) $p->monthly_interest_percent, 2), '0'), '.')
                        ))->implode(', ');
                }
                $parts[] = "NASIYA (bo'lib to'lash): mavjud. "
                    . ($planLines ? "Tariflar: {$planLines}. " : '')
                    . "Limit har bir mijozga xarid tarixi va ishonch balliga qarab beriladi (Profil > Nasiya bo'limida ko'rinadi). Birinchi to'lov xarid paytida kartadan olinadi, qolganlari jadval bo'yicha avtomatik yechiladi. Muddatidan oldin yopsa qolgan oylar ustamasi kechiriladi.";
            }
        } catch (\Throwable) {
        }

        // ── Savdolashish (haggling) ──────────────────────────────────────
        $parts[] = "CHEGIRMA: mijoz shu chatda savdolashishi mumkin! Savatiga mahsulot qo'shib, chegirma so'rasa — Hamid aka bilan savdolashadi va promokod oladi (24 soat amal qiladi).";

        // ── Buyurtma jarayoni ────────────────────────────────────────────
        $parts[] = "BUYURTMA: Savat > Checkout'da manzil, yetkazish xizmati va to'lov usuli tanlanadi. Buyurtma holati Profil > Xaridlarim bo'limida kuzatiladi (yig'ilmoqda > yo'lda > yetkazildi).";
        $parts[] = "QAYTARISH: muammoli mahsulot bo'lsa mijoz support bilan bog'lanishi kerak — bot qaytarish va'da bermasin.";

        // ── Kontakt ──────────────────────────────────────────────────────
        if ($settings) {
            $contact = array_filter([
                $settings->kitobchi_phone ? "tel: {$settings->kitobchi_phone}" : null,
                $settings->kitobchi_email ? "email: {$settings->kitobchi_email}" : null,
            ]);
            if ($contact) {
                $parts[] = 'SUPPORT: ' . implode(', ', $contact) . '. Murakkab muammolarda (pul qaytarish, buzilgan mahsulot, hisob muammosi) shu yerga yo\'naltir.';
            }
        }

        // ── 3-qatlam: boshqaruvdan yozilgan qo'shimcha qo'llanma ─────────
        $extra = trim((string) ($settings?->ai_bot_extra_notes ?? ''));
        if ($extra !== '') {
            $parts[] = "QO'SHIMCHA QOIDALAR (admin tomonidan):\n" . mb_substr($extra, 0, 2000);
        }

        return implode("\n", $parts);
    }

    // ─── 2-qatlam: foydalanuvchiga xos kontekst ──────────────────────────────

    private function buildUserContext(User $user): string
    {
        $parts = ["=== SHU MIJOZ HAQIDA ==="];

        // Keshbek balansi
        $cashback = (int) ($user->cashback ?? 0);
        $parts[] = "Keshbek balansi: " . number_format($cashback) . " so'm.";

        // Asosiy manzil bo'yicha haqiqiy yetkazish narxi
        try {
            $location = $user->location; // mainAddressID

            if ($location && ! $location->isDeleted) {
                $addressLabel = $location->city_name ?: $location->district_name ?: $location->region_name ?: $location->fullAddress;

                // Savati bo'yicha (bepul chegara ishlashi uchun) — bo'sh bo'lsa 0
                $cartTotal = (float) MyCart::query()
                    ->where('user_id', $user->id)
                    ->with('product')
                    ->get()
                    ->sum(fn ($item) => ($item->product_price ?? 0) * ($item->count_item ?? 1));

                $offers = $this->deliveryResolver->resolveOffers($location, 1, $cartTotal);

                if ($offers->isNotEmpty()) {
                    $parts[] = "Asosiy manzili: {$addressLabel}. Shu manzil uchun yetkazish variantlari (hozirgi savati bo'yicha):";
                    foreach ($offers as $offer) {
                        $price = (int) $offer['calculated_price'];
                        $parts[] = sprintf(
                            "- %s: %s, ~%d kun%s",
                            $offer['name'],
                            $price === 0 ? 'BEPUL' : number_format($price) . " so'm",
                            (int) $offer['muddat'],
                            ! empty($offer['cod_allowed']) ? ", naqd to'lov mumkin" : ", faqat karta orqali"
                        );
                    }
                    $parts[] = "Eslatma: bir buyurtmada bir nechta sotuvchi bo'lsa yetkazish ozgina oshadi — yakuniy narx checkout'da.";
                } else {
                    $parts[] = "Asosiy manzili: {$addressLabel} — bu manzil uchun hozircha yetkazish xizmati sozlanmagan, checkout'da tekshirishni ayt.";
                }
            } else {
                $parts[] = "Mijozning asosiy manzili kiritilmagan. Yetkazish narxini so'rasa: manzilini Profil > Manzillarim'da qo'shsa aniq narxni aytishingni tushuntir, hozircha umumiy tarifni ayt.";
            }
        } catch (\Throwable $e) {
            Log::warning('ChatBot user delivery context failed: ' . $e->getMessage());
        }

        // Naqd to'lov holati (COD strike)
        if ((int) ($user->cod_return_strikes ?? 0) > 0) {
            $parts[] = "DIQQAT: bu mijozda avvalgi naqd buyurtma qaytgani uchun naqd to'lov vaqtincha yopiq bo'lishi mumkin — karta orqali to'lashni tavsiya qil (aybsiz ohangda).";
        }

        // Premium
        if ((bool) ($user->is_premium ?? false)) {
            $parts[] = "Mijoz Premium obunachi.";
        }

        return implode("\n", $parts);
    }
}
