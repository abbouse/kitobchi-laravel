<?php

namespace App\Services\ReadingIntelligence;

use App\Models\BookClub;
use App\Models\BookReadingInsight;
use App\Models\Books;
use App\Models\Stationery;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Mahsulot darajasidagi (foydalanuvchidan mustaqil) Reading Intelligence
 * kontentini generatsiya qiladi: qiyinlik, kayfiyat, kimlar uchun mos/emas,
 * sharhlar xulosasi.
 *
 * MUHIM — "avval kontekst, keyin AI": chaqiruvdan oldin barcha kerakli
 * ma'lumot (nomi, muallif, kategoriya, tavsif, hajm, mavjud community
 * postlar) bitta joyda yig'iladi va bitta strukturali so'rovga solinadi —
 * ketma-ket bir nechta chaqiruv yo'q.
 *
 * Qiyinlik va kayfiyat — ERKIN MATN EMAS, belgilangan taksonomiya
 * (ENUM) dan tanlanadi. Bu ikki narsani beradi: (1) AI xilma-xil so'z
 * ishlatib chalkashtirmaydi, (2) tarjima til fayllari orqali qilinadi —
 * har bir lokal uchun qayta AI chaqirilmaydi.
 */
class ReadingInsightGenerator
{
    public const DIFFICULTIES = ['light', 'medium', 'deep'];

    public const MOOD_TAGS = [
        'warm', 'fast_paced', 'thought_provoking', 'dark',
        'humorous', 'romantic', 'suspenseful', 'calm', 'inspiring',
    ];

    private const CONTENT_LOCALES = ['uz', 'ru', 'en', 'ja'];
    private const MAX_REVIEW_EXCERPTS = 6;

    public function __construct(
        private readonly OpenAIService $ai,
    ) {
    }

    /**
     * FAQAT o'qish — hech qanday AI chaqiruvi va hech qanday "eskirganmi"
     * tekshiruvi yo'q (bu tekshiruv mahsulot+sharhlarni qayta yuklashni
     * talab qiladi). Item sahifasi so'rovlari FAQAT shu metodni chaqiradi —
     * shu orqali "bu menga mosmi?" kartochkasi hech qachon AI javobini
     * kutib turmaydi (request bloklanmaydi).
     *
     * Kesh hali yo'q bo'lsa (mahsulot yangi qo'shilgan, hali generatsiya
     * qilinmagan) — null qaytadi, servis shunda ham kontentsiz "yangi
     * mahsulot" kartasini xavfsiz ko'rsata oladi.
     *
     * Haqiqiy generatsiya `generateAndStore()` orqali FAQAT fon jarayonida
     * (`reading-intelligence:generate-insights` scheduled buyrug'i) bajariladi.
     */
    public function get(string $type, int $id): ?BookReadingInsight
    {
        $cacheKey = "reading-intel:insight:{$type}:{$id}";

        return Cache::remember($cacheKey, 600, function () use ($type, $id) {
            return BookReadingInsight::query()
                ->where('product_type', $type)
                ->where('product_id', $id)
                ->first();
        });
    }

    /**
     * Kesh mavjud va mahsulot matni o'zgarmagan bo'lsa — mavjudini qaytaradi.
     * Aks holda AI orqali (barcha 4 til uchun bitta chaqiruvda) generatsiya
     * qilib, keshni yangilaydi.
     *
     * DIQQAT: bu metod OpenAI ga jonli so'rov yuborishi mumkin (sekund(lar)
     * davom etishi mumkin) — shuning uchun HTTP so'rov yo'lida emas, FAQAT
     * `reading-intelligence:generate-insights` fon buyrug'idan chaqiriladi.
     */
    public function generateAndStore(string $type, int $id): ?BookReadingInsight
    {
        $product = $this->loadProduct($type, $id);
        if (! $product) {
            return null;
        }

        $context = $this->buildContext($product, $type);
        $hash = md5($context['fingerprint']);

        $insight = BookReadingInsight::query()
            ->where('product_type', $type)
            ->where('product_id', $id)
            ->first();

        if ($insight && $insight->content_hash === $hash) {
            return $insight;
        }

        $generated = $this->generate($context);
        if ($generated === null) {
            // AI muvaffaqiyatsiz bo'lsa — eski (bo'lsa ham) keshni qaytaramiz,
            // hech narsa yo'qolmaydi, hech qanday xato foydalanuvchiga chiqmaydi.
            return $insight;
        }

        $saved = BookReadingInsight::updateOrCreate(
            ['product_type' => $type, 'product_id' => $id],
            [
                'content_hash' => $hash,
                'difficulty' => $generated['difficulty'],
                'mood_tags' => $generated['mood_tags'],
                'audience_fit' => $generated['audience_fit'],
                'audience_avoid' => $generated['audience_avoid'],
                'review_synthesis' => $generated['review_synthesis'],
                'generated_at' => now(),
            ]
        );

        // `get()` dagi keshni yangi natija bilan darhol yangilaymiz —
        // aks holda foydalanuvchilar eski (yoki bo'sh) natijani yana
        // 10 daqiqa ko'rishda davom etadi.
        Cache::put("reading-intel:insight:{$type}:{$id}", $saved, 600);

        return $saved;
    }

