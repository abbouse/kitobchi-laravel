<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BotTicketAttachment;
use App\Models\SupportTemplate;
use App\Services\Support\SupportInboxService;
use App\Services\TelegramSupportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Boshqaruv → Support inbox (Telegram Web uslubidagi operator ish joyi) va Support KPI.
 */
class SupportInboxController extends Controller
{
    public function __construct(private SupportInboxService $inbox)
    {
    }

    private function me(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('panel')->user();
        abort_unless($admin, 401);

        return $admin;
    }

    // ─── SAHIFALAR ───────────────────────────────────────────────────────────

    public function page(Request $request): Response
    {
        $me = $this->me();
        $segment = $request->query('segment') === 'shop' ? 'shop' : 'customer';
        $filter = (string) $request->query('filter', 'open');

        return Inertia::render('SupportInbox', [
            'inbox' => [
                'me' => ['id' => (int) $me->id, 'name' => $me->name, 'readOnly' => (bool) ($me->is_read_only ?? false)],
                'agents' => $this->agents(),
                'templates' => $this->templatesPayload(),
                'realtime' => $this->realtimeConfig($request),
                'initial' => [
                    'segment' => $segment,
                    'filter' => $filter,
                    'key' => (string) $request->query('key', ''),
                    'list' => $this->inbox->threads($segment, $filter, '', $me),
                ],
                'urls' => [
                    'threads' => route('boshqaruv.support.inbox.threads'),
                    'thread' => route('boshqaruv.support.inbox.thread'),
                    'older' => route('boshqaruv.support.inbox.older'),
                    'reply' => route('boshqaruv.support.inbox.reply'),
                    'assign' => route('boshqaruv.support.inbox.assign'),
                    'close' => route('boshqaruv.support.inbox.close'),
                    'read' => route('boshqaruv.support.inbox.read'),
                    'templates' => route('boshqaruv.support.inbox.templates.store'),
                    'kpi' => route('boshqaruv.support.kpi'),
                    'broadcastAuth' => route('boshqaruv.broadcasting.auth'),
                ],
            ],
        ]);
    }

