<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    // Siz keltirgan misoldagi v1beta va model nomi
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent';

    public function __construct()
    {
        $this->apiKey = config('gemini.api_key') ?? env('GEMINI_API_KEY');
    }

    public function askSimple(string $text): string
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey, 
            ])->post($this->baseUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'max_output_tokens' => 300,
                    'temperature' => 0.7,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Javob topilmadi.";
            }

            Log::error("Gemini 3 API Error: " . $response->body());
            
            return $this->fallbackTo15($text);

        } catch (\Exception $e) {
            Log::error("Gemini Service Exception: " . $e->getMessage());
            return "Aloqa muvaffaqiyatsiz tugadi.";
        }
    }

    private function fallbackTo15($text)
    {
        $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $this->apiKey;
        $response = Http::post($url, [
            'contents' => [['parts' => [['text' => $text]]]]
        ]);
        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? "Xatolik.";
    }

    public function askJson(string $text): array
{
    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ])->post($this->baseUrl, [
            'contents' => [['parts' => [['text' => $text]]]],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $resText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            return json_decode($resText, true) ?? [];
        }
        return [];
    } catch (\Exception $e) {
        return [];
    }
}

    // Embedding uchun ham to'g'ridan-to'g'ri HTTP dan foydalanamiz
    public function getVector(string $text): array
{
    try {
        // URL'ni tozalang
        $url = "https://generativelanguage.googleapis.com/v1/models/text-embedding-004:embedContent?key=" . $this->apiKey;
        
        $response = Http::post($url, [
            'model' => 'models/text-embedding-004', // Ba'zi versiyalarda model nomi talab qilinadi
            'content' => ['parts' => [['text' => $text]]]
        ]);

        if ($response->successful()) {
            return $response->json()['embedding']['values'] ?? array_fill(0, 768, 0.0);
        }
        
        Log::error("Embedding Error: " . $response->body());
        return array_fill(0, 768, 0.0);
    } catch (\Exception $e) {
        return array_fill(0, 768, 0.0);
    }
}

    public function calculateSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0; $mag1 = 0; $mag2 = 0;
        foreach ($vec1 as $i => $val) {
            if (isset($vec2[$i])) {
                $dotProduct += $val * $vec2[$i];
                $mag1 += $val ** 2;
                $mag2 += $vec2[$i] ** 2;
            }
        }
        return ($mag1 && $mag2) ? $dotProduct / (sqrt($mag1) * sqrt($mag2)) : 0;
    }
}