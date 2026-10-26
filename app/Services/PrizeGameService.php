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
    /** Yutilgan promokod va kitobning amal qilish muddati (kun). */
    public const REWARD_VALID_DAYS = 15;

    /** Yutilgan kitob user uchun shu narxda (do'konga to'liq narx, farqini platforma to'laydi). */
    public const GAME_BOOK_PRICE = 1000;

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

    /**
     * Bosh sahifadagi kapsula uchun balans: hamyon yaratmaydi va tanga yozmaydi.
     * O'yin o'chirilgan bo'lsa — null (kapsula ko'rinmaydi).
     */
    public function peekCoins(int $userId): ?int
    {
        if ($this->setting('enabled') !== 1) {
            return null;
        }
        $coins = GameWallet::where('user_id', $userId)->value('coins');

        return $coins === null ? $this->setting('welcome_coins') : (int) $coins;
    }

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
        // Bir marta yutilgan kitob qayta tushmaydi — o'rniga boshqa kitob chiqadi
        if ($prize->type === 'fragments'
            && GameReward::where('user_id', $userId)->where('prize_id', $prize->id)->where('type', 'book')->exists()) {
            return false;
        }
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

            $candidates = $this->candidates($user->id);

            $board = $this->boardFor($user->id, $candidates);
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

            // Yig'ilayotgan kitoblar katagi boshqa yutuq uchun almashtirilmaydi
            $types = $candidates->pluck('type', 'id')->all();
            $collecting = GameFragment::where('user_id', $user->id)->distinct()->pluck('prize_id')->all();
            foreach ($collecting as $id) {
                if (isset($types[$id]) && $id !== $prize?->id) {
                    $types[$id] = 'collecting';
                }
            }
            $placed = PrizeBoard::place($board, $prize?->id, $prize?->type,
                $types, $user->id.'|'.$spin->id);

            return [
                'ok' => true,
                'spin_id' => $spin->id,
                'prize_id' => $prize?->id,
                'board' => $placed['board'],
                'cell' => $placed['cell'],
                'result' => $payload,
                'coins' => GameWallet::where('user_id', $user->id)->value('coins'),
            ];
        });
    }

    /** Hozir yutish mumkin bo'lgan sovg'alar (foizi bor va shartlari bajarilgan). */
    public function candidates(int $userId)
    {
        $orders = $this->deliveredOrders($userId);
        $spins = GameSpin::where('user_id', $userId)->count();

        $live = $this->livePrizes();
        $candidates = $live
            ->filter(fn (GamePrize $p) => $p->chance > 0 && $this->eligible($p, $userId, $orders, $spins))
            ->values();

        // Kitob — eng oliy sovg'a: bir vaqtda ko'pi bilan 2 ta kitob yig'iladi.
        // 2 ta kitob yig'ilayotgan bo'lsa, yangi kitob tushmaydi — avval shularni tugatadi.
        $collecting = $this->collectingBooks($userId, $live);
        if (count($collecting) >= PrizeBoard::QUOTA['fragments']) {
            $candidates = $candidates
                ->filter(fn (GamePrize $p) => $p->type !== 'fragments' || in_array($p->id, $collecting, true))
                ->values();
        }

        return $candidates;
    }

    /** Hozir yig'ilayotgan (boshlangan, tugallanmagan, sovg'asi jonli) kitoblar ID lari. */
    public function collectingBooks(int $userId, $live = null): array
    {
        $have = GameFragment::where('user_id', $userId)
            ->selectRaw('prize_id, count(*) as n')->groupBy('prize_id')->pluck('n', 'prize_id');
        if ($have->isEmpty()) {
            return [];
        }
        $won = GameReward::where('user_id', $userId)->where('type', 'book')
            ->whereIn('prize_id', $have->keys())->pluck('prize_id')->all();

        return ($live ?? $this->livePrizes())
            ->filter(fn (GamePrize $p) => $p->type === 'fragments'
                && isset($have[$p->id])
                && (int) $have[$p->id] < max(2, (int) $p->fragments_total)
                && ! in_array($p->id, $won, true))
            ->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * "Omadli maydon"ning 8 katagi (sovg'a ID si yoki null — "Keyingi safar").
     * Kun davomida o'zgarmaydi; boshqa kitoblar kuniga bir marta almashadi.
     * Yig'ilayotgan kitob — sovg'a faol va zaxirasi bor ekan — doim maydonda turadi,
     * hatto bugun kunlik limit yoki boshqa shart tufayli tushmasa ham.
     */
    public function boardFor(int $userId, $candidates): array
    {
        $collecting = GameFragment::where('user_id', $userId)
            ->selectRaw('prize_id, count(*) as n')->groupBy('prize_id')->pluck('n', 'prize_id');

        // Tushish shartidan o'tmagan, lekin yig'ilayotgan va hali ham "jonli" kitoblar
        if ($collecting->isNotEmpty()) {
            $have = $candidates->pluck('id')->all();
            $won = GameReward::where('user_id', $userId)->where('type', 'book')
                ->whereIn('prize_id', $collecting->keys())->pluck('prize_id')->all();
            $extra = $this->livePrizes()
                ->filter(fn (GamePrize $p) => $p->type === 'fragments'
                    && isset($collecting[$p->id])
                    && ! in_array($p->id, $have, true)
                    && ! in_array($p->id, $won, true));
            $candidates = $candidates->concat($extra)->values();
        }

        $progress = [];
        foreach ($candidates->where('type', 'fragments') as $b) {
            $n = (int) ($collecting[$b->id] ?? 0);
            if ($n > 0 && $n < max(2, (int) $b->fragments_total)) {
                $progress[$b->id] = $n / max(2, (int) $b->fragments_total);
            }
        }

        return PrizeBoard::build(
            $candidates->map(fn (GamePrize $p) => ['id' => $p->id, 'type' => $p->type])->all(),
            $progress,
            $userId.'|'.today()->toDateString(),
            intdiv(today()->getTimestamp(), 86400),
            $userId,
        );
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
            case 'surprise':
                // Sovg'a qutisi: keyingi buyurtmaga Kitobchi jamoasi kichik tasodifiy sovg'a qo'shadi
                $prize->increment('won_count');
                $reward = GameReward::create([
                    'user_id' => $user->id,
                    'prize_id' => $prize->id,
                    'type' => 'surprise',
                    'title' => $prize->title_uz,
                    'expires_at' => now()->addDays(90),
                ]);

                return ['result' => 'surprise', 'title' => $prize->title(), 'reward_id' => $reward->id];

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
                // Kitob user uchun 1000 so'm — "Savatga qo'shish" tugmasi bilan (promokodsiz)
                $reward = GameReward::create([
                    'user_id' => $user->id,
                    'prize_id' => $prize->id,
                    'type' => 'book',
                    'title' => $prize->title_uz,
                    'expires_at' => now()->addDays(self::REWARD_VALID_DAYS),
                ]);

                return ['result' => 'completed', 'title' => $prize->title(), 'fragment_index' => $index,
                    'collected' => $total, 'total' => $total, 'price' => self::GAME_BOOK_PRICE,
                    'expires_at' => $reward->expires_at?->toIso8601String(), 'reward_id' => $reward->id];
        }
    }

    private function issuePromocode(GamePrize $prize, int $userId, array $o, string $rewardType = 'promocode'): GameReward
    {
        $expires = now()->addDays(self::REWARD_VALID_DAYS);
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

    // ── Yutilgan kitob (user uchun 1000 so'm) ──────────────────

    /** Amaldagi (sotib olinmagan, muddati o'tmagan) kitob yutug'i. */
    public function activeBookReward(int $userId, ?int $rewardId = null, ?int $editionId = null): ?GameReward
    {
        $q = GameReward::query()->with('prize:id,edition_id')
            ->where('user_id', $userId)->where('type', 'book')
            ->whereNull('used_at')->where('expires_at', '>', now());
        if ($rewardId) {
            $q->whereKey($rewardId);
        }
        if ($editionId) {
            $q->whereHas('prize', fn ($p) => $p->where('edition_id', $editionId));
        }

        return $q->orderBy('expires_at')->first();
    }

    /** Savatdagi qator yutilgan kitob narxida turishi mumkinmi (yutuq amalda va kitob mos). */
    public function cartRewardValid(\App\Models\MyCart $row): bool
    {
        if (! $row->game_reward_id || $row->product_type !== 'book') {
            return false;
        }
        $reward = $this->activeBookReward((int) $row->user_id, (int) $row->game_reward_id);
        $editionId = $reward?->prize?->edition_id;

        return $reward && $editionId && (int) ($row->product?->edition_id ?? 0) === (int) $editionId;
    }

    /**
     * "Savatga qo'shish": kitobning sotuvdagi taklifi savatga 1 dona, user uchun
     * 1000 so'm narxda qo'shiladi (do'kon to'liq narxni oladi).
     */
    public function addBookRewardToCart(User $user, int $rewardId): array
    {
        $reward = $this->activeBookReward($user->id, $rewardId);
        if (! $reward) {
            return ['ok' => false, 'error' => "Yutuq topilmadi yoki muddati o'tgan"];
        }
        $editionId = (int) ($reward->prize?->edition_id ?? 0);
        $offer = \App\Support\ProductVisibilityScope::applyBooks(\App\Models\Books::query()->withAvailableTotal())
            ->where('edition_id', $editionId)
            ->get()
            ->filter(fn ($b) => (int) ($b->count ?? 0) > 0)
            ->sortBy(fn ($b) => (float) (($b->discountPrice ?? 0) > 0 ? $b->discountPrice : $b->price))
            ->first();
        if (! $offer) {
            return ['ok' => false, 'error' => "Bu kitob hozir sotuvda yo'q. Keyinroq urinib ko'ring."];
        }

        // Boshqa qatorlardagi shu yutuq bog'lanishini olib tashlaymiz (bitta yutuq — bitta qator)
        \App\Models\MyCart::where('user_id', $user->id)->where('game_reward_id', $reward->id)
            ->where('product_id', '!=', $offer->id)->update(['game_reward_id' => null, 'priceItem' => null]);

        $row = \App\Models\MyCart::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $offer->id, 'product_type' => 'book', 'variant_id' => null],
            ['count_item' => 1, 'priceItem' => self::GAME_BOOK_PRICE, 'game_reward_id' => $reward->id],
        );

        return ['ok' => true, 'cart_id' => $row->id, 'product_id' => $offer->id, 'price' => self::GAME_BOOK_PRICE];
    }

    /** Buyurtma yaratilgach: savatdagi kitob yutuqlari ishlatildi deb belgilanadi. */
    public function markBookRewardsUsed(array $rewardIds, int $orderId): void
    {
        if ($rewardIds && \Illuminate\Support\Facades\Schema::hasColumn('game_rewards', 'order_id')) {
            GameReward::whereIn('id', $rewardIds)->where('type', 'book')
                ->update(['used_at' => now(), 'order_id' => $orderId]);
        }
    }

    // ── Sovg'a qutisi ──────────────────────────────────────────

    /** Yangi buyurtmaga kutib turgan bitta sovg'a qutisini biriktiradi. */
    public function attachSurpriseToOrder(int $userId, int $orderId): ?int
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('game_rewards', 'order_id')) {
            return null;
        }
        $reward = GameReward::where('user_id', $userId)->where('type', 'surprise')
            ->whereNull('order_id')->whereNull('used_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderBy('id')->lockForUpdate()->first();
        if (! $reward) {
            return null;
        }
        $reward->forceFill(['order_id' => $orderId, 'used_at' => now()])->save();

        return (int) $reward->id;
    }

    /** Buyurtma bekor qilinsa — quti keyingi buyurtmaga qaytadi. */
    public function releaseSurpriseForOrder(int $orderId): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('game_rewards', 'order_id')) {
            return;
        }
        GameReward::where('order_id', $orderId)->where('type', 'surprise')
            ->update(['order_id' => null, 'used_at' => null]);
        // Kitob yutug'i ham qaytadi (muddati o'tmagan bo'lsa — user qayta savatga qo'shadi)
        GameReward::where('order_id', $orderId)->where('type', 'book')
            ->update(['order_id' => null, 'used_at' => null]);
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
            // Kitob — muqovasi; promokod va tanga uchun rasm yuborilmaydi, ilova o'zi chizadi
            'image' => $p->type === 'fragments'
                ? (self::imageUrl($p->edition?->coverPath()) ?? self::imageUrl($p->image))
                : null,
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
            'board' => $this->boardFor($user->id, $this->candidates($user->id)),
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
                ->whereIn('game_spins.result', ['fragment', 'completed', 'promocode', 'coins', 'surprise'])
                ->orderByDesc('game_spins.id')->limit($limit)
                ->get(['users.name', 'users.lastname', 'game_prizes.title_uz', 'game_prizes.title_ru', 'game_prizes.type', 'game_spins.result', 'game_spins.created_at'])
                ->map(fn ($r) => [
                    'name' => self::maskName((string) $r->name, (string) ($r->lastname ?? '')),
                    // fragment — kitob bo'lagi, completed — butun kitob, promocode, coins, surprise
                    'kind' => $r->result,
                    'type' => $r->type,
                    'prize' => (app()->getLocale() === 'ru' && $r->title_ru) ? $r->title_ru : $r->title_uz,
                    'at' => Carbon::parse($r->created_at)->toIso8601String(),
                ])->all();
        });
    }

    /** "Abbos T." — ism va familiyaning bosh harfi. */
    public static function maskName(string $name, string $lastname = ''): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $first = $parts[0] ?? '';
        $last = trim($lastname) !== '' ? trim($lastname) : ($parts[1] ?? '');
        if ($first === '') {
            $first = 'Foydalanuvchi';
        }
        $initial = $last !== '' ? ' '.mb_strtoupper(mb_substr($last, 0, 1)).'.' : '';

        return $first.$initial;
    }

    public function rewards(int $userId): array
    {
        return GameReward::with('prize.edition:id,front_image,images')
            ->where('user_id', $userId)->orderByDesc('id')->limit(100)->get()
            ->map(function (GameReward $r) {
                $promo = $r->promocode_id ? Promocode::find($r->promocode_id) : null;
                $used = $r->used_at || ($promo && (int) $promo->usedCount >= max(1, (int) $promo->usesLimit));
                if ($r->type === 'surprise') {
                    $used = $r->order_id !== null;
                }

                return [
                    'id' => $r->id,
                    'type' => $r->type,
                    'title' => $r->title,
                    'code' => $r->code,
                    'expires_at' => $r->expires_at?->toIso8601String(),
                    'status' => $used ? 'used' : ($r->expires_at && $r->expires_at->isPast() ? 'expired' : 'active'),
                    'scope_type' => $promo?->scope_type,
                    'scope_id' => $promo?->scope_id,
                    'image' => $r->type === 'book'
                        ? (self::imageUrl($r->prize?->edition?->coverPath()) ?? self::imageUrl($r->prize?->image))
                        : null,
                    'discount_type' => $promo?->type,
                    'amount' => $promo ? (int) $promo->amount : null,
                    'won_at' => $r->created_at?->toIso8601String(),
                    // Sovg'a qutisi / kitob qaysi buyurtma bilan ketgan
                    'order_id' => $r->order_id ?? null,
                    'price' => $r->type === 'book' ? self::GAME_BOOK_PRICE : null,
                ];
            })->all();
    }
}
