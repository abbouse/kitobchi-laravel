<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CuratedCollection;
use App\Models\MarketNews;
use App\Models\SellerAd;
use App\Models\Books;
use App\Support\ProductPayloadFormatter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NewsController extends Controller
{
    private function formatBook($book, $user = null): ?array
    {
        return ProductPayloadFormatter::format($book, [
            'user' => $user,
            'type' => 'book',
            'mode' => 'card',
            'category_format' => 'title',
        ]);
    }

    private function normalizeAction(?string $action): string
    {
        return match (trim((string) $action)) {
            'to_book' => MarketNews::ACTION_TO_PRODUCT,
            MarketNews::ACTION_NEWS => MarketNews::ACTION_TO_BOTTOMSHEET,
            '' => MarketNews::ACTION_TO_BOTTOMSHEET,
            default => trim((string) $action),
        };
    }

    private function formatCollectionPreview(?CuratedCollection $collection): ?array
    {
        if (! $collection) {
            return null;
        }

        return [
            'id' => $collection->id,
            'slug' => $collection->slug,
            'title' => $collection->localized('title', 'uz'),
            'subtitle' => $collection->localized('subtitle', 'uz'),
            'hero_image' => $collection->hero_image,
            'gradient_from' => $collection->gradient_from,
            'gradient_to' => $collection->gradient_to,
            'button_bg_color' => $collection->button_bg_color,
            'button_text_color' => $collection->button_text_color,
        ];
    }

    private function formatMarketNews(MarketNews $item, array $bookMap, array $collectionMap, $user = null): array
    {
        $action = $item->normalizedAction();
        $productData = null;
        $shopId = null;
        $shopName = null;
        $shopPhoto = null;
        $collectionData = null;

        if ($action === MarketNews::ACTION_TO_PRODUCT) {
            $book = $bookMap[$item->action_id] ?? null;
            $productData = $this->formatBook($book, $user);
        } elseif ($action === MarketNews::ACTION_TO_SHOP) {
            $shopId = $item->seller?->id;
            $shopName = $item->seller?->shop_name;
            $shopPhoto = $item->seller?->photo;
        } elseif ($action === MarketNews::ACTION_TO_COLLECTION) {
            $collectionData = $this->formatCollectionPreview($collectionMap[$item->action_id] ?? null);
            if (! $collectionData) {
                $action = MarketNews::ACTION_TO_BOTTOMSHEET;
            }
        }

        return [
            'id' => $item->id,
            'type' => $item->align === 'top' ? 'top_banner' : 'center_banner',
            'title' => $item->title,
            'description' => $item->description,
            'imgUrl' => $item->imgUrl,
            'align' => $item->align,
            'created_at' => optional($item->created_at)?->toIso8601String(),
            'action' => $action,
            'action_id' => $item->action_id,
            'shop_id' => $shopId,
            'shop_name' => $shopName,
            'shop_photo' => $shopPhoto,
            'product_id' => $action === MarketNews::ACTION_TO_PRODUCT ? $item->action_id : null,
            'collection_id' => $action === MarketNews::ACTION_TO_COLLECTION ? $item->action_id : null,
            'product_data' => $productData,
            'collection_data' => $collectionData,
        ];
    }

    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        $hasCollectionsTable = Schema::hasTable('curated_collections');

        $with = [
            'seller:id,shop_name,photo',
            'book:id,name,author,category_id,images,description,price,count,lang,langType,coverType,year,discountPrice,is_hidden,is_approved,seller_id,ugc_aggregate_score,ugc_reviews_count,ugc_last_scored_at',
            'book.category:id,name_uz,name_ru,name_en,name_ja,slug',
            'book.tags',
            'book.seller:id,shop_name,photo,rating,rating_reviews_count,reputation_score,isVerified',
        ];

        if ($hasCollectionsTable) {
            $with[] = 'collection:id,slug,title_uz,title_ru,title_en,title_ja,subtitle_uz,subtitle_ru,subtitle_en,subtitle_ja,hero_image,gradient_from,gradient_to,button_bg_color,button_text_color';
        }

        $marketNews = MarketNews::query()
            ->with($with)
            ->where('status', true)
            ->get();

        $newsProductIds = $marketNews
            ->filter(fn (MarketNews $news) => $news->normalizedAction() === MarketNews::ACTION_TO_PRODUCT)
            ->pluck('action_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $activeBanners = SellerAd::whereIn('type', ['top_banner', 'center_banner'])
            ->where('moderation', 'approved')
            ->where('expire_at', '>', Carbon::now())
            ->inRandomOrder()
            ->get();

        $adProductIds = $activeBanners->pluck('product_id')->filter()->unique()->toArray();
        $bookIds = collect($newsProductIds)
            ->merge($adProductIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $adProducts = Books::with('category', 'tags', 'seller') 
            ->whereIn('id', $bookIds)
            ->get()
            ->keyBy('id');

        $collections = collect();
        if ($hasCollectionsTable) {
            $collectionIds = $marketNews
                ->filter(fn (MarketNews $news) => $news->normalizedAction() === MarketNews::ACTION_TO_COLLECTION)
                ->pluck('action_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $collections = CuratedCollection::query()
                ->whereIn('id', $collectionIds)
                ->get()
                ->keyBy('id');
        }

        $news = $marketNews->map(fn (MarketNews $item) => $this->formatMarketNews(
            $item,
            $adProducts->all(),
            $collections->all(),
            $user,
        ));

        $formattedBanners = $activeBanners->map(function ($ad) use ($adProducts, $user) {
            $productData = null;
            if ($ad->product_id && $adProducts->has($ad->product_id)) {
                $book = $adProducts->get($ad->product_id);
                $productData = $this->formatBook($book, $user);
            }

            $action = $this->normalizeAction($ad->action);

            return [
                'ad_id' => 'ad_' . $ad->id,
                'type' => $ad->type,
                'title' => $ad->seller->shop_name, 
                'description' => $ad->description,
                'imgUrl' => $ad->banner_img,
                'action' => $action,
                'action_id' => $action === MarketNews::ACTION_TO_PRODUCT ? $ad->product_id : ($action === MarketNews::ACTION_TO_SHOP ? $ad->seller_id : null),
                'product_id' => $ad->product_id,
                'created_at_ad' => optional($ad->updated_at)?->toIso8601String(),
                'isAd' => true,
                'shop_id'     => $ad->seller ? $ad->seller->id : null,
                'shop_name'     => $ad->seller ? $ad->seller->shop_name : null,
                'shop_photo'    => $ad->seller ? $ad->seller->photo : null,
                'product_data' => $productData,
            ];
        });

        $combinedData = $news->concat($formattedBanners);
        $sortedResult = $combinedData
            ->sortByDesc(fn ($item) => $item['created_at_ad'] ?? $item['created_at'] ?? null)
            ->take(10)
            ->values();

        return response()->json(['status' => 'success', 'data' => $sortedResult], 200);
    }
}
