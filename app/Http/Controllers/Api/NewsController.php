<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketNews;
use App\Models\SellerAd;
use App\Models\Books; // Books Model
use App\Models\FavouriteProducts; // FavouriteProducts Model
use App\Models\Users;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    /**
     * Kitob ma'lumotlarini standart formatga keltiradi.
     * Bu BookClubController'dan olingan format.
     */
    private function formatBook($book, $user = null)
    {
        $price = (float)($book->price ?? 0.0);
        $discountPrice = (float)($book->discountPrice ?? 0.0);

        return [
            'id' => $book->id,
            'name' => $book->name,
            'author' => $book->author,
            'category_id' => $book->category_id,
            'images' => $book->images,
            'description' => $book->description,
            'price' => $price,
            'count' => $book->count,
            'sales' => $book->sales,
            'lang' => $book->lang ?? 'O\'zbek',
            'langType' => $book->langType ?? '',
            'coverType' => $book->coverType ?? 'Yumshoq',
            'year' => $book->year ?? now()->year,
            'discountPrice' => $discountPrice,
            'favourite' => $user
                ? FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $book->id)
                    ->exists()
                : false,
            'category' => $book->category ? $book->category->title : null,
            'tags' => $book->tags->map(function ($tag) {
                return [
                    'uz' => $tag->tag_name_uz,
                    'ru' => $tag->tag_name_ru,
                    'en' => $tag->tag_name_en,
                ];
            }),
            'seller' => [
                'seller_id' => $book->seller ? $book->seller->id : null,
                'shop_name' => $book->seller ? $book->seller->shop_name : null,
                'photo' => $book->seller ? $book->seller->photo : null,
            ],
        ];
    }
    
    /**
     * Yangiliklar va Banner reklamalar ro'yxatini qaytaradi.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        $news = MarketNews::where('status', true)
            ->select('id', 'title', 'description', 'imgUrl', 'align', 'created_at')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->align == 'top' ? 'top_banner' : 'center_banner', 
                    'title' => $item->title,
                    'description' => $item->description,
                    'imgUrl' => $item->imgUrl,
                    'align' => $item->align,
                    'created_at' => $item->created_at,
                ];
            });
        $activeBanners = SellerAd::whereIn('type', ['top_banner', 'center_banner'])
            ->where('moderation', 'approved')
            ->where('expire_at', '>', Carbon::now())
            ->inRandomOrder() 
            ->get();
        $adProductIds = $activeBanners->pluck('product_id')->filter()->unique()->toArray();
        $adProducts = Books::with('category', 'tags', 'seller') 
            ->whereIn('id', $adProductIds)
            ->where('count', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->get()
            ->keyBy('id');

        $formattedBanners = $activeBanners->map(function ($ad) use ($adProducts, $user) {
            $productData = null;
            if ($ad->product_id && $adProducts->has($ad->product_id)) {
                $book = $adProducts->get($ad->product_id);
                $productData = $this->formatBook($book, $user ?? 0);
            }
            
            return [
                'ad_id' => 'ad_' . $ad->id,
                'type' => $ad->type,
                'title' => $ad->seller->shop_name, 
                'description' => $ad->description,
                'imgUrl' => $ad->banner_img,
                'action' => $ad->action,
                'product_id' => $ad->product_id,
                'created_at_ad' => $ad->updated_at,
                'isAd' => true,
                'shop_id'     => $ad->seller ? $ad->seller->id : null,
                'shop_name'     => $ad->seller ? $ad->seller->shop_name : null,
                'shop_photo'    => $ad->seller ? $ad->seller->photo : null,
                'product_data' => $productData,
            ];
        });
        $combinedData = $news->concat($formattedBanners);
        $sortedResult = $combinedData
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
        return response()->json(['status' => 'success', 'data' => $sortedResult], 201);
    }
}