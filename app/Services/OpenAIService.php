<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected \OpenAI\Client $client;
    protected string $chatModel   = 'gpt-4o-mini';
    protected string $visionModel = 'gpt-4o-mini';
    protected string $embedModel  = 'text-embedding-3-small';

    /** Query embedding cache muddati (sekund) */
    protected int $embedCacheTtl = 86400;

    public function __construct()
    {
        $apiKey = (string) config('services.openai.key', '');

        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured. Expected config("services.openai.key").');
        }

        $this->client = \OpenAI::client($apiKey);
    }

    // ─── Oddiy matn ─────────────────────────────────────────────────────────

    public function askSimple(string $prompt, int $maxTokens = 400, float $temperature = 0.7): string
    {
        return $this->askSimpleWithMessages(
            [['role' => 'user', 'content' => $prompt]],
            $maxTokens,
            $temperature
        );
    }

    public function askSimpleWithMessages(array $messages, int $maxTokens = 400, float $temperature = 0.7): string
    {
        try {
            $response = $this->client->chat()->create([
                'model'       => $this->chatModel,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
                'messages'    => $messages,
            ]);

            return $response->choices[0]->message->content ?? 'Javob topilmadi.';
        } catch (\Throwable $e) {
            Log::error('OpenAI askSimple error: ' . $e->getMessage());
            return "Kechirasiz, hozir biroz muammo chiqdi \xf0\x9f\x98\x85 Yana bir bor yozib ko'ring.";
        }
    }

    // ─── JSON — oddiy prompt ─────────────────────────────────────────────────

    public function askJson(string $prompt, int $maxTokens = 800, float $temperature = 0.3): array
    {
        return $this->askJsonWithMessages([
            ['role' => 'system', 'content' => "Faqat toza JSON qaytar. Hech qanday qo'shimcha matn, markdown yoki izoh bo'lmasin."],
            ['role' => 'user',   'content' => $prompt],
        ], $maxTokens, $temperature);
    }

    // ─── JSON — to'liq messages array bilan ─────────────────────────────────
    //
    // JSON parse xatoligi hal qilindi:
    //   1. response_format:json_object — OpenAI darajasida JSON majburiy
    //   2. Agar parse xato bersa — regex extraction
    //   3. Agar u ham bo'lmasa  — 1 marta retry (modelga eslatamiz)
    //   4. Retry ham muvaffaqiyatsiz — xavfsiz fallback
    // ────────────────────────────────────────────────────────────────────────

    public function askJsonWithMessages(array $messages, int $maxTokens = 800, float $temperature = 0.3): array
    {
        $attempt = 0;

        while ($attempt < 2) {
            $attempt++;

            try {
                $response = $this->client->chat()->create([
                    'model'           => $this->chatModel,
                    'max_tokens'      => $maxTokens,
                    'temperature'     => $temperature,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => $messages,
                ]);

                $raw = trim($response->choices[0]->message->content ?? '{}');

                Log::debug('OpenAI Raw JSON', [
                    'attempt' => $attempt,
                    'raw'     => mb_substr($raw, 0, 600),
                ]);

                $decoded = $this->parseJson($raw);

                if ($decoded !== null) {
                    return $decoded;
                }

                Log::warning('JSON parse failed, will retry', [
                    'attempt' => $attempt,
                    'raw'     => mb_substr($raw, 0, 300),
                ]);

                $messages[] = ['role' => 'assistant', 'content' => $raw];
                $messages[] = [
                    'role'    => 'user',
                    'content' => "FAQAT toza JSON qaytarib ber. Markdown, izoh yoki qo'shimcha matn bo'lmasin.",
                ];

            } catch (\Throwable $e) {
                Log::error('OpenAI JSON request failed', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $this->jsonFallback();
    }

    // ─── JSON parsing ────────────────────────────────────────────────────────

    private function parseJson(string $raw): ?array
    {
        if (empty($raw)) return null;

        $clean = preg_replace('/^\\xEF\\xBB\\xBF/', '', $raw);
        $clean = trim($clean);
        $clean = preg_replace('/^```(?:json)?\\s*/i', '', $clean);
        $clean = preg_replace('/\\s*```\\s*$/', '',     $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\\{[\\s\\S]*\\}/u', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                Log::info('JSON extracted via regex');
                return $decoded;
            }
        }

        $fixed   = preg_replace('/,\\s*([\\}\\]])/m', '$1', $clean);
        $decoded = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::info('JSON fixed via trailing comma removal');
            return $decoded;
        }

        Log::error('JSON parse completely failed', [
            'json_error' => json_last_error_msg(),
            'sample'     => mb_substr($raw, 0, 300),
        ]);

        return null;
    }

    private function jsonFallback(): array
    {
        return [
            'content'        => "Kechirasiz, biroz chalkashib qoldim \xf0\x9f\x98\x85 Yana bir bor yozingmi?",
            'status'         => 'negotiating',
            'proposed_price' => 0,
            'items'          => [],
        ];
    }

    // ─── Vision — rasm tahlili ───────────────────────────────────────────────

    /**
     * Rasmni tahlil qilib, mahsulot qidiruvi uchun strukturali JSON qaytaradi.
     *
     * @param string $imageDataUrl "data:image/jpeg;base64,..." formatidagi rasm
     * @param string $userText     Foydalanuvchi rasm bilan yuborgan matn (bo'lishi shart emas)
     *
     * @return array{
     *   product_type: string,       // 'book' | 'stationery' | 'other'
     *   title: ?string,             // kitob nomi / mahsulot nomi
     *   author: ?string,            // muallif (agar kitob bo'lsa)
     *   text_on_image: ?string,     // rasmda ko'ringan matn
     *   keywords: array,            // qidiruv kalit so'zlari
     *   search_query: string,       // vector qidiruv uchun tayyor matn
     *   description: string         // rasm tavsifi
     * }
     */
    public function analyzeProductImage(string $imageDataUrl, string $userText = ''): array
    {
        $instruction = <<<EOT
Sen rasmdagi mahsulotni aniqlaydigan yordamchisan. Rasm kitob do'koni (kitoblar va kanselyariya) konteksti uchun tahlil qilinadi.
Rasmni diqqat bilan ko'r: muqovadagi matnni (nom, muallif) o'qi, mahsulot turini aniqla.
FAQAT quyidagi JSON formatida javob ber:
{
  "product_type": "book" yoki "stationery" yoki "other",
  "title": "kitob/mahsulot nomi yoki null",
  "author": "muallif ismi yoki null",
  "isbn": "rasmda ISBN raqami yoki shtrix-kod ostidagi raqam ko'rinsa (masalan 978-...) yoki null",
  "text_on_image": "rasmda ko'ringan asosiy matn yoki null",
  "keywords": ["qidiruv", "uchun", "kalit", "so'zlar"],
  "search_query": "mahsulotni topish uchun eng yaxshi qidiruv matni (nom + muallif + tur)",
  "description": "rasmning qisqa tavsifi (1-2 gap)"
}
Kitob muqovasi bo'lsa: title va author ni aniq o'qishga harakat qil.
Kitobning orqa muqovasida ISBN/shtrix-kod bo'lsa — raqamini aynan o'qib "isbn" ga yoz.
Kanselyariya bo'lsa (qalam, daftar, ruchka, sumka...): turini va rangini keywords ga yoz.
EOT;

        if (trim($userText) !== '') {
            $instruction .= "\n\nFoydalanuvchi rasm bilan shu xabarni yubordi (kontekst sifatida ishlat): \"" .
                mb_substr($userText, 0, 300) . '"';
        }

        try {
            $response = $this->client->chat()->create([
                'model'           => $this->visionModel,
                'max_tokens'      => 500,
                'temperature'     => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $instruction],
                            [
                                'type'      => 'image_url',
                                'image_url' => ['url' => $imageDataUrl, 'detail' => 'high'],
                            ],
                        ],
                    ],
                ],
            ]);

            $raw     = trim($response->choices[0]->message->content ?? '{}');
            $decoded = $this->parseJson($raw);

            if ($decoded !== null) {
                return $this->normalizeImageAnalysis($decoded);
            }
        } catch (\Throwable $e) {
            Log::error('OpenAI vision error: ' . $e->getMessage());
        }

        return $this->normalizeImageAnalysis([]);
    }

    /**
     * Rasm + matn + suhbat tarixi bilan erkin javob (vision chat).
     */
    public function askVision(string $imageDataUrl, string $prompt, int $maxTokens = 400, float $temperature = 0.5): string
    {
        try {
            $response = $this->client->chat()->create([
                'model'       => $this->visionModel,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
                'messages'    => [
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            [
                                'type'      => 'image_url',
                                'image_url' => ['url' => $imageDataUrl, 'detail' => 'low'],
                            ],
                        ],
                    ],
                ],
            ]);

            return $response->choices[0]->message->content ?? '';
        } catch (\Throwable $e) {
            Log::error('OpenAI askVision error: ' . $e->getMessage());
            return '';
        }
    }

    private function normalizeImageAnalysis(array $data): array
    {
        $type = strtolower(trim((string) ($data['product_type'] ?? 'other')));
        if (! in_array($type, ['book', 'stationery', 'other'], true)) {
            $type = 'other';
        }

        $keywords = array_values(array_filter(array_map(
            fn ($k) => trim((string) $k),
            is_array($data['keywords'] ?? null) ? $data['keywords'] : []
        )));

        $title  = filled($data['title'] ?? null) ? trim((string) $data['title']) : null;
        $author = filled($data['author'] ?? null) ? trim((string) $data['author']) : null;

        // ISBN: faqat raqam va X qoldiramiz, 10 yoki 13 xonali bo'lsa qabul qilinadi
        $isbn = null;
        if (filled($data['isbn'] ?? null)) {
            $cleaned = preg_replace('/[^0-9Xx]/', '', (string) $data['isbn']);
            if (in_array(strlen($cleaned), [10, 13], true)) {
                $isbn = strtoupper($cleaned);
            }
        }

        $searchQuery = trim((string) ($data['search_query'] ?? ''));
        if ($searchQuery === '') {
            $searchQuery = trim(implode(' ', array_filter([$title, $author, implode(' ', $keywords)])));
        }

        return [
            'product_type'  => $type,
            'title'         => $title,
            'author'        => $author,
            'isbn'          => $isbn,
            'text_on_image' => filled($data['text_on_image'] ?? null) ? trim((string) $data['text_on_image']) : null,
            'keywords'      => $keywords,
            'search_query'  => $searchQuery,
            'description'   => trim((string) ($data['description'] ?? '')),
        ];
    }

    // ─── Embedding ───────────────────────────────────────────────────────────

    /**
     * Qidiruv so'rovlari uchun cache'langan embedding.
     * Bir xil so'rov qayta yuborilsa OpenAI ga murojaat qilinmaydi.
     */
    public function getCachedVector(string $text): array
    {
        $normalized = mb_strtolower(trim($text));

        if ($normalized === '') {
            return [];
        }

        $key = 'embed:q:' . md5($normalized);

        return Cache::remember($key, $this->embedCacheTtl, fn () => $this->getVector($normalized));
    }

    public function getVector(string $text): array
    {
        if (trim($text) === '') {
            return array_fill(0, 1536, 0.0);
        }

        try {
            $response = $this->client->embeddings()->create([
                'model' => $this->embedModel,
                'input' => mb_substr($text, 0, 8000),
            ]);

            return $response->embeddings[0]->embedding ?? array_fill(0, 1536, 0.0);
        } catch (\Throwable $e) {
            Log::error('Embedding error: ' . $e->getMessage());
            return array_fill(0, 1536, 0.0);
        }
    }

    // ─── Cosine similarity ───────────────────────────────────────────────────

    public function calculateSimilarity(array $vec1, array $vec2): float
    {
        if (empty($vec1) || empty($vec2)) return 0.0;

        $dot = $mag1 = $mag2 = 0.0;
        $len = min(count($vec1), count($vec2));

        for ($i = 0; $i < $len; $i++) {
            $dot  += $vec1[$i] * $vec2[$i];
            $mag1 += $vec1[$i] ** 2;
            $mag2 += $vec2[$i] ** 2;
        }

        $denom = sqrt($mag1) * sqrt($mag2);
        return $denom > 0 ? $dot / $denom : 0.0;
    }

    // ─── Mahsulot embed matni ────────────────────────────────────────────────

    public function buildProductEmbedText(array $data): string
    {
        $parts = [];

        if (!empty($data['name']))        $parts[] = 'Nomi: ' . $data['name'];
        if (!empty($data['author']))      $parts[] = 'Muallif: ' . $data['author'];
        if (!empty($data['category']))    $parts[] = 'Kategoriya: ' . $data['category'];
        if (!empty($data['tags']))        $parts[] = 'Teglar: ' . implode(', ', (array) $data['tags']);
        if (!empty($data['artikul']))     $parts[] = 'Artikul: ' . $data['artikul'];
        if (!empty($data['lang']))        $parts[] = 'Tili: ' . $data['lang'];
        if (!empty($data['year']))        $parts[] = 'Yili: ' . $data['year'];
        if (!empty($data['coverType']))   $parts[] = 'Muqova: ' . $data['coverType'];
        if (!empty($data['material']))    $parts[] = 'Material: ' . $data['material'];
        if (!empty($data['publisher']))   $parts[] = 'Nashriyot: ' . $data['publisher'];
        if (!empty($data['shop_name']))   $parts[] = "Do'kon: " . $data['shop_name'];
        if (!empty($data['description'])) $parts[] = 'Tavsif: ' . mb_substr($data['description'], 0, 500);

        // Narx darajasi — "arzon kitob" kabi so'rovlar uchun semantik signal
        $price = (float) ($data['price'] ?? 0);
        if ($price > 0) {
            $priceLabel = match (true) {
                $price < 30000  => 'juda arzon',
                $price < 60000  => 'arzon',
                $price < 120000 => "o'rtacha narx",
                $price < 250000 => 'qimmatroq',
                default         => 'premium narx',
            };
            $parts[] = 'Narx: ' . number_format($price) . " so'm ({$priceLabel})";
        }

        $sales     = (int) ($data['totalSales']     ?? 0);
        $salesWeek = (int) ($data['totalSalesWeek'] ?? 0);

        if ($sales > 0) {
            $label = match (true) {
                $sales > 1000 => 'juda mashhur bestseller',
                $sales > 500  => "ko'p sotilgan",
                $sales > 100  => "o'rtacha mashhur",
                default       => 'yangi mahsulot',
            };
            $parts[] = "Jami savdo: {$sales} ta ({$label})";
        }

        if ($salesWeek > 0) {
            $weekLabel = match (true) {
                $salesWeek > 100 => 'haftalik trendda',
                $salesWeek > 30  => 'haftalik mashhur',
                $salesWeek > 10  => 'haftalik faol',
                default          => 'haftalik savdo bor',
            };
            $parts[] = "Haftalik savdo: {$salesWeek} ta ({$weekLabel})";
        }

        return implode('. ', $parts);
    }
}
