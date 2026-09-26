<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\BookVideoSet;
use App\Services\BookVideoSelectionService;
use App\Support\ProductVisibilityScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * KITOB VIDEOLARI — haftalik / oylik e'lon videolari.
 *
 * Tizim joriy davr uchun kitoblarni avtomatik tanlaydi, admin tarkibini,
 * tartibini, sarlavhasini va shablonini o'zgartiradi. Video brauzerda
 * (canvas + MediaRecorder) musiqa va ovoz effektlari bilan yig'iladi va yuklab olinadi.
 */
class BookVideoController extends Controller
{
    public function __construct(private BookVideoSelectionService $selection)
    {
    }

    public function index(Request $request): Response
    {
        $period = in_array($request->query('period'), BookVideoSet::PERIODS, true)
            ? (string) $request->query('period')
            : BookVideoSet::PERIOD_WEEKLY;

        $current = $this->selection->currentSet($period);
        $set = $request->filled('set')
            ? BookVideoSet::query()->where('period', $period)->find((int) $request->query('set')) ?? $current
            : $current;

        $history = BookVideoSet::query()
            ->where('period', $period)
            ->orderByDesc('period_start')
            ->limit(12)
            ->get(['id', 'period_start', 'period_end', 'title'])
            ->map(fn (BookVideoSet $s) => [
                'id' => $s->id,
                'label' => $s->period_start->format('d.m.Y') . ' – ' . $s->period_end->format('d.m.Y'),
                'title' => $s->title,
            ]);

        return Inertia::render('BookVideos', [
            'period' => $period,
            'set' => [
                'id' => $set->id,
                'title' => $set->title,
                'template' => $set->template,
                'periodLabel' => $set->period_start->format('d.m') . ' – ' . $set->period_end->format('d.m.Y'),
                'isCustomized' => (bool) $set->is_customized,
                'books' => $this->selection->cards(array_map('intval', $set->book_ids ?? [])),
                'updateUrl' => route('boshqaruv.book-videos.update', $set->id),
                'autoPickUrl' => route('boshqaruv.book-videos.auto', $set->id),
            ],
            'history' => $history,
            'templates' => BookVideoSet::TEMPLATES,
            'maxBooks' => BookVideoSet::MAX_BOOKS,
            'searchUrl' => route('boshqaruv.book-videos.search'),
            'convertUrl' => route('boshqaruv.book-videos.convert'),
            'assets' => [
                'icon' => asset('favicon.svg'),
                'appStore' => asset('images/icons/app-store-badge.svg'),
                'googlePlay' => asset('images/icons/google-play-badge.svg'),
            ],
        ]);
    }

    public function update(Request $request, BookVideoSet $set): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'template' => ['required', Rule::in(BookVideoSet::TEMPLATES)],
            'book_ids' => 'required|array|min:1|max:' . BookVideoSet::MAX_BOOKS,
            'book_ids.*' => 'integer|distinct|exists:books,id',
        ]);

        $set->update([
            'title' => $data['title'],
            'template' => $data['template'],
            'book_ids' => array_map('intval', $data['book_ids']),
            'is_customized' => true,
            'updated_by' => Auth::guard('panel')->id(),
        ]);

        return back()->with('success', 'Video tarkibi saqlandi.');
    }

    /** Tarkibni qaytadan avtomatik tanlaydi (admin o'zgarishlari bekor qilinadi). */
    public function autoPick(BookVideoSet $set): RedirectResponse
    {
        $set->update([
            'book_ids' => $this->selection->autoPick($set->period),
            'is_customized' => false,
            'updated_by' => Auth::guard('panel')->id(),
        ]);

        return back()->with('success', 'Kitoblar qaytadan avtomatik tanlandi.');
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $ids = ProductVisibilityScope::applyBooks(Books::query())
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('author', 'like', "%{$q}%")
                ->orWhere('isbn', $q)
                ->orWhere('artikul', $q))
            ->orderByDesc('totalSales')
            ->limit(15)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return response()->json(['items' => $this->selection->cards($ids)]);
    }

    /**
     * Brauzer MP4 yozolmasa (H.264 yo'q), yozilgan WebM serverdagi ffmpeg bilan
     * Instagram qabul qiladigan MP4 (H.264 + AAC, yuv420p, faststart) ga o'giriladi.
     */
    public function convert(Request $request): BinaryFileResponse|JsonResponse
    {
        $request->validate(['video' => 'required|file|max:204800']);

        $ffmpeg = config('services.ffmpeg_binary', '/usr/bin/ffmpeg');
        if (! is_executable($ffmpeg)) {
            return response()->json(['message' => "Serverda ffmpeg topilmadi — videoni Google Chrome'da yozing."], 422);
        }

        $dir = storage_path('app/tmp/book-videos');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $id = bin2hex(random_bytes(8));
        $in = "{$dir}/{$id}.webm";
        $out = "{$dir}/{$id}.mp4";
        $request->file('video')->move($dir, "{$id}.webm");

        $result = Process::timeout(180)->run([
            $ffmpeg, '-y', '-loglevel', 'error', '-i', $in,
            '-c:v', 'libx264', '-preset', 'medium', '-crf', '18',
            '-pix_fmt', 'yuv420p', '-r', '30', '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart', $out,
        ]);
        @unlink($in);

        if (! $result->successful() || ! is_file($out)) {
            @unlink($out);

            return response()->json(['message' => "Videoni MP4 ga o'girib bo'lmadi."], 422);
        }

        return response()->download($out, 'kitobchi-video.mp4', ['Content-Type' => 'video/mp4'])->deleteFileAfterSend();
    }

    /**
     * Kitob rasmini boshqaruv domenidan beradi — canvas tashqi rasm bilan
     * "tainted" bo'lib qolsa videoni yozib bo'lmaydi.
     */
    public function image(Books $book): HttpResponse
    {
        $url = $this->selection->firstImageUrl($book);
        abort_if($url === null, 404);

        $headers = ['Cache-Control' => 'private, max-age=86400'];
        $storagePrefix = rtrim(asset('storage'), '/') . '/';

        if (str_starts_with($url, $storagePrefix)) {
            $path = rawurldecode(substr($url, strlen($storagePrefix)));
            abort_unless(Storage::disk('public')->exists($path), 404);

            return response()->file(Storage::disk('public')->path($path), $headers);
        }

        // Faqat tashqi https rasm (masalan import qilingan katalog rasmi) proksilanadi
        abort_unless(str_starts_with($url, 'https://'), 404);
        $remote = Http::timeout(10)->get($url);
        abort_unless($remote->successful() && str_starts_with((string) $remote->header('Content-Type'), 'image/'), 404);

        return response($remote->body(), 200, $headers + ['Content-Type' => $remote->header('Content-Type')]);
    }
}
