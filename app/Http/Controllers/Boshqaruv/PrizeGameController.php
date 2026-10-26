<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\BookEdition;
use App\Models\GamePrize;
use App\Models\GameTask;
use App\Models\Seller;
use App\Services\PrizeGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Boshqaruv: "Sovg'alar g'ildiragi" — sozlamalar, sovg'alar, shartlar, statistika. */
class PrizeGameController extends Controller
{
    public function __construct(private PrizeGameService $game) {}

    public function index(): Response
    {
        $spinsTotal = DB::table('game_spins')->count();
        $since = now()->subDays(30);

        // Har bir sovg'aning so'nggi 30 kundagi haqiqiy tushish foizi
        $spins30 = DB::table('game_spins')->where('created_at', '>=', $since)->count();
        $byPrize = DB::table('game_spins')->where('created_at', '>=', $since)->whereNotNull('prize_id')
            ->selectRaw('prize_id, count(*) c')->groupBy('prize_id')->pluck('c', 'prize_id');

        $prizes = GamePrize::with('edition:id,title,author,front_image,images')
            ->orderBy('position')->orderBy('id')->get()
            ->map(fn (GamePrize $p) => [
                'id' => $p->id,
                'type' => $p->type,
                'title_uz' => $p->title_uz,
                'title_ru' => $p->title_ru,
                'image' => PrizeGameService::imageUrl($p->image),
                'cover' => PrizeGameService::imageUrl($p->edition?->coverPath()),
                'difficulty' => $p->difficulty,
                'chance' => $p->chance,
                'is_active' => $p->is_active,
                'position' => $p->position,
                'fragments_total' => $p->fragments_total,
                'edition_id' => $p->edition_id,
                'edition_title' => $p->edition ? trim($p->edition->title.' — '.$p->edition->author, ' —') : null,
                'coins_amount' => $p->coins_amount,
                'discount_type' => $p->discount_type,
                'discount_value' => $p->discount_value,
                'max_discount' => $p->max_discount,
                'min_order_amount' => $p->min_order_amount,
                'scope' => $p->scope,
                'scope_id' => $p->scope_id,
                'scope_title' => $this->scopeTitle($p->scope, $p->scope_id),
                'valid_days' => $p->valid_days,
                'min_orders' => $p->min_orders,
                'min_spins' => $p->min_spins,
                'max_wins_per_user' => $p->max_wins_per_user,
                'daily_limit' => $p->daily_limit,
                'stock' => $p->stock,
                'won_count' => $p->won_count,
                'left' => $p->stock ? max(0, $p->stock - $p->won_count) : null,
                'starts_at' => $p->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $p->ends_at?->format('Y-m-d\TH:i'),
                'actual_rate' => $spins30 > 0 ? round(((int) ($byPrize[$p->id] ?? 0)) * 100 / $spins30, 2) : null,
            ]);

        $daily = DB::table('game_spins')->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) d, count(*) c, sum(result <> "nothing") w')
            ->groupBy('d')->orderBy('d')->get()
            ->map(fn ($r) => ['date' => $r->d, 'spins' => (int) $r->c, 'wins' => (int) $r->w]);

        $recent = DB::table('game_spins')
            ->leftJoin('users', 'users.id', '=', 'game_spins.user_id')
            ->leftJoin('game_prizes', 'game_prizes.id', '=', 'game_spins.prize_id')
            ->where('game_spins.result', '!=', 'nothing')
            ->orderByDesc('game_spins.id')->limit(30)
            ->get(['game_spins.id', 'game_spins.user_id', 'users.name', 'users.lastname', 'game_prizes.title_uz',
                'game_spins.result', 'game_spins.payload', 'game_spins.created_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'user' => trim(($r->name ?? '').' '.($r->lastname ?? '')) ?: "#{$r->user_id}",
                'prize' => $r->title_uz,
                'result' => $r->result,
                'code' => json_decode((string) $r->payload, true)['code'] ?? null,
                'at' => $r->created_at,
            ]);

