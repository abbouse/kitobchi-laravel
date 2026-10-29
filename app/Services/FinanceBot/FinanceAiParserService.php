<?php

namespace App\Services\FinanceBot;

use App\Models\PlatformExpense;
use App\Models\PlatformIncome;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

/**
 * Guruhda yozilgan tabiiy tildagi xabarlarni OpenAI orqali
 * moliyaviy chiqim yoki kirim sifatida tahlil qiluvchi xizmat.
 */
class FinanceAiParserService
{
    public function __construct(
        protected OpenAIService $openAI
    ) {}

    /**
     * Matnda son yoki moliyaviy belgi bor-yo'qligini tezkor tekshirish (regex filter).
     * OpenAI ga behuda so'rov yubormaslik uchun oddiy suhbatlarni elab oladi.
     */
    public function couldBeFinancial(string $text): bool
    {
        $clean = trim($text);
        if ($clean === '') {
            return false;
        }

        // Raqamlar yoki son belgisi
        if (preg_match('/[0-9]/', $clean)) {
            return true;
        }

        // O'zbekcha yozilgan sonlar / moliyaviy kalit so'zlar
        $keywords = [
            'ming', 'mln', 'million', 'som', "so'm", 'uzs', 'arenda', 'ijara',
            'taksi', 'benzin', 'obed', 'obid', 'qadoq', 'skotch', 'reklama',
            'kuryer', 'buyurtma', 'tushum', 'kirim', 'chiqim', 'xarajat',
        ];

        $pattern = '/\b(' . implode('|', array_map('preg_quote', $keywords)) . ')\b/ui';

        return (bool) preg_match($pattern, $clean);
    }

    /**
     * Matnda minus ishorasi bormi-yo'qligini aniqlash.
     * Minusli son -> Chiqim/xarajat ko'rsatkichi.
     */
    public function hasNegativeSign(string $text): bool
    {
        // Masalan: "-50000", "- 50 000", "-20k", "-15 ming"
        return (bool) preg_match('/(?:^|[^\w])-\s*(\d+|bir|ikki|uch|to\'rt|besh|olti|yetti|sakkiz|to\'qqiz|o\'n|yigirma|ellik|yuz|ming)/ui', $text);
    }