    public function kpiPage(Request $request): Response
    {
        [$from, $to, $range] = $this->range($request);

        return Inertia::render('SupportKpi', [
            'kpi' => $this->inbox->kpi($from, $to),
            'kpiFilters' => ['range' => $range, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'inboxUrl' => route('boshqaruv.support.inbox'),
        ]);
    }

    // ─── JSON API ────────────────────────────────────────────────────────────

    public function threads(Request $request): JsonResponse
    {
        $segment = $request->query('segment') === 'shop' ? 'shop' : 'customer';

        return response()->json($this->inbox->threads(
            $segment,
            (string) $request->query('filter', 'all'),
            (string) $request->query('q', ''),
            $this->me(),
            $request->query('before') ?: null,
        ));
    }

    public function thread(Request $request): JsonResponse
    {
        $key = (string) $request->query('key', '');

        return $this->guard(fn () => $this->inbox->thread($key, $this->me()));
    }

    public function older(Request $request): JsonResponse
    {
        $key = (string) $request->query('key', '');
        $before = (int) $request->query('before_id', 0);

        return $this->guard(fn () => $this->inbox->olderMessages($key, $before));
    }

    public function reply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:4000'],
            'note' => ['sometimes', 'boolean'],
            'template_id' => ['nullable', 'integer'],
        ]);

        return $this->guard(function () use ($data) {
            $message = $this->inbox->reply($data['key'], $this->me(), $data['message'], (bool) ($data['note'] ?? false));

            if (! empty($data['template_id'])) {
                SupportTemplate::query()->whereKey($data['template_id'])->increment('usage_count');
            }

            return ['message' => $message];
        });
    }

    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'admin_id' => ['nullable', 'integer'],
        ]);

        return $this->guard(fn () => ['thread' => $this->inbox->assign($data['key'], $this->me(), $data['admin_id'] ?? null)]);
    }

    public function close(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:64']]);

        return $this->guard(fn () => ['thread' => $this->inbox->close($data['key'], $this->me())]);
    }

    public function read(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:64']]);

        return $this->guard(function () use ($data) {
            $this->inbox->markRead($data['key']);

            return ['ok' => true];
        });
    }

    public function kpi(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return response()->json($this->inbox->kpi($from, $to));
    }

    // ─── SHABLONLAR ──────────────────────────────────────────────────────────

    public function templateStore(Request $request): JsonResponse
    {
        $data = $this->validateTemplate($request);
        SupportTemplate::create($data + ['created_by' => $this->me()->id, 'sort' => (int) SupportTemplate::query()->max('sort') + 1]);

        return response()->json(['templates' => $this->templatesPayload()]);
    }

    public function templateUpdate(Request $request, SupportTemplate $template): JsonResponse
    {
        $template->update($this->validateTemplate($request));

        return response()->json(['templates' => $this->templatesPayload()]);
    }

    public function templateDestroy(SupportTemplate $template): JsonResponse
    {
        $template->update(['is_active' => false]);

        return response()->json(['templates' => $this->templatesPayload()]);
    }

    private function validateTemplate(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:4000'],
            'audience' => ['required', 'in:all,customer,shop'],
            'category' => ['nullable', 'string', 'max:40'],
            'shortcut' => ['nullable', 'string', 'max:32', 'regex:/^[\pL\pN_-]+$/u'],
        ]);
        $data['is_active'] = true;

        return $data;
    }

    private function templatesPayload(): array
    {
        return SupportTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (SupportTemplate $t) => [
                'id' => (int) $t->id,
                'title' => $t->title,
                'body' => $t->body,
                'audience' => $t->audience,
                'category' => $t->category,
                'shortcut' => $t->shortcut,
                'usage' => (int) $t->usage_count,
            ])->all();
    }

    // ─── MEDIA (Telegram fayllari) ──────────────────────────────────────────

    public function media(BotTicketAttachment $attachment, TelegramSupportService $telegram): StreamedResponse
    {
        $file = Cache::remember('support-media:' . $attachment->id, now()->addMinutes(50), fn () => $telegram->resolveFile((string) $attachment->file_id));
        abort_unless($file, 404, 'Fayl topilmadi yoki 20 MB dan katta.');

        $mime = match (strtolower(pathinfo($file['path'], PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'oga', 'ogg' => 'audio/ogg',
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
        $name = $attachment->file_name ?: basename($file['path']);

        return response()->stream(function () use ($file) {
            $response = Http::withOptions(['stream' => true])->timeout(60)->get($file['url']);
            $body = $response->toPsrResponse()->getBody();
            while (! $body->eof()) {
                echo $body->read(65536);
                flush();
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => (str_starts_with($mime, 'image/') || str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/') ? 'inline' : 'attachment') . '; filename="' . addslashes($name) . '"',
            'Cache-Control' => 'private, max-age=3000',
        ]);
    }

    // ─── REAL-VAQT AVTORIZATSIYASI ───────────────────────────────────────────

    public function broadcastingAuth(Request $request)
    {
        return Broadcast::auth($request);
    }

    // ─── YORDAMCHILAR ────────────────────────────────────────────────────────

    private function guard(callable $fn): JsonResponse
    {
        try {
            return response()->json($fn());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function agents(): array
    {
        return $this->inbox->supportAdmins()
            ->map(fn (Admin $a) => ['id' => (int) $a->id, 'name' => $a->name])
            ->values()
            ->all();
    }

    private function realtimeConfig(Request $request): ?array
    {
        $driver = (string) config('broadcasting.default');
        if (! in_array($driver, ['reverb', 'pusher'], true)) {
            return null;
        }

        $connection = (array) config('broadcasting.connections.' . $driver, []);
        $key = (string) ($connection['key'] ?? '');
        if ($key === '') {
            return null;
        }

        $client = (array) config('broadcasting.panel_client', []);
        $scheme = (string) ($client['scheme'] ?: 'https');

        return [
            'driver' => $driver,
            'key' => $key,
            'cluster' => $connection['options']['cluster'] ?? 'mt1',
            'host' => (string) ($client['host'] ?: $request->getHost()),
            'port' => (int) ($client['port'] ?: 443),
            'tls' => $scheme === 'https',
        ];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function range(Request $request): array
    {
        $range = (string) $request->query('range', '7d');
        $to = now()->endOfDay();

        switch ($range) {
            case 'today':
                $from = now()->startOfDay();
                break;
            case '30d':
                $from = now()->subDays(29)->startOfDay();
                break;
            case 'custom':
                try {
                    $from = Carbon::parse((string) $request->query('from'))->startOfDay();
                    $to = Carbon::parse((string) $request->query('to'))->endOfDay();
                } catch (\Throwable) {
                    $from = now()->subDays(6)->startOfDay();
                }
                if ($from->gt($to)) {
                    [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                }
                if ($from->diffInDays($to) > 92) {
                    $from = $to->copy()->subDays(91)->startOfDay();
                }
                break;
            default:
                $range = '7d';
                $from = now()->subDays(6)->startOfDay();
        }

        return [$from, $to, $range];
    }
}
