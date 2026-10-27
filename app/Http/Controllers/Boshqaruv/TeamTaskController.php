<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Jobs\TeamBotSyncTaskJob;
use App\Models\Admin;
use App\Models\TeamTask;
use App\Services\TeamBot\TeamBotClient;
use App\Services\TeamBot\TeamBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jamoa → Vazifalar. Boshqaruvdagi vazifalar va Telegram guruhidagi vazifa
 * xabarlari bir xil yozuv: qayerda o'zgarsa, ikkinchisi ham yangilanadi.
 */
class TeamTaskController extends Controller
{
    private function me(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('panel')->user();
        abort_unless($admin, 401);

        return $admin;
    }

    private function ensureWritable(): Admin
    {
        $me = $this->me();
        abort_if($me->is_read_only ?? false, 403, "Hisobingiz faqat ko'rish rejimida.");

        return $me;
    }

    public function page(Request $request, TeamBotClient $tg, TeamBotService $bot): Response
    {
        $me = $this->me();
        $scope = $request->query('scope') === 'mine' ? 'mine' : 'all';

        $query = TeamTask::query()->with(['assignee:id,name', 'creator:id,name'])
            ->where(fn ($q) => $q->whereIn('status', ['todo', 'doing'])
                ->orWhere('updated_at', '>', now()->subDays(14)))
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByRaw('due_at IS NULL, due_at')
            ->latest('id');
        if ($scope === 'mine') {
            $query->where('assignee_admin_id', $me->id);
        }

        return Inertia::render('Tasks', [
            'tasks' => $query->limit(300)->get()->map(fn (TeamTask $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status,
                'priority' => $t->priority,
                'dueAt' => $t->due_at?->timezone('Asia/Tashkent')->format('Y-m-d\TH:i'),
                'dueLabel' => $t->due_at?->timezone('Asia/Tashkent')->format('d.m H:i'),
                'overdue' => $t->isOverdue(),
                'assignee' => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
                'creator' => $t->creator?->name,
                'orderId' => $t->order_id,
                'orderUrl' => $t->order_id ? route('boshqaruv.orders', ['orders_search' => $t->order_id, 'orders_tab' => 'all']) : null,
                'source' => $t->source,
                'updatedAt' => $t->updated_at?->diffForHumans(),
                'updateUrl' => route('boshqaruv.tasks.update', $t),
            ])->values(),
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'telegram_user_id'])
                ->map(fn (Admin $a) => ['id' => $a->id, 'name' => $a->name, 'telegram' => (bool) $a->telegram_user_id])->values(),
            'scope' => $scope,
            'me' => ['id' => $me->id, 'readOnly' => (bool) ($me->is_read_only ?? false), 'telegramLinked' => (bool) $me->telegram_user_id, 'telegramUsername' => $me->telegram_username],
            'bot' => [
                'configured' => $tg->enabled(),
                'groupConnected' => $bot->chatId() !== null,
                'username' => $tg->enabled() ? $tg->username() : null,
            ],
            'urls' => [
                'store' => route('boshqaruv.tasks.store'),
                'link' => route('boshqaruv.team-bot.link'),
                'unlink' => route('boshqaruv.team-bot.unlink'),
            ],
        ]);
    }

    private function rules(bool $partial): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$req, 'string', 'max:250'],
            'description' => ['nullable', 'string', 'max:4000'],
            'priority' => ['sometimes', Rule::in(array_keys(TeamTask::PRIORITIES))],
            'status' => ['sometimes', Rule::in(array_keys(TeamTask::STATUSES))],
            'due_at' => ['nullable', 'date'],
            'assignee_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'order_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $me = $this->ensureWritable();
        $data = $request->validate($this->rules(false));
        $task = TeamTask::query()->create([
            ...$data,
            'due_at' => isset($data['due_at']) ? \Carbon\Carbon::parse($data['due_at'], 'Asia/Tashkent')->setTimezone(config('app.timezone')) : null,
            'status' => 'todo',
            'priority' => $data['priority'] ?? 'normal',
            'created_by_admin_id' => $me->id,
            'source' => 'panel',
        ]);
        TeamBotSyncTaskJob::dispatch($task->id)->afterCommit();

        return back()->with('success', "Vazifa #{$task->id} yaratildi.");
    }

    public function update(Request $request, TeamTask $task): RedirectResponse
    {
        $this->ensureWritable();
        $data = $request->validate($this->rules(true));
        if (array_key_exists('due_at', $data)) {
            $data['due_at'] = $data['due_at'] ? \Carbon\Carbon::parse($data['due_at'], 'Asia/Tashkent')->setTimezone(config('app.timezone')) : null;
            $data['reminded_at'] = null;
        }
        $status = $data['status'] ?? null;
        unset($data['status']);
        $task->fill($data);
        if ($status !== null && $status !== $task->status) {
            $task->setStatus($status);
        } else {
            $task->save();
        }
        TeamBotSyncTaskJob::dispatch($task->id)->afterCommit();

        return back();
    }

    public function link(TeamBotService $bot): JsonResponse
    {
        $me = $this->me();
        $url = $bot->linkUrl($me);
        if (! $url) {
            return response()->json(['message' => "Jamoa boti hali sozlanmagan (TEAM_BOT_TOKEN)."], 422);
        }

        return response()->json(['url' => $url]);
    }

    public function unlink(): RedirectResponse
    {
        $me = $this->me();
        $me->forceFill(['telegram_user_id' => null, 'telegram_username' => null])->save();

        return back()->with('success', "Telegram akkaunti uzildi.");
    }
}
