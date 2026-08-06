<?php

namespace App\Observers;

use App\Models\FavouriteProducts;
use App\Models\MyCart;
use Illuminate\Support\Facades\Cache;

/**
 * Savat yoki sevimlilar o'zgarganda (qo'shildi, yangilandi yoki o'chirildi),
 * shu foydalanuvchi uchun `UserTasteProfileService`dagi savat/sevimlilar
 * keshini DARHOL tozalaydi.
 *
 * MUHIM: bu tozalash bo'lmasa, `UserTasteProfileService::cartItems()` /
 * `favoriteItems()` (kesh TTL: sevimlilar 1 soat, savat 10 daqiqa) orqali
 * foydalanuvchi hozir qo'shgan mahsulot moslik (%) hisobiga darrov ta'sir
 * qilmay qolardi. AI chaqiruvi YO'Q — faqat kesh kaliti o'chiriladi,
 * keyingi so'rovda o'zi qayta hisoblanadi.
 *
 * BILINGAN CHEKLOV: bu observer faqat Eloquent MODEL hodisalarida ishlaydi
 * (`$model->save()`, `$model->delete()`). `CartController::remove()` va
 * `batchDelete()` kabi ba'zi joylar `MyCart::where(...)->delete()` (query
 * builder) orqali o'chiradi — bular hech qanday model hodisasi
 * chaqirmaydi, shuning uchun bu observer ularni ushlay olmaydi. Shu sababli
 * savat uchun qo'shimcha xavfsizlik sifatida qisqaroq (10 daqiqalik) TTL
 * ishlatiladi — `UserTasteProfileService::CART_CACHE_TTL`ga qarang.
 */
class ReadingIntelTasteCacheObserver
{
    public function saved(MyCart|FavouriteProducts $model): void
    {
        $this->forget($model);
    }

    public function deleted(MyCart|FavouriteProducts $model): void
    {
        $this->forget($model);
    }

    private function forget(MyCart|FavouriteProducts $model): void
    {
        $userId = $model->user_id;
        if (! $userId) {
            return;
        }

        $key = $model instanceof MyCart ? 'cart' : 'favorites';
        Cache::forget("reading-intel:{$key}:{$userId}");
    }
}