    private function loadProduct(string $type, int $id): Books|Stationery|null
    {
        return $type === 'book'
            ? Books::query()->with('category')->find($id)
            : Stationery::query()->with('category')->find($id);
    }

    private function buildContext(Books|Stationery $product, string $type): array
    {
        $categoryName = $product->category->name_uz ?? null;
        $description = mb_substr((string) ($product->description ?? ''), 0, 1200);

        $reviewExcerpts = BookClub::query()
            ->where('product_type', $type)
            ->where('product_id', $product->id)
            ->where('is_deleted', false)
            ->where(function ($q) {
                $q->where('repost', false)->orWhereNull('repost');
            })
            ->whereNotNull('text')
            ->orderByDesc('created_at')
            ->limit(self::MAX_REVIEW_EXCERPTS)
            ->pluck('text')
            ->map(fn ($text) => mb_substr((string) $text, 0, 300))
            ->values()
            ->all();

        $facts = [
            'nomi' => $product->name,
            'muallif' => $type === 'book' ? ($product->author ?? null) : null,
            'kategoriya' => $categoryName,
            'tavsif' => $description !== '' ? $description : null,
            'sahifalar' => $type === 'book' ? ($product->pages ?? null) : null,
            'yili' => $type === 'book' ? ($product->year ?? null) : null,
        ];

        // Fingerprint — vector_text_hash bilan bir xil naqsh: shu matn
        // o'zgarmasa qayta AI chaqirilmaydi. `schema:v2` qismi ATAYLAB
        // qo'shildi — prompt/sxema o'zgarganda (masalan, 'audience_avoid'
        // olib tashlanib, 'audience_fit' kengaytirilganda) BARCHA mavjud
        // yozuvlarning hash'i eskirgan hisoblanishi uchun, garchi
        // mahsulotning o'zi o'zgarmagan bo'lsa ham. Kelajakda promptni
        // yana o'zgartirsangiz, shu belgini oshiring (v3, v4...).
        $fingerprint = json_encode($facts) . '|reviews:' . implode('|', $reviewExcerpts) . '|schema:v2';

        return [
            'facts' => array_filter($facts, fn ($v) => $v !== null && $v !== ''),
            'review_excerpts' => $reviewExcerpts,
            'fingerprint' => $fingerprint,
        ];
    }

