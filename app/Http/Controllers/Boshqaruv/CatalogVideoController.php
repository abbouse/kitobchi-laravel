<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEditionVideo;
use App\Models\BookEdition;
use App\Models\BookEditionVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Global kitobga (nashrga) bitta mahsulot videosini biriktirish.
 * Yuklangan fayl navbatda SD/HD va posterga aylantiriladi.
 */
class CatalogVideoController extends Controller
{
    public function store(Request $request, int $edition): RedirectResponse
    {
        $model = BookEdition::query()->findOrFail($edition);
        $request->validate([
            // 300 MB gacha: mp4, mov, webm, mkv
            'video' => ['required', 'file', 'max:307200', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v'],
        ], [
            'video.max' => "Video 300 MB dan oshmasligi kerak",
            'video.mimetypes' => "Faqat MP4, MOV, WEBM yoki MKV video yuklash mumkin",
        ]);

        $file = $request->file('video');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $path = $file->storeAs("edition-videos/{$model->id}", 'original_'.now()->format('YmdHis').'.'.$ext, 'public');

        $video = BookEditionVideo::query()->firstOrNew(['edition_id' => $model->id]);
        // Avvalgi qayta ishlanmagan asl fayl qolib ketmasin
        if ($video->original_path && $video->original_path !== $path) {
            Storage::disk('public')->delete($video->original_path);
        }
        $video->fill([
            'status' => BookEditionVideo::STATUS_PROCESSING,
            'original_path' => $path,
            'error' => null,
            'uploaded_by' => Auth::guard('panel')->id(),
        ])->save();

        ProcessEditionVideo::dispatch($video->id);

        return back()->with('success', "Video yuklandi. Mobil uchun tayyorlanmoqda — bir necha daqiqada ilovada chiqadi.");
    }

    public function retry(int $edition): RedirectResponse
    {
        $video = BookEditionVideo::query()->where('edition_id', $edition)->firstOrFail();
        if (! $video->original_path) {
            return back()->with('error', "Asl fayl yo'q — videoni qayta yuklang");
        }
        $video->update(['status' => BookEditionVideo::STATUS_PROCESSING, 'error' => null]);
        ProcessEditionVideo::dispatch($video->id);

        return back()->with('success', 'Qayta ishlash boshlandi');
    }

    public function destroy(int $edition): RedirectResponse
    {
        $video = BookEditionVideo::query()->where('edition_id', $edition)->first();
        if ($video) {
            $video->delete();
        }
        // Papka bilan birga: asl fayl, 480p, 720p, poster va ishlov jarayonida
        // qolgan har qanday oraliq fayl
        Storage::disk('public')->deleteDirectory("edition-videos/{$edition}");

        return back()->with('success', "Video o'chirildi");
    }

    /** Boshqaruv sahifasi uchun holat. */
    public static function panelPayload(int $editionId): ?array
    {
        $video = BookEditionVideo::query()->where('edition_id', $editionId)->first();
        if (! $video) {
            return null;
        }

        return [
            'status' => $video->status,
            'sdUrl' => BookEditionVideo::url($video->sd_path),
            'hdUrl' => BookEditionVideo::url($video->hd_path),
            'posterUrl' => BookEditionVideo::url($video->poster_path),
            'duration' => $video->duration,
            'sdSize' => $video->sd_size,
            'hdSize' => $video->hd_size,
            'error' => $video->error,
            'canRetry' => (bool) $video->original_path,
            'updatedAt' => $video->updated_at?->format('d.m.Y H:i'),
        ];
    }
}
