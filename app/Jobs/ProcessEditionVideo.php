<?php

namespace App\Jobs;

use App\Models\BookEditionVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Mahsulot videosini mobil uchun tayyorlaydi:
 *  - SD 480p (H.264 main, ~800 kbit/s, AAC 64k) — sekin internetda ham tez;
 *  - HD 720p (~2 Mbit/s) — Wi‑Fi uchun;
 *  - poster (1-soniyadagi kadr) — video yuklanguncha darhol ko'rinadi.
 * Har ikkala faylda `+faststart` va 2 soniyalik kalit kadrlar: o'ynash
 * birinchi baytlardanoq boshlanadi, oldinga o'tkazish ham tez.
 */
class ProcessEditionVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 2;

    public function __construct(public int $videoId)
    {
    }

    public function handle(): void
    {
        $video = BookEditionVideo::query()->find($this->videoId);
        if (! $video || ! $video->original_path) {
            return;
        }

        $disk = Storage::disk('public');
        $source = $disk->path($video->original_path);
        if (! is_file($source)) {
            $video->update(['status' => BookEditionVideo::STATUS_FAILED, 'error' => 'Asl fayl topilmadi']);

            return;
        }

        $ffmpeg = config('services.ffmpeg.binary', '/usr/bin/ffmpeg');
        $ffprobe = config('services.ffmpeg.probe', '/usr/bin/ffprobe');
        $dir = "edition-videos/{$video->edition_id}";
        $stamp = now()->format('YmdHis');
        $sd = "{$dir}/sd_{$stamp}.mp4";
        $hd = "{$dir}/hd_{$stamp}.mp4";
        $poster = "{$dir}/poster_{$stamp}.jpg";
        $disk->makeDirectory($dir);

        try {
            $meta = $this->probe($ffprobe, $source);
            $srcHeight = $meta['height'] ?? 720;

            $this->encode($ffmpeg, $source, $disk->path($sd), min(480, $srcHeight), 800, 1200, 64);
            $this->encode($ffmpeg, $source, $disk->path($hd), min(720, $srcHeight), 2000, 2800, 96);
            $this->run([
                $ffmpeg, '-y', '-ss', (($meta['duration'] ?? 0) > 2 ? '1' : '0'), '-i', $source,
                '-frames:v', '1', '-vf', 'scale=-2:min(720\\,ih)', '-q:v', '4', $disk->path($poster),
            ], 300);

            // Ishlov davomida admin videoni o'chirgan yoki almashtirgan bo'lsa —
            // tayyorlangan fayllar yetim qolmasin
            $fresh = BookEditionVideo::query()->find($video->id);
            if (! $fresh || $fresh->original_path !== $video->original_path) {
                $disk->delete([$sd, $hd, $poster]);
                if (! $fresh) {
                    $disk->delete($video->original_path);
                    if ($disk->exists($dir) && empty($disk->allFiles($dir))) {
                        $disk->deleteDirectory($dir);
                    }
                }

                return;
            }
            $video = $fresh;

            $old = [$video->sd_path, $video->hd_path, $video->poster_path, $video->original_path];
            $sdMeta = $this->probe($ffprobe, $disk->path($sd));

            $video->update([
                'status' => BookEditionVideo::STATUS_READY,
                'sd_path' => $sd,
                'hd_path' => $hd,
                'poster_path' => $poster,
                'duration' => (int) round($meta['duration'] ?? 0),
                'width' => $sdMeta['width'] ?? null,
                'height' => $sdMeta['height'] ?? null,
                'sd_size' => @filesize($disk->path($sd)) ?: null,
                'hd_size' => @filesize($disk->path($hd)) ?: null,
                'original_path' => null,
                'error' => null,
            ]);

            // Eski variantlar va asl (katta) fayl endi kerak emas
            foreach (array_filter($old) as $path) {
                if (! in_array($path, [$sd, $hd, $poster], true)) {
                    $disk->delete($path);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Edition video processing failed', ['video' => $video->id, 'error' => $e->getMessage()]);
            foreach ([$sd, $hd, $poster] as $path) {
                $disk->delete($path);
            }
            $video->update([
                'status' => BookEditionVideo::STATUS_FAILED,
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);
        }
    }

    private function encode(string $ffmpeg, string $in, string $out, int $height, int $kbps, int $maxKbps, int $audioKbps): void
    {
        $height = max(240, $height - ($height % 2));
        $this->run([
            $ffmpeg, '-y', '-i', $in,
            '-vf', "scale=-2:{$height}",
            '-c:v', 'libx264', '-preset', 'medium', '-profile:v', 'main', '-pix_fmt', 'yuv420p',
            '-b:v', "{$kbps}k", '-maxrate', "{$maxKbps}k", '-bufsize', ($maxKbps * 2).'k',
            '-g', '48', '-keyint_min', '48', '-sc_threshold', '0',
            '-c:a', 'aac', '-b:a', "{$audioKbps}k", '-ac', '2',
            '-movflags', '+faststart',
            $out,
        ], 3000);
    }

    private function probe(string $ffprobe, string $file): array
    {
        try {
            $out = $this->run([
                $ffprobe, '-v', 'error', '-select_streams', 'v:0',
                '-show_entries', 'stream=width,height:format=duration', '-of', 'json', $file,
            ], 60);
            $json = json_decode($out, true) ?: [];

            return [
                'width' => (int) ($json['streams'][0]['width'] ?? 0) ?: null,
                'height' => (int) ($json['streams'][0]['height'] ?? 0) ?: null,
                'duration' => (float) ($json['format']['duration'] ?? 0),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    private function run(array $cmd, int $timeout): string
    {
        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim(mb_substr($process->getErrorOutput(), -800)) ?: 'ffmpeg xatosi');
        }

        return $process->getOutput();
    }

    public function failed(\Throwable $e): void
    {
        BookEditionVideo::query()->whereKey($this->videoId)->update([
            'status' => BookEditionVideo::STATUS_FAILED,
            'error' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
