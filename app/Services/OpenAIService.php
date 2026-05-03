<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected \OpenAI\Client $client;
    protected string $chatModel  = 'gpt-4o-mini';
    protected string $embedModel = 'text-embedding-3-small';

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

    // ─── Embedding ───────────────────────────────────────────────────────────

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
        if (!empty($data['shop_name']))   $parts[] = "Do'kon: " . $data['shop_name'];
        if (!empty($data['description'])) $parts[] = 'Tavsif: ' . mb_substr($data['description'], 0, 500);

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
