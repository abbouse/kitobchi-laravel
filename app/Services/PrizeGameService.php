<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Models\GameCoinTransaction;
use App\Models\GameFragment;
use App\Models\GamePrize;
use App\Models\GameReward;
use App\Models\GameSpin;
use App\Models\GameTask;
use App\Models\GameWallet;
use App\Models\Promocode;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * "Sovg'alar g'ildiragi": tangalar, aylantirish, kitob bo'laklari va
 * yutganlarga avtomatik yaratiladigan shaxsiy promokodlar.
 */
class PrizeGameService
{
    public const DEFAULT_SETTINGS = [
        'enabled' => '1',
        'spin_cost' => '10',
        'daily_coins' => '5',
        'order_coins' => '10',
        'welcome_coins' => '20',
    ];

    // ── Sozlamalar ─────────────────────────────────────────────

    public function settings(): array
    {
        return Cache::remember('game_settings', 300, function () {
            $rows = DB::table('game_settings')->pluck('value', 'key')->all();

            return array_merge(self::DEFAULT_SETTINGS, $rows);
        });
    }

    public function setting(string $key): int
    {
        return (int) ($this->settings()[$key] ?? 0);
    }

    public function saveSettings(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULT_SETTINGS)) {
                continue;
            }
            DB::table('game_settings')->updateOrInsert(['key' => $key], ['value' => (string) (int) $value]);
        }
        Cache::forget('game_settings');
    }

    // ── Hamyon ─────────────────────────────────────────────────

    public function wallet(int $userId): GameWallet
    {
        $wallet = GameWallet::where('user_id', $userId)->first();
        if ($wallet) {
            return $wallet;
        }

        try {
            $wallet = GameWallet::create(['user_id' => $userId, 'coins' => 0]);
        } catch (QueryException) {
            return GameWallet::where('user_id', $userId)->firstOrFail();
        }

        $welcome = $this->setting('welcome_coins');
        if ($welcome > 0) {
            $this->credit($userId, $welcome, 'admin', 'welcome', "Xush kelibsiz sovg'asi");
        }

        return $wallet->refresh();
    }

    /**
     * Tanga qo'shish/ayirish. (reason, ref) juftligi takrorlanmaydi — bir
     * hodisa uchun ikki marta tanga berilmaydi. Qaytaradi: qo'shildimi.
     */
    public function credit(int $userId, int $amount, string $reason, ?string $ref = null, ?string $note = null): bool
    {
        if ($amount === 0) {
            return false;
        }

        try {
            DB::transaction(function () use ($userId, $amount, $reason, $ref, $note) {
                GameCoinTransaction::create([
                    'user_id' => $userId,
                    'amount' => $amount,
                    'reason' => $reason,
                    'ref' => $ref,
                    'note' => $note,
                ]);
                $q = GameWallet::where('user_id', $userId);
                $q->increment('coins', $amount, $amount > 0
                    ? ['total_earned' => DB::raw('total_earned + '.$amount)]
                    : ['total_spent' => DB::raw('total_spent + '.abs($amount))]);
            });
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || (int) ($e->errorInfo[1] ?? 0) === 1062 || str_contains($e->getMessage(), 'UNIQUE')) {
                return false;
            }
            throw $e;
        }

        return true;
    }

    /** Yetkazilgan buyurtmalar uchun tangalar (o'yinga qo'shilgandan keyingilar). */
    public function syncOrderCoins(GameWallet $wallet): void
    {
        $perOrder = $this->setting('order_coins');
        if ($perOrder <= 0) {
            return;
        }

        $credited = GameCoinTransaction::where('user_id', $wallet->user_id)
            ->where('reason', 'order')->pluck('ref')->all();

        $ids = DB::table('solds')
            ->where('user_id', $wallet->user_id)
            ->where('created_at', '>=', $wallet->created_at)
            ->whereIn('status_code', [OrderStatusCode::DELIVERED->value, OrderStatusCode::CUSTOMER_RECEIVED->value])
            ->pluck('id');

        foreach ($ids as $id) {
            if (! in_array((string) $id, $credited, true)) {
                $this->credit($wallet->user_id, $perOrder, 'order', (string) $id, "Buyurtma #{$id}");
            }
        }
    }

    public function deliveredOrders(int $userId): int
    {
        return DB::table('solds')->where('user_id', $userId)
            ->whereIn('status_code', [OrderStatusCode::DELIVERED->value, OrderStatusCode::CUSTOMER_RECEIVED->value])
            ->count();
    }

    // ── Kunlik bonus ───────────────────────────────────────────

    public function nextDailyAt(GameWallet $wallet): ?Carbon
    {
        if (! $wallet->last_daily_at || ! $wallet->last_daily_at->isToday()) {
            return null;
        }

        return today()->addDay();
    }

    public function claimDaily(int $userId): array
    {
        $wallet = $this->wallet($userId);
        if ($this->nextDailyAt($wallet)) {
            return ['ok' => false, 'error' => 'Bugungi bonus olingan', 'coins' => $wallet->coins];
        }

        $amount = $this->setting('daily_coins');
        $this->credit($userId, $amount, 'daily', today()->toDateString(), 'Kunlik bonus');
        $wallet->forceFill(['last_daily_at' => now()])->save();

        return ['ok' => true, 'amount' => $amount, 'coins' => $wallet->refresh()->coins];
    }

    // ── Vazifalar ──────────────────────────────────────────────

    /** Vazifa bugun bajarilganmi. */
    public function taskDone(string $key, int $userId): bool
    {
        $today = [today()->startOfDay(), today()->endOfDay()];

        return match ($key) {
            'review' => DB::table('book_club')->where('user_id', $userId)
                ->whereNotNull('product_id')->where('product_type', '!=', 'order')
                ->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))
                ->whereBetween('created_at', $today)->exists(),
            'club_post' => DB::table('book_club')->where('user_id', $userId)
                ->whereNull('product_id')
                ->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))
                ->whereBetween('created_at', $today)->exists(),
            'order' => DB::table('solds')->where('user_id', $userId)
                ->whereBetween('created_at', $today)->exists(),
            default => false,
        };
    }

    public function tasks(int $userId): array
    {
        $claimed = GameCoinTransaction::where('user_id', $userId)->where('reason', 'task')
            ->where('ref', 'like', '%:'.today()->toDateString())->pluck('ref')->all();

        return GameTask::where('is_active', true)->orderBy('position')->get()
            ->map(function (GameTask $t) use ($userId, $claimed) {
                $isClaimed = in_array($t->key.':'.today()->toDateString(), $claimed, true);

                return [
                    'key' => $t->key,
                    'title' => app()->getLocale() === 'ru' && $t->title_ru ? $t->title_ru : $t->title_uz,
                    'coins' => $t->coins,
                    'status' => $isClaimed ? 'claimed' : ($this->taskDone($t->key, $userId) ? 'ready' : 'todo'),
                ];
            })->values()->all();
    }

    public function claimTask(int $userId, string $key): array
    {
        $task = GameTask::where('key', $key)->where('is_active', true)->first();
        if (! $task) {
            return ['ok' => false, 'error' => 'Vazifa topilmadi'];
        }
        $this->wallet($userId);
        if (! $this->taskDone($key, $userId)) {
            return ['ok' => false, 'error' => 'Vazifa hali bajarilmagan'];
        }
        if (! $this->credit($userId, $task->coins, 'task', $key.':'.today()->toDateString(), $task->title_uz)) {
            return ['ok' => false, 'error' => 'Bugun bu vazifa uchun tanga olingan'];
        }

        return ['ok' => true, 'amount' => $task->coins, 'coins' => $this->wallet($userId)->coins];
    }

    // ── Sovg'alar ──────────────────────────────────────────────

    /** Hozir g'ildirakda ko'rinadigan sovg'alar (sana va zaxira bo'yicha). */
    public function livePrizes()
    {
        return GamePrize::with('edition:id,title,author,front_image,images')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('stock')->orWhereColumn('won_count', '<', 'stock'))
            ->orderBy('position')->orderBy('id')
            ->get();
    }

    /** Foydalanuvchi bu sovg'ani hozir yuta oladimi. */
    public function eligible(GamePrize $prize, int $userId, int $orders, int $spins): bool
    {
        if ($prize->min_orders > 0 && $orders < $prize->min_orders) {
            return false;
        }
        if ($prize->min_spins > 0 && $spins < $prize->min_spins) {
            return false;
        }
        if ($prize->max_wins_per_user) {
            $wins = GameSpin::where('user_id', $userId)->where('prize_id', $prize->id)
                ->whereIn('result', ['completed', 'promocode', 'coins'])->count();
            if ($wins >= $prize->max_wins_per_user) {
                return false;
            }
        }
        if ($prize->daily_limit) {
            $today = GameSpin::where('prize_id', $prize->id)->where('created_at', '>=', today())
                ->whereIn('result', ['fragment', 'completed', 'promocode', 'coins'])->count();
            if ($today >= $prize->daily_limit) {
                return false;
            }
        }

        return true;
    }

    public function spin(User $user): array
    {
        $settings = $this->settings();
        if ((int) $settings['enabled'] !== 1) {
            return ['ok' => false, 'error' => "O'yin vaqtincha o'chirilgan"];
        }
        $cost = (int) $settings['spin_cost'];
        $wallet = $this->wallet($user->id);
        $this->syncOrderCoins($wallet);

        return DB::transaction(function () use ($user, $cost) {
            $wallet = GameWallet::where('user_id', $user->id)->lockForUpdate()->first();
            if ($wallet->coins < $cost) {
                return ['ok' => false, 'error' => 'Tangalar yetarli emas', 'coins' => $wallet->coins, 'need' => $cost];
            }

            $orders = $this->deliveredOrders($user->id);
            $spins = GameSpin::where('user_id', $user->id)->count();
            $candidates = $this->livePrizes()
                ->filter(fn (GamePrize $p) => $p->chance > 0 && $this->eligible($p, $user->id, $orders, $spins))
                ->values();

            $prize = $this->roll($candidates);

            $spin = GameSpin::create([
                'user_id' => $user->id,
                'prize_id' => $prize?->id,
                'result' => 'nothing',
                'coins_spent' => $cost,
            ]);
            $this->credit($user->id, -$cost, 'spin', (string) $spin->id, 'Aylantirish');

            $payload = $prize ? $this->award($prize, $user, $spin) : ['result' => 'nothing'];
            $spin->forceFill(['result' => $payload['result'], 'payload' => $payload])->save();

            return [
                'ok' => true,
                'spin_id' => $spin->id,
                'prize_id' => $prize?->id,
                'result' => $payload,
                'coins' => GameWallet::where('user_id', $user->id)->value('coins'),
            ];
        });
    }

    /** Foizlar bo'yicha tanlash; qolgan ehtimol — "omad keyingi safar". */
    public function roll($candidates): ?GamePrize
    {
        $sum = (float) $candidates->sum('chance');
        if ($sum <= 0) {
            return null;
        }
        $scale = $sum > 100 ? 100 / $sum : 1;
        $r = mt_rand(0, 9_999_999) / 100_000; // 0..99.99999
        $acc = 0.0;
        foreach ($candidates as $p) {
            $acc += $p->chance * $scale;
            if ($r < $acc) {
                return $p;
            }
        }

        return null;
    }

    private function award(GamePrize $prize, User $user, GameSpin $spin): array
    {
        $prize = GamePrize::whereKey($prize->id)->lockForUpdate()->first();

        switch ($prize->type) {
            case 'coins':
                $prize->increment('won_count');
                $this->credit($user->id, (int) $prize->coins_amount, 'prize', (string) $spin->id, $prize->title_uz);

                return ['result' => 'coins', 'amount' => (int) $prize->coins_amount, 'title' => $prize->title()];

            case 'promocode':
                $prize->increment('won_count');
                $reward = $this->issuePromocode($prize, $user->id, [
                    'type' => $prize->discount_type === 'fixed' ? 'uzs' : 'percent',
                    'amount' => (int) $prize->discount_value,
                    'max' => $prize->max_discount,
                    'min' => $prize->min_order_amount,
                    'scope_type' => $prize->scope === 'all' ? null : $prize->scope,
                    'scope_id' => $prize->scope === 'all' ? null : $prize->scope_id,
                ]);

                return ['result' => 'promocode', 'title' => $prize->title(), 'code' => $reward->code,
                    'expires_at' => $reward->expires_at?->toIso8601String(), 'reward_id' => $reward->id];

            case 'fragments':
            default:
                $total = max(2, (int) $prize->fragments_total);
                $have = GameFragment::where('user_id', $user->id)->where('prize_id', $prize->id)
                    ->pluck('fragment_index')->all();
                $missing = array_values(array_diff(range(0, $total - 1), $have));
                if (! $missing) {
                    // Hammasi yig'ilgan: yangi to'plam boshlanadi
                    GameFragment::where('user_id', $user->id)->where('prize_id', $prize->id)->delete();
                    $have = [];
                    $missing = range(0, $total - 1);
                }
                $index = $missing[array_rand($missing)];
                GameFragment::create(['user_id' => $user->id, 'prize_id' => $prize->id, 'fragment_index' => $index]);
                $collected = count($have) + 1;

                if ($collected < $total) {
                    return ['result' => 'fragment', 'title' => $prize->title(), 'fragment_index' => $index,
                        'collected' => $collected, 'total' => $total];
                }

                $prize->increment('won_count');
                GameFragment::where('user_id', $user->id)->where('prize_id', $prize->id)->delete();
                $reward = $this->issuePromocode($prize, $user->id, [
                    'type' => 'free_item',
                    'amount' => 0,
                    'max' => null,
                    'min' => null,
                    'scope_type' => 'edition',
                    'scope_id' => $prize->edition_id,
                ], 'book');

                return ['result' => 'completed', 'title' => $prize->title(), 'fragment_index' => $index,
                    'collected' => $total, 'total' => $total, 'code' => $reward->code,
                    'expires_at' => $reward->expires_at?->toIso8601String(), 'reward_id' => $reward->id];
        }
    }

    private function issuePromocode(GamePrize $prize, int $userId, array $o, string $rewardType = 'promocode'): GameReward
    {
        $expires = now()->addDays(max(1, (int) ($prize->valid_days ?: 30)));
        do {
            $code = 'KB'.strtoupper(Str::random(8));
        } while (Promocode::where('code', $code)->exists());

        $promo = new Promocode;
        $promo->forceFill([
            'user_id' => $userId,
            'code' => $code,
            'type' => $o['type'],
            'amount' => $o['amount'],
            'max_discount_amount' => $o['max'] ?: null,
            'min_order_amount' => $o['min'] ?: null,
            'per_user_limit' => 1,
            'usesLimit' => 1,
            'usedCount' => 0,
            'status' => true,
            'expires_at' => $expires,
            'scope_type' => $o['scope_type'],
            'scope_id' => $o['scope_id'],
            'source' => 'game',
        ])->save();

        return GameReward::create([
            'user_id' => $userId,
            'prize_id' => $prize->id,
            'type' => $rewardType,
            'title' => $prize->title_uz,
            'promocode_id' => $promo->id,
            'code' => $code,
            'expires_at' => $expires,
        ]);
    }

    // ── Ilova uchun holat ──────────────────────────────────────

    public function prizePayload(GamePrize $p, ?int $userId = null): array
    {
        $fragments = null;
        if ($p->type === 'fragments' && $userId) {
            $fragments = GameFragment::where('user_id', $userId)->where('prize_id', $p->id)
                ->pluck('fragment_index')->map(fn ($i) => (int) $i)->values()->all();
        }

        // Mijozga faqat sovg'aning o'zi: qiyinlik, foiz va shartlar ko'rsatilmaydi
        return [
            'id' => $p->id,
            'type' => $p->type,
            'title' => $p->title(),
            'image' => self::imageUrl($p->image) ?? self::imageUrl($p->edition?->coverPath()),
            'coins_amount' => $p->coins_amount,
            'discount_type' => $p->discount_type,
            'discount_value' => $p->discount_value,
            'fragments_total' => $p->type === 'fragments' ? max(2, (int) $p->fragments_total) : null,
            'fragments' => $fragments,
        ];
    }

    public static function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    public function state(User $user): array
    {
        $wallet = $this->wallet($user->id);
        $this->syncOrderCoins($wallet);
        $wallet->refresh();
        $next = $this->nextDailyAt($wallet);

        return [
            'enabled' => $this->setting('enabled') === 1,
            'coins' => $wallet->coins,
            'spin_cost' => $this->setting('spin_cost'),
            'daily' => [
                'coins' => $this->setting('daily_coins'),
                'available' => $next === null,
                'next_at' => $next?->toIso8601String(),
            ],
            'order_coins' => $this->setting('order_coins'),
            'prizes' => $this->livePrizes()->map(fn ($p) => $this->prizePayload($p, $user->id))->values()->all(),
            'tasks' => $this->tasks($user->id),
            'winners' => $this->winners(),
            'rewards_count' => GameReward::where('user_id', $user->id)->whereNull('used_at')
                ->where('expires_at', '>', now())->count(),
        ];
    }

    /** "Kim nima yutdi" lentasi. */
    public function winners(int $limit = 15): array
    {
        return Cache::remember('game_winners', 60, function () use ($limit) {
            return GameSpin::query()
                ->join('users', 'users.id', '=', 'game_spins.user_id')
                ->leftJoin('game_prizes', 'game_prizes.id', '=', 'game_spins.prize_id')
                ->whereIn('game_spins.result', ['completed', 'promocode', 'coins'])
                ->orderByDesc('game_spins.id')->limit($limit)
                ->get(['users.name', 'game_prizes.title_uz', 'game_prizes.title_ru', 'game_spins.created_at'])
                ->map(fn ($r) => [
                    'name' => self::maskName((string) $r->name),
                    'prize' => (app()->getLocale() === 'ru' && $r->title_ru) ? $r->title_ru : $r->title_uz,
                    'at' => Carbon::parse($r->created_at)->toIso8601String(),
                ])->all();
        });
    }

    public static function maskName(string $name): string
    {
        $name = trim($name) ?: 'Foydalanuvchi';
        $parts = preg_split('/\s+/u', $name);
        $first = $parts[0];
        $initial = isset($parts[1]) ? ' '.mb_substr($parts[1], 0, 1).'.' : '';

        return $first.$initial;
    }

    public function rewards(int $userId): array
    {
        return GameReward::where('user_id', $userId)->orderByDesc('id')->limit(100)->get()
            ->map(function (GameReward $r) {
                $promo = $r->promocode_id ? Promocode::find($r->promocode_id) : null;
                $used = $r->used_at || ($promo && (int) $promo->usedCount >= max(1, (int) $promo->usesLimit));

                return [
                    'id' => $r->id,
                    'type' => $r->type,
                    'title' => $r->title,
                    'code' => $r->code,
                    'expires_at' => $r->expires_at?->toIso8601String(),
                    'status' => $used ? 'used' : ($r->expires_at && $r->expires_at->isPast() ? 'expired' : 'active'),
                    'scope_type' => $promo?->scope_type,
                    'scope_id' => $promo?->scope_id,
                ];
            })->all();
    }
}
