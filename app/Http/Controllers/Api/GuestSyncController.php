<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\FavouriteProducts;
use App\Models\MyCart;
use App\Models\Stationery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// ═══════════════════════════════════════════════════════════════════════
// GuestSyncController
//
// Guest holatida yig'ilgan cart va favorites ni
// login/register dan keyin server ga ko'chiradi.
//
// Merge mantiq:
//   Cart:      server da bor → quantity += guest_qty (max stock ga cheklanadi)
//              server da yo'q → yangi yaratiladi
//   Favorites: server da bor → o'tkazib yuboriladi (duplicate yo'q)
//              server da yo'q → qo'shiladi
// ═══════════════════════════════════════════════════════════════════════

class GuestSyncController extends Controller
{
    public function syncCart(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['status' => 'success', 'synced' => 0, 'skipped' => 0]);
        }

        $synced = 0;
        $skipped = 0;

        foreach ($items as $raw) {
            try {
                $productId = (int) ($raw['product_id'] ?? 0);
                $productType = (string) ($raw['product_type'] ?? 'book');
                $variantId = isset($raw['variant_id']) ? (int) $raw['variant_id'] : null;
                $quantity = max(1, (int) ($raw['quantity'] ?? 1));

                if ($productId <= 0) {
                    $skipped++;

                    continue;
                }
                if (! in_array($productType, ['book', 'stationery'])) {
                    $skipped++;

                    continue;
                }

                [$exists, $maxStock] = $this->checkProduct($productId, $productType, $variantId);

                if (! $exists) {
                    $skipped++;

                    continue;
                }

                $existing = $this->findCartItem($user->id, $productId, $productType, $variantId);

                if ($existing) {
                    $merged = min($existing->count_item + $quantity, $maxStock);

                    if ($merged > $existing->count_item) {
                        $existing->update(['count_item' => $merged]);
                        $synced++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $cappedQty = min($quantity, $maxStock);
                    if ($cappedQty <= 0) {
                        $skipped++;

                        continue;
                    }

                    MyCart::create([
                        'user_id' => $user->id,
                        'product_id' => $productId,
                        'product_type' => $productType,
                        'variant_id' => $variantId,
                        'count_item' => $cappedQty,
                    ]);
                    $synced++;
                }
            } catch (\Throwable $e) {
                Log::error('GuestSyncController::syncCart item error', [
                    'item' => $raw ?? null,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        return response()->json([
            'status' => 'success',
            'synced' => $synced,
            'skipped' => $skipped,
        ]);
    }

    public function syncFavorites(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['status' => 'success', 'synced' => 0, 'skipped' => 0]);
        }

        $synced = 0;
        $skipped = 0;

        foreach ($items as $raw) {
            try {
                $productId = (int) ($raw['product_id'] ?? 0);
                $productType = (string) ($raw['product_type'] ?? 'book');

                if ($productId <= 0) {
                    $skipped++;

                    continue;
                }

                $alreadyFaved = FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $productId)
                    ->where('product_type', $productType)
                    ->exists();

                if ($alreadyFaved) {
                    $skipped++;

                    continue;
                }

                [$exists] = $this->checkProduct($productId, $productType, null, checkStock: false);
                if (! $exists) {
                    $skipped++;

                    continue;
                }

                FavouriteProducts::create([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'product_type' => $productType,
                ]);
                $synced++;
            } catch (\Throwable $e) {
                Log::error('GuestSyncController::syncFavorites item error', [
                    'item' => $raw ?? null,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        return response()->json([
            'status' => 'success',
            'synced' => $synced,
            'skipped' => $skipped,
        ]);
    }

    /**
     * @return array [bool $exists, int $maxStock]
     */
    private function checkProduct(
        int $productId,
        string $productType,
        ?int $variantId = null,
        bool $checkStock = true,
    ): array {
        if ($productType === 'book') {
            $book = Books::where('id', $productId)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->when($checkStock, fn ($q) => $q->inStock())
                ->first(['id']);

            if (! $book) {
                return [false, 0];
            }

            return [true, (int) ($book->count ?? 99)];
        }

        $stat = Stationery::where('id', $productId)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->when($checkStock, fn ($q) => $q->inStock())
            ->first(['id']);

        if (! $stat) {
            return [false, 0];
        }

        if ($variantId) {
            $variant = $stat->variants()->where('id', $variantId)->first(['id', 'product_id']);
            if (! $variant) {
                return [false, 0];
            }

            return [true, (int) ($variant->stock ?? 0)];
        }

        return [true, (int) ($stat->stock ?? 99)];
    }

    private function findCartItem(
        int $userId,
        int $productId,
        string $productType,
        ?int $variantId,
    ): ?MyCart {
        return MyCart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_type', $productType)
            ->when(
                $variantId,
                fn ($q) => $q->where('variant_id', $variantId),
                fn ($q) => $q->whereNull('variant_id'),
            )
            ->first();
    }
}