        // Sovg'a qutilari: qaysi buyurtmaga qo'shilishi kerak (Kitobchi jamoasi soladi)
        $surprises = \Illuminate\Support\Facades\Schema::hasColumn('game_rewards', 'order_id')
            ? DB::table('game_rewards')
                ->leftJoin('users', 'users.id', '=', 'game_rewards.user_id')
                ->leftJoin('solds', 'solds.id', '=', 'game_rewards.order_id')
                ->where('game_rewards.type', 'surprise')->whereNotNull('game_rewards.order_id')
                ->orderByDesc('game_rewards.used_at')->limit(50)
                ->get(['game_rewards.id', 'game_rewards.order_id', 'game_rewards.user_id', 'users.name', 'users.lastname', 'solds.status_code', 'game_rewards.used_at'])
                ->map(fn ($r) => [
                    'id' => $r->id, 'order_id' => $r->order_id, 'user_id' => $r->user_id,
                    'user' => trim(($r->name ?? '').' '.($r->lastname ?? '')) ?: "#{$r->user_id}",
                    'status' => $r->status_code, 'at' => $r->used_at,
                ])
            : collect();
        $surprisesWaiting = \Illuminate\Support\Facades\Schema::hasColumn('game_rewards', 'order_id')
            ? DB::table('game_rewards')->where('type', 'surprise')->whereNull('order_id')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count()
            : 0;

        return Inertia::render('PrizeGame', [
            'surprises' => $surprises,
            'surprises_waiting' => $surprisesWaiting,
            'settings' => array_map('intval', $this->game->settings()),
            'prizes' => $prizes,
            'tasks' => GameTask::orderBy('position')->get(['id', 'key', 'title_uz', 'title_ru', 'coins', 'is_active']),
            'presets' => GamePrize::DIFFICULTY_PRESETS,
            'stats' => [
                'spins_total' => $spinsTotal,
                'spins_today' => DB::table('game_spins')->where('created_at', '>=', today())->count(),
                'players' => DB::table('game_wallets')->count(),
                'coins_in_wallets' => (int) DB::table('game_wallets')->sum('coins'),
                'coins_earned' => (int) DB::table('game_wallets')->sum('total_earned'),
                'rewards_issued' => DB::table('game_rewards')->count(),
                'rewards_used' => DB::table('game_rewards')->whereNotNull('used_at')->count(),
                'daily' => $daily,
            ],
            'recent' => $recent,
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'spin_cost' => 'required|integer|min:1|max:10000',
            'daily_coins' => 'required|integer|min:0|max:10000',
            'order_coins' => 'required|integer|min:0|max:10000',
            'welcome_coins' => 'required|integer|min:0|max:10000',
        ]);
        $this->game->saveSettings($data);

        return back()->with('success', 'Sozlamalar saqlandi');
    }

    public function storePrize(Request $request): RedirectResponse
    {
        $prize = new GamePrize;
        $this->fill($prize, $request);

        return back()->with('success', "Sovg'a qo'shildi");
    }

    public function updatePrize(Request $request, GamePrize $prize): RedirectResponse
    {
        $this->fill($prize, $request);

        return back()->with('success', "Sovg'a saqlandi");
    }

    public function togglePrize(GamePrize $prize): RedirectResponse
    {
        $prize->forceFill(['is_active' => ! $prize->is_active])->save();
        Cache::forget('game_winners');

        return back()->with('success', $prize->is_active ? 'Yoqildi' : "O'chirildi");
    }

    public function updateChances(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'chances' => 'required|array',
            'chances.*' => 'numeric|min:0|max:100',
        ]);
        foreach ($data['chances'] as $id => $chance) {
            GamePrize::whereKey((int) $id)->update(['chance' => round((float) $chance, 3)]);
        }
        $this->assertTotal();

        return back()->with('success', 'Foizlar saqlandi');
    }

    public function destroyPrize(GamePrize $prize): RedirectResponse
    {
        if ($prize->image) {
            Storage::disk('public')->delete($prize->image);
        }
        DB::table('game_fragments')->where('prize_id', $prize->id)->delete();
        $prize->delete();

        return back()->with('success', "Sovg'a o'chirildi");
    }

    public function updateTask(Request $request, GameTask $task): RedirectResponse
    {
        $data = $request->validate([
            'title_uz' => 'required|string|max:120',
            'title_ru' => 'nullable|string|max:120',
            'coins' => 'required|integer|min:0|max:10000',
            'is_active' => 'required|boolean',
        ]);
        $task->update($data);

        return back()->with('success', 'Vazifa saqlandi');
    }

    /** Kitob (global katalog) yoki do'kon qidirish — tanlagich uchun. */
    public function lookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $type = $request->query('type') === 'seller' ? 'seller' : 'edition';
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        if ($type === 'seller') {
            $rows = Seller::query()
                ->when(ctype_digit($q), fn ($w) => $w->whereKey((int) $q), fn ($w) => $w->where('shop_name', 'like', "%{$q}%"))
                ->limit(15)->get(['id', 'shop_name'])
                ->map(fn ($s) => ['id' => $s->id, 'title' => $s->shop_name, 'sub' => "#{$s->id}"]);
        } else {
            $rows = BookEdition::query()
                ->when(ctype_digit($q) && strlen($q) < 10, fn ($w) => $w->whereKey((int) $q), fn ($w) => $w->where(fn ($x) => $x
                    ->where('title', 'like', "%{$q}%")->orWhere('author', 'like', "%{$q}%")->orWhere('isbn13', $q)))
                ->limit(15)->get(['id', 'title', 'author', 'front_image', 'images'])
                ->map(fn ($e) => ['id' => $e->id, 'title' => $e->title, 'sub' => $e->author,
                    'image' => PrizeGameService::imageUrl($e->coverPath())]);
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Sovg'a formasi (soddalashtirilgan): turi, qiymati, tushish foizi, soni va holati.
     * Rasm, muddat va qo'shimcha shartlar yo'q — promokod/tanga rasmini ilova o'zi chizadi,
     * kitob uchun muqova olinadi; soni tugagach sovg'a avtomatik g'ildirakdan chiqadi.
     */
    private function fill(GamePrize $prize, Request $request): void
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['fragments', 'promocode', 'coins', 'surprise'])],
            'title_uz' => 'nullable|string|max:120',
            'title_ru' => 'nullable|string|max:120',
            'chance' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:1|max:1000000',

            'fragments_total' => 'nullable|required_if:type,fragments|integer|min:2|max:12',
            'edition_id' => 'nullable|required_if:type,fragments|integer|exists:book_editions,id',
            'coins_amount' => 'nullable|required_if:type,coins|integer|min:1|max:100000',

            'discount_type' => ['nullable', 'required_if:type,promocode', Rule::in(['percent', 'fixed'])],
            'discount_value' => 'nullable|required_if:type,promocode|integer|min:1',
            'max_discount' => 'nullable|integer|min:0',
            'min_order_amount' => 'nullable|integer|min:0',
            'scope' => ['nullable', Rule::in(['all', 'edition', 'seller'])],
            'scope_id' => 'nullable|integer|min:1',
        ], [
            'edition_id.required_if' => "Bo'laklar uchun kitobni tanlang",
            'discount_value.required_if' => 'Chegirma miqdorini kiriting',
            'coins_amount.required_if' => 'Tanga miqdorini kiriting',
        ]);

        $type = $data['type'];
        if (($data['discount_type'] ?? null) === 'percent' && (int) ($data['discount_value'] ?? 0) > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_value' => 'Foiz 100 dan oshmasin']);
        }
        $scope = $type === 'promocode' ? ($data['scope'] ?? 'all') : 'all';
        if ($scope !== 'all' && empty($data['scope_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['scope_id' => $scope === 'seller' ? "Do'konni tanlang" : 'Kitobni tanlang']);
        }

        // Avvalgi qo'lda yuklangan rasm endi kerak emas
        if ($prize->image) {
            Storage::disk('public')->delete($prize->image);
        }

        $nullInt = fn ($k) => isset($data[$k]) && $data[$k] !== '' ? (int) $data[$k] : null;
        $chance = round((float) $data['chance'], 3);

        $prize->forceFill([
            'type' => $type,
            'image' => null,
            'difficulty' => $chance >= 10 ? 'easy' : ($chance >= 3 ? 'medium' : 'hard'),
            'chance' => $chance,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : ($prize->exists ? $prize->is_active : true),
            'position' => (int) ($prize->position ?? 0),
            'fragments_total' => $type === 'fragments' ? (int) $data['fragments_total'] : null,
            'edition_id' => $type === 'fragments' ? (int) $data['edition_id'] : null,
            'coins_amount' => $type === 'coins' ? (int) $data['coins_amount'] : null,
            'discount_type' => $type === 'promocode' ? $data['discount_type'] : null,
            'discount_value' => $type === 'promocode' ? (int) $data['discount_value'] : null,
            'max_discount' => $type === 'promocode' && $data['discount_type'] === 'percent' ? $nullInt('max_discount') : null,
            'min_order_amount' => $type === 'promocode' ? $nullInt('min_order_amount') : null,
            'scope' => $scope,
            'scope_id' => $scope === 'all' ? null : (int) $data['scope_id'],
            'stock' => $nullInt('stock'),
            // Muddat va qo'shimcha shartlar yo'q
            'valid_days' => PrizeGameService::REWARD_VALID_DAYS,
            'min_orders' => 0,
            'min_spins' => 0,
            'max_wins_per_user' => null,
            'daily_limit' => null,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        // Nomi bo'sh bo'lsa — qiymatdan avtomatik ("15% chegirma", "+50 tanga", kitob nomi)
        [$autoUz, $autoRu] = $this->autoTitle($prize);
        $prize->title_uz = trim((string) ($data['title_uz'] ?? '')) ?: $autoUz;
        $prize->title_ru = trim((string) ($data['title_ru'] ?? '')) ?: $autoRu;
        $prize->save();

        // Soni qisqartirilib, yutilganlar sonidan kam bo'lib qolsa ham sovg'a o'zi chiqadi
        $this->assertTotal();
    }

    /** @return array{0:string,1:string} */
    private function autoTitle(GamePrize $p): array
    {
        $num = fn (int $n) => number_format($n, 0, '.', ' ');
        if ($p->type === 'surprise') {
            return ["Sovg'a qutisi", 'Подарок-сюрприз'];
        }
        if ($p->type === 'coins') {
            return ["+{$p->coins_amount} tanga", "+{$p->coins_amount} монет"];
        }
        if ($p->type === 'fragments') {
            $title = BookEdition::whereKey($p->edition_id)->value('title') ?: 'Kitob';

            return [$title, $title];
        }
        $v = $p->discount_type === 'percent' ? "{$p->discount_value}%" : $num((int) $p->discount_value)." so'm";
        $vRu = $p->discount_type === 'percent' ? "{$p->discount_value}%" : $num((int) $p->discount_value).' сум';

        return ["{$v} chegirma", "Скидка {$vRu}"];
    }

    /** Faol sovg'alar foizlari yig'indisi 100% dan oshmasin. */
    private function assertTotal(): void
    {
        $sum = (float) GamePrize::where('is_active', true)->sum('chance');
        if ($sum > 100.0001) {
            session()->flash('error', "Diqqat: faol sovg'alar foizlari yig'indisi ".round($sum, 2).'% — 100% dan oshdi, foizlar proporsional kamaytirib ishlatiladi.');
        }
    }

    private function scopeTitle(?string $scope, ?int $id): ?string
    {
        if (! $id || ! $scope || $scope === 'all') {
            return null;
        }
        if ($scope === 'seller') {
            return Seller::whereKey($id)->value('shop_name');
        }
        $e = BookEdition::find($id, ['id', 'title', 'author']);

        return $e ? trim($e->title.' — '.$e->author, ' —') : null;
    }
}
