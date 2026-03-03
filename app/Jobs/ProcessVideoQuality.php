<?php

namespace App\Jobs;

use App\Models\ReelItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessVideoQuality implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ReelItem $reelItem;
    protected string $videoPath;

    /**
     * Job timeout - 1 soat
     */
    public $timeout = 3600;

    /**
     * Qayta urinishlar soni
     */
    public $tries = 3;

    public function __construct(ReelItem $reelItem, string $videoPath)
    {
        $this->reelItem = $reelItem;
        $this->videoPath = $videoPath;
    }

    public function handle(): void
    {
        try {
            Log::info("Video konvertatsiya boshlandi: {$this->videoPath}");

            $fullPath = Storage::disk('public')->path($this->videoPath);

            // FFMpeg binary yo'llarini tekshirish
            if (!file_exists('/usr/bin/ffmpeg')) {
                throw new \Exception('FFmpeg topilmadi. Iltimos, servergа o\'rnating.');
            }

            // FFMpeg ni sozlash
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',
                'timeout'          => 3600,
                'ffmpeg.threads'   => 12,
            ]);

            $video = $ffmpeg->open($fullPath);

            // Fayl ma'lumotlarini olish
            $pathInfo = pathinfo($this->videoPath);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];

            // Turli sifatlarda konvertatsiya
            $conversions = [
                '720p' => ['height' => 720, 'crf' => 23, 'bitrate' => 2500],
                '480p' => ['height' => 480, 'crf' => 28, 'bitrate' => 1000],
                '360p' => ['height' => 360, 'crf' => 32, 'bitrate' => 600],
            ];

            foreach ($conversions as $quality => $settings) {
                $this->convertVideo(
                    $video,
                    $directory,
                    $filename,
                    $quality,
                    $settings['height'],
                    $settings['crf'],
                    $settings['bitrate']
                );
                
                Log::info("Video konvertatsiya qilindi: {$quality}");
            }

            // Original videoni 720p deb qayta nomlash
            $newPath = "{$directory}/{$filename}_720p.mp4";
            if (Storage::disk('public')->exists($this->videoPath)) {
                Storage::disk('public')->move($this->videoPath, $newPath);
            }

            // Database ni yangilash
            $this->reelItem->update(['video_url' => $newPath]);

            Log::info("Video konvertatsiya muvaffaqiyatli yakunlandi: {$this->videoPath}");

        } catch (\Exception $e) {
            Log::error("Video konvertatsiya xatosi: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Video konvertatsiya qilish
     */
    protected function convertVideo(
        $video,
        string $directory,
        string $filename,
        string $quality,
        int $height,
        int $crf,
        int $bitrate
    ): void {
        $outputPath = Storage::disk('public')->path("{$directory}/{$filename}_{$quality}.mp4");

        // Format sozlamalari
        $format = new \FFMpeg\Format\Video\X264('aac');
        $format->setKiloBitrate($bitrate)
               ->setAudioKiloBitrate(128);

        // Qo'shimcha parametrlar
        $format->setAdditionalParameters([
            '-crf', (string) $crf,
            '-preset', 'medium',
            '-vf', "scale=-2:$height",  // Aspect ratio ni saqlash
            '-movflags', '+faststart',  // Progressive download
            '-pix_fmt', 'yuv420p',      // Keng qo'llab-quvvatlash
        ]);

        // Konvertatsiya
        $video->save($format, $outputPath);
    }

    /**
     * Job fail bo'lganda
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Video konvertatsiya job fail: {$this->videoPath}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}