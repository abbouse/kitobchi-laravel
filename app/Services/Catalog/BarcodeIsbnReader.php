<?php

namespace App\Services\Catalog;

use App\Services\OpenAIService;
use App\Support\Isbn;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Orqa muqova rasmidan ISBN o'qish (server tomonda, ilovaga ishonmasdan).
 *
 *  1. zbarimg (zbar-tools) — shtrix-kodni aniq o'qiydi, tez (< 1s).
 *  2. OpenAI vision — shtrix-kod ostidagi raqamlarni o'qiydi (zaxira; natija
 *     faqat checksum to'g'ri bo'lsa qabul qilinadi).
 */
class BarcodeIsbnReader
{
    /** @return array{isbn: ?string, method: ?string} */
    public function read(string $publicPath, bool $allowVision = true): array
    {
        $absolute = Storage::disk('public')->path($publicPath);
        if (! is_file($absolute)) {
            return ['isbn' => null, 'method' => null];
        }

        if ($isbn = $this->readWithZbar($absolute)) {
            return ['isbn' => $isbn, 'method' => 'zbar'];
        }

        if ($allowVision && config('catalog.vision_fallback') && ($isbn = $this->readWithVision($absolute))) {
            return ['isbn' => $isbn, 'method' => 'vision'];
        }

        return ['isbn' => null, 'method' => null];
    }

    public function zbarAvailable(): bool
    {
        $binary = (string) config('catalog.zbarimg_path', 'zbarimg');
        $process = new Process([$binary, '--version']);
        try {
            $process->setTimeout(3)->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function readWithZbar(string $absolute): ?string
    {
        $binary = (string) config('catalog.zbarimg_path', 'zbarimg');
        $process = new Process([$binary, '--quiet', '--raw', '-Sdisable', '-Sean13.enable', '-Sisbn13.enable', '-Sisbn10.enable', $absolute]);

        try {
            $process->setTimeout(8)->run();
        } catch (\Throwable $e) {
            Log::debug('zbarimg unavailable: ' . $e->getMessage());

            return null;
        }

        foreach (preg_split('/\R/', trim($process->getOutput())) ?: [] as $line) {
            if ($isbn = Isbn::toIsbn13($line)) {
                return $isbn;
            }
        }

        return null;
    }

    private function readWithVision(string $absolute): ?string
    {
        try {
            $mime = mime_content_type($absolute) ?: 'image/jpeg';
            $dataUrl = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolute));
            $answer = app(OpenAIService::class)->askVision(
                $dataUrl,
                'This is the back cover of a book. Read the ISBN printed near the barcode. '
                . 'Reply with the 13 digits only (no spaces or dashes), or NONE if there is no readable ISBN.',
                40,
                0.0
            );
        } catch (\Throwable $e) {
            Log::debug('Vision ISBN read failed: ' . $e->getMessage());

            return null;
        }

        if (preg_match_all('/97[89][\d\-\s]{10,16}/', (string) $answer, $matches)) {
            foreach ($matches[0] as $candidate) {
                if ($isbn = Isbn::toIsbn13($candidate)) {
                    return $isbn;
                }
            }
        }

        return null;
    }
}