    /**
     * OpenAI orqali matnni tahlil qilish.
     *
     * @param string $text Guruhdagi xabar
     * @param array|null $pendingContext Agar oldingi qoralamadan qolgan ma'lumot bo'lsa
     * @return array{
     *     is_financial: bool,
     *     type: string, // 'expense' | 'income'
     *     amount: int|null,
     *     category: string,
     *     title: string|null,
     *     note: string|null,
     *     is_complete: bool,
     *     missing_field: string|null,
     *     clarification_question: string|null
     * }
     */
    public function parse(string $text, ?array $pendingContext = null): array
    {
        $hasMinus = $this->hasNegativeSign($text);

        $expenseCategories = implode(', ', array_keys(PlatformExpense::CATEGORIES));
        $incomeTypes = implode(', ', array_keys(PlatformIncome::TYPES));

        $contextJson = $pendingContext ? json_encode($pendingContext, JSON_UNESCAPED_UNICODE) : 'null';
        $minusText = $hasMinus ? 'HA' : "YO'Q";

        $prompt = <<<EOT
Siz moliya va hisob-kitob bo'yicha aqlli yordamchisiz. Telegram guruhida xodimlar va adminlar yozgan tabiiy tildagi xabarlarni tahlil qilasiz.

QOIDALAR:
1. Matn xarajat (chiqim) yoki daromad (kirim/foyda) haqidami?
   - Agar matnda son minus bilan yozilgan bo'lsa (masalan: -50000, -100 ming, taksi -25k), bu aniq CHIQIM (type: "expense").
   - Agar minussiz yozilgan bo'lsa (yoki plus bilan, masalan: 120000, buyurtma 85000, kuryerdan arenda 150 ming), bu FOYDA / KIRIM (type: "income") bo'lishi mumkin.
   - Agar matn umumiy gap bo'lsa va moliyaga hech qanday aloqasi bo'lmasa: is_financial: false.

2. Summani hisoblash (amount):
   - Har doim musbat butun son (so'm / UZS) ko'rinishida qaytarilsin.
   - "50k" -> 50000
   - "50 ming" / "50ming" -> 50000
   - "1.5 mln" -> 1500000
   - "200.000" / "200 000" -> 200000
   - Agar summa mutlaqo yo'q bo'lsa: null.

3. Kategoriya (category):
   - Agar type == "expense", faqat quyidagilardan biri: {$expenseCategories}
     * marketing: reklama, bloger, target, smm, banner, flyer
     * packaging: qadoqlash, korobka, skotch, paket, plyonka, lenta
     * logistics: taksi, transport, yo'lkira, benzin, yetkazib berish xarajati
     * returns: qaytgan tovar xarajati
     * infrastructure: server, hosting, sms, domen, dastur
     * rent: ofis yoki ombor ijarasi
     * utilities: kommunal, svet, gaz, suv, internet, aloqa
     * office: kantselyariya, qog'oz, obed/ovqat, choy, qahva, xo'jalik mollari
     * other: boshqa xarajatlar
   - Agar type == "income", faqat quyidagilardan biri: {$incomeTypes}
     * order_payment: buyurtma to'lovi, kitob savdosi tushumi
     * courier_rent: kuryerdan olingan arenda/ijara haqi (masalan skuter, moped, sumka)
     * delivery_income: yetkazishdan tushgan to'lov
     * other: boshqa kirimlar

4. Maqsad / Sarlavha (title):
   - Nima uchun sarflangani yoki qayerdan kelganining qisqa, aniq nomi (masalan: "Ofis ijarasi", "Kantselyariya tovarlari", "Kuryer Alisher arenda to'lovi", "Taksi yo'lkirasi").

5. To'liqligi (is_complete):
   - Agar ham summa (amount), ham maqsad (title) aniq bo'lsa: is_complete = true.
   - Agar summa bor, lekin nimaga sarflangani/qayerdan kelgani noma'lum bo'lsa (masalan shunchaki "-50000" yoki "100 ming"):
     is_complete = false, missing_field = "title", clarification_question = "Summani tushundim ({amount} so'm). Iltimos, bu {type_uz} nimaga sarflanganini (yoki kimdan kelganini) ushbu xabarga javob qilib yozing."
   - Agar maqsad bor, lekin summa yo'q bo'lsa (masalan "Kuryer arenda to'ladi" yoki "Ofisga qog'oz oldik"):
     is_complete = false, missing_field = "amount", clarification_question = "Iltimos, summani (necha so'm) ham yozib yuboring."

Oldingi qoralama (pending context): {$contextJson}
Matn: "{$text}"
Matnda minus belgisi bormi: {$minusText}

Faqat toza JSON qaytaring. Sxema:
{
  "is_financial": true,
  "type": "expense",
  "amount": 50000,
  "category": "office",
  "title": "Ofis uchun qog'oz va ruchkalar",
  "note": null,
  "is_complete": true,
  "missing_field": null,
  "clarification_question": null
}
EOT;

        try {
            $result = $this->openAI->askJsonWithMessages([
                ['role' => 'system', 'content' => "Siz buxgalteriya va moliya AI tahlilchisisiz. Faqat ko'rsatilgan JSON formatda javob qaytarasiz."],
                ['role' => 'user', 'content' => $prompt],
            ], 600, 0.1);

            return [
                'is_financial'           => (bool) ($result['is_financial'] ?? false),
                'type'                   => in_array($result['type'] ?? '', ['expense', 'income']) ? $result['type'] : ($hasMinus ? 'expense' : 'income'),
                'amount'                 => isset($result['amount']) && is_numeric($result['amount']) ? (int) abs($result['amount']) : null,
                'category'               => (string) ($result['category'] ?? ($hasMinus ? 'other' : 'other')),
                'title'                  => ! empty($result['title']) ? trim((string) $result['title']) : null,
                'note'                   => ! empty($result['note']) ? trim((string) $result['note']) : null,
                'is_complete'            => (bool) ($result['is_complete'] ?? false),
                'missing_field'          => $result['missing_field'] ?? null,
                'clarification_question' => $result['clarification_question'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('FinanceAiParserService: OpenAI xatosi: ' . $e->getMessage());

            // OpenAI ishlamasa fallback heuristika
            return $this->fallbackParse($text, $hasMinus);
        }
    }

    /**
     * Agar OpenAI ulanmay qolsa yoki xato bersa oddiy regex fallback
     */
    protected function fallbackParse(string $text, bool $hasMinus): array
    {
        $amount = null;
        if (preg_match('/(?:-|\+)?\s*(\d[\d\s\.,]*)\s*(k|ming|mln|million)?/ui', $text, $matches)) {
            $numStr = preg_replace('/[\s\.,]/', '', $matches[1]);
            $num = (float) $numStr;
            $unit = mb_strtolower($matches[2] ?? '');
            if ($unit === 'k' || $unit === 'ming') {
                $num *= 1000;
            } elseif ($unit === 'mln' || $unit === 'million') {
                $num *= 1000000;
            }
            if ($num > 0) {
                $amount = (int) $num;
            }
        }

        $cleanTitle = trim(preg_replace('/[-+]?\s*\d[\d\s\.,]*(k|ming|mln|million|so\'m|som|uzs)?/ui', '', $text));

        return [
            'is_financial'           => $amount !== null,
            'type'                   => $hasMinus ? 'expense' : 'income',
            'amount'                 => $amount,
            'category'               => 'other',
            'title'                  => $cleanTitle !== '' ? $cleanTitle : null,
            'note'                   => $text,
            'is_complete'            => $amount !== null && $cleanTitle !== '',
            'missing_field'          => $amount === null ? 'amount' : ($cleanTitle === '' ? 'title' : null),
            'clarification_question' => $amount === null
                ? "Iltimos, summani (necha so'm) ham yozib yuboring."
                : ($cleanTitle === '' ? "Summani tushundim. Iltimos, bu nimaga sarflanganini (yoki kimdan olinganini) yozing." : null),
        ];
    }
}