    /**
     * @return array{difficulty:string,mood_tags:array,audience_fit:array,audience_avoid:array,review_synthesis:array}|null
     *
     * (audience_avoid — endi doim bo'sh massiv, saqlab qolingan garchi
     * DB ustuni hali mavjud bo'lsa ham. Qarang: validate() dagi izoh.)
     */
    private function generate(array $context): ?array
    {
        $factsText = collect($context['facts'])
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode("\n");

        $reviewsText = empty($context['review_excerpts'])
            ? '(hali community sharh/post yo\'q)'
            : implode("\n---\n", $context['review_excerpts']);

        $localeList = implode(', ', self::CONTENT_LOCALES);
        $difficultyList = implode('|', self::DIFFICULTIES);
        $moodList = implode(', ', self::MOOD_TAGS);

        $system = <<<EOT
Sen kitob bo'yicha xolis va chuqur tahlilchisan. Vazifang — quyida
berilgan MA'LUMOTLAR asosida (hech narsani o'ylab topmasdan) foydalanuvchiga
"bu menga mosmi?" savoliga ISHONARLI javob beradigan tahlil tayyorlash.

QATTIQ QOIDALAR:
1. "difficulty" faqat shu qiymatlardan biri bo'lishi kerak: {$difficultyList}
2. "mood_tags" faqat shu ro'yxatdan 1-3 ta kalit bo'lishi kerak: {$moodList}
   (boshqa so'z ishlatma, faqat shu inglizcha kalitlar)
3. "audience_fit" — HAR BIR TIL uchun ({$localeList}) alohida, 2-3 gapli
   (taxminan 25-45 so'z) HAQIQIY TAHLIL — nega aynan shu kitob o'quvchiga
   mos kelishi mumkinligini chuqurroq tushuntiradi. HECH QANDAY salbiy/
   qo'rqituvchi "kimlar uchun mos emas" qismi YO'Q — faqat ijobiy,
   ishontiruvchi ohangda.
   ENG MUHIM QOIDA — TAKRORLAMASLIK: yuqoridagi "tavsif" maydoni — bu
   DO'KON tomonidan yozilgan mahsulot tavsifi (syujet/mavzu haqida). Sen
   YOZAYOTGAN "audience_fit" USHBU TAVSIFNI TAKRORLAMASLIGI yoki qayta
   ifodalamasligi SHART — bu boshqa, chuqurroq burchak bo'lishi kerak:
   masalan, kitobning KAYFIYATI/USLUBI kimga yoqishi mumkinligi, qanday
   holat/kayfiyatda o'qish yaxshi ta'sir qilishi, qaysi o'quvchi TIPIGA
   (masalan "tez syujetli voqealarni yoqtiradiganlar", "chuqur falsafiy
   mulohaza izlaydiganlar", "amaliy maslahat qidiruvchilar") mos kelishi,
   yoki o'qigandan keyin qanday HISSIY natija/ta'sir qoldirishi haqida
   yoz. Descriptionda bor narsani qayta aytib berish TAQIQLANADI. Umumiy
   ("qiziqarli kitob" kabi) jumla ham TAQIQLANADI — har doim KONKRET va
   sababli bo'lsin.
4. "review_synthesis" — faqat pastdagi community postlar mavjud bo'lsa
   to'ldir (bo'sh bo'lsa, o'sha til uchun null qo'y). O'quvchilar haqiqatan
   yozgan narsani mazmunini ber, o'ylab topma.
5. Har bir matn maydonini {$localeList} tillarining barchasida ber —
   tabiiy, o'sha til so'zlashuvchisi yozgandek, so'zma-so'z tarjima emas.
6. FAQAT toza JSON qaytar, quyidagi formatda:
{
  "difficulty": "...",
  "mood_tags": ["...", "..."],
  "audience_fit": {"uz": "...", "ru": "...", "en": "...", "ja": "..."},
  "review_synthesis": {"uz": "..." | null, "ru": "..." | null, "en": "..." | null, "ja": "..." | null}
}
EOT;

        $user = "MAHSULOT MA'LUMOTLARI:\n{$factsText}\n\nCOMMUNITY POSTLAR/SHARHLAR:\n{$reviewsText}";

        // MUHIM: maxTokens oshirildi (900 → 1300) — "audience_fit" endi
        // 4 tilning har birida 2-3 gapli tahlil (avvalgi 5-10 so'zlik
        // qisqa jumla o'rniga), shuning uchun ko'proq joy kerak.
        $result = $this->ai->askJsonWithMessages([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], maxTokens: 1300, temperature: 0.4);

        return $this->validate($result);
    }

    private function validate(array $result): ?array
    {
        $difficulty = $result['difficulty'] ?? null;
        if (! in_array($difficulty, self::DIFFICULTIES, true)) {
            Log::warning('ReadingInsightGenerator: invalid difficulty', ['raw' => $difficulty]);
            return null;
        }

        $moodTags = array_values(array_intersect(
            (array) ($result['mood_tags'] ?? []),
            self::MOOD_TAGS
        ));

        $audienceFit = $this->normalizeLocaleBag($result['audience_fit'] ?? null);
        $reviewSynthesis = $this->normalizeLocaleBag($result['review_synthesis'] ?? null, allowEmpty: true);

        if (empty($audienceFit)) {
            Log::warning('ReadingInsightGenerator: missing audience_fit, discarding result');
            return null;
        }

        return [
            'difficulty' => $difficulty,
            'mood_tags' => $moodTags,
            'audience_fit' => $audienceFit,
            // MUHIM: 'audience_avoid' ENDI generatsiya qilinmaydi — salbiy/
            // qo'rqituvchi "kimlar uchun mos emas" qismi butunlay olib
            // tashlandi, o'rniga 'audience_fit' chuqurroq (2-3 gapli)
            // tahlilga aylantirildi (yuqoridagi prompt izohiga qarang).
            // Ustun DB'da hali bor (eski yozuvlar uchun, migratsiya
            // qilinmadi), lekin endi har doim bo'sh massiv sifatida
            // yoziladi — shu orqali eski salbiy matn ham regeneratsiyada
            // avtomatik tozalanadi.
            'audience_avoid' => [],
            'review_synthesis' => $reviewSynthesis,
        ];
    }

    private function normalizeLocaleBag($raw, bool $allowEmpty = false): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $bag = [];
        foreach (self::CONTENT_LOCALES as $locale) {
            $value = $raw[$locale] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $bag[$locale] = trim($value);
            }
        }

        return $bag;
    }
}
