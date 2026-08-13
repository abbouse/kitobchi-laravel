<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\User;
use App\Services\DeliveryZoneResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Veb-saytdan (mehmon, ro'yxatdan o'tmagan) kelgan buyurtmalarni qabul qiladi.
 *
 * MUHIM ARXITEKTURA QARORI: bu yerda buyurtma yaratish logikasi TAKRORLANMAYDI.
 * Buning o'rniga: (1) telefon raqami bo'yicha "mehmon" User avtomatik
 * topiladi/yaratiladi, (2) yuborilgan manzil uchun locations qatori yaratiladi,
 * (3) savat itemlar MyCart'ga yoziladi, (4) so'ng ilova ishlatadigan XUDDI
 * O'SHA PurchaseController::buy_book() chaqiriladi. Shu sababli veb buyurtmasi
 * ilovadan kelgan buyurtma bilan bir xil: SellerOrder/SellerOrderItem/
 * CourierOrder yaratiladi, filial zaxirasi kamayadi, hub/kuryer taskalari
 * ochiladi — sotuvchi business ilovada ilovadagidek ko'radi. Bu logikani
 * qo'lda qayta yozish katta xato xavfi tug'dirar edi (fulfillment routing,
 * stock decrement, financial snapshot va h.k. juda ko'p bosqichli).
 */
class WebCheckoutController extends Controller
{
    /**
     * O'zbekiston viloyatlari — taxminiy markaziy koordinatalar bilan.
     * Manzil xaritadan aniq tanlanmagani uchun (veb'da hozircha xarita
     * tanlagich yo'q) zona/filial marshruti shu markaz nuqtaga asoslanadi —
     * aniq GPS emas, lekin viloyat darajasida yetkazish narxini to'g'ri
     * hisoblash va yaqin filialni tanlash uchun yetarli.
     */
    private const REGIONS = [
        'Toshkent shahri' => [41.2995, 69.2401],
        'Toshkent viloyati' => [41.0000, 69.3000],
        'Andijon viloyati' => [40.7821, 72.3442],
        "Farg'ona viloyati" => [40.3894, 71.7864],
        'Namangan viloyati' => [41.0011, 71.6726],
        'Samarqand viloyati' => [39.6542, 66.9597],
        'Buxoro viloyati' => [39.7747, 64.4286],
        'Navoiy viloyati' => [40.0844, 65.3792],
        'Qashqadaryo viloyati' => [38.8606, 65.7891],
        'Surxondaryo viloyati' => [37.2242, 67.2783],
        'Jizzax viloyati' => [40.1158, 67.8422],
        'Sirdaryo viloyati' => [40.4897, 68.7842],
        'Xorazm viloyati' => [41.5500, 60.6333],
        "Qoraqalpog'iston Respublikasi" => [42.4600, 59.6200],
    ];

    public function index()
    {
        return view('checkout.index', [
            'regions' => array_keys(self::REGIONS),
        ]);
    }

    public function process(Request $request, DeliveryZoneResolverService $deliveryResolver)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'phone_number' => 'required|string|max:25',
            'address' => 'required|string|max:255',
            'region' => ['required', 'string', 'in:'.implode(',', array_keys(self::REGIONS))],
            'payment_method' => 'required|string|in:cash,click,payme,uzum',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.id' => 'required|integer',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone_number']);
        if (strlen($phone) === 9) {
            $phone = '998'.$phone;
        }
        if (! preg_match('/^998\d{9}$/', $phone)) {
            return response()->json([
                'success' => false,
                'message' => "Telefon raqami noto'g'ri. Masalan: +998 90 123 45 67",
            ], 422);
        }

        $guestUser = null;

        try {
            DB::beginTransaction();

            // ── 1) Mehmon User — telefon bo'yicha topish yoki yaratish ──
            $guestUser = User::where('phone_number', $phone)->where('isDeleted', 'no')->first();

            if (! $guestUser) {
                [$firstName, $lastName] = $this->splitName($validated['customer_name']);
                $guestUser = User::create([
                    'phone_number' => $phone,
                    'name' => $firstName,
                    'lastname' => $lastName,
                    'password' => bcrypt(Str::random(32)),
                ]);
            } elseif (empty($guestUser->name)) {
                [$firstName, $lastName] = $this->splitName($validated['customer_name']);
                $guestUser->update(['name' => $firstName, 'lastname' => $lastName]);
            }

            // ── 2) Savat itemlarini serverda qayta tekshirish ────────────
            // Faqat haqiqatan sotuvda bo'lgan (tasdiqlangan, yashirilmagan)
            // mahsulotlar qabul qilinadi — mijoz brauzerdan yuborgan
            // ma'lumotga (narx, mavjudlik) ishonilmaydi.
            $sellerIds = [];
            $cartTotal = 0;
            $cartRows = [];

            foreach ($validated['cart_items'] as $ci) {
                $id = (int) $ci['id'];
                $qty = (int) $ci['quantity'];

                $book = Books::where('id', $id)
                    ->where('status', true)->where('is_approved', 1)->where('is_hidden', 0)
                    ->first();
                $stationery = ! $book
                    ? Stationery::where('id', $id)
                        ->where('status', true)->where('is_approved', 1)->where('is_hidden', 0)
                        ->first()
                    : null;
                $product = $book ?: $stationery;

                if (! $product || ! $product->seller_id) {
                    continue;
                }

                $price = $book
                    ? ((is_numeric($product->discountPrice) && $product->discountPrice > 0) ? (float) $product->discountPrice : (float) $product->price)
                    : ((is_numeric($product->discount_price) && $product->discount_price > 0) ? (float) $product->discount_price : (float) $product->price);

                $sellerIds[(int) $product->seller_id] = true;
                $cartTotal += $price * $qty;

                $cartRows[] = [
                    'user_id' => $guestUser->id,
                    'product_id' => $product->id,
                    'product_type' => $book ? 'book' : 'stationery',
                    'variant_id' => null,
                    'count_item' => $qty,
                    'priceItem' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (empty($cartRows)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Savatda mahsulotlar topilmadi'], 400);
            }

            // ── 3) Manzil — tanlangan viloyat markazi asosida ────────────
            [$lat, $lon] = self::REGIONS[$validated['region']];
            $fullAddress = $validated['region'].', '.$validated['address'];

            $locationId = DB::table('locations')->insertGetId([
                'user_id' => $guestUser->id,
                'lat' => (string) $lat,
                'lon' => (string) $lon,
                'fullAddress' => $fullAddress,
                'country_code' => 'UZ',
                'region_name' => $validated['region'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $guestUser->update(['mainAddressID' => $locationId]);
            $locationRow = DB::table('locations')->where('id', $locationId)->first();

            // ── 4) Savat qatorlari — mehmon uchun toza savat ─────────────
            DB::table('my_carts')->where('user_id', $guestUser->id)->delete();
            DB::table('my_carts')->insert($cartRows);

            // ── 5) Yetkazish xizmatini avtomatik tanlash ──────────────────
            // Veb formada hozircha xizmat tanlash yo'q — shu manzil uchun
            // mavjud takliflardan eng arzonini olamiz. "Tez kuryer" (bitta
            // do'kon, aniq GPS talab qiladigan) turini o'tkazib yuboramiz —
            // bizda faqat viloyat markazi bor, aniq nuqta emas.
            $offer = $deliveryResolver->resolveOffers($locationRow, count($sellerIds), $cartTotal)
                ->reject(fn ($o) => ($o['type'] ?? null) === 'store_courier')
                ->sortBy('calculated_price')
                ->first();

            if (! $offer) {
                // DB::rollBack() shu tranzaksiya ichidagi hamma narsani
                // (shu jumladan yuqoridagi my_carts insert'ini) allaqachon
                // bekor qiladi — alohida delete shart emas.
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "Kechirasiz, hozircha {$validated['region']} uchun yetkazib berish xizmati mavjud emas.",
                ], 422);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Web checkout: guest/cart setup failed', [
                'msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);

            return response()->json(['success' => false, 'message' => 'Buyurtmani rasmiylashtirishda xatolik yuz berdi. Qayta urinib ko\'ring.'], 500);
        }

        // ── 6) Haqiqiy xarid quvuriga topshirish ──────────────────────────
        // Ilova ishlatadigan aynan shu metod: SellerOrder/CourierOrder,
        // filial marshruti, stock kamayishi, hub/kuryer taskalari — barchasi
        // shu yerda, allaqachon sinovdan o'tgan kodda amalga oshadi.
        $paymentLabels = ['cash' => 'Naqd/karta (kuryerga)', 'click' => 'Click', 'payme' => 'Payme', 'uzum' => 'Uzum Pay'];
        $note = "Veb-sayt orqali buyurtma. Mijoz to'lov usuli: ".($paymentLabels[$validated['payment_method']] ?? $validated['payment_method']).'. Operator tasdiqlaydi.';

        $innerRequest = Request::create('/', 'POST', [
            'paymentStatus' => 0,
            'deliveryservice_id' => (int) $offer['id'],
            'buyerWish' => $note,
        ]);

        // MUHIM: bu yerda avvalgi auth holatini tiklashga urinish yo'q —
        // setUser() null qabul qilmaydi (non-nullable type-hint), va bu
        // so'rov aynan shu checkout uchun ochilgan, undan keyin auth holati
        // boshqa hech narsaga kerak emas (javob shu yerda qaytariladi).
        Auth::guard('user')->setUser($guestUser);

        try {
            $inner = app(PurchaseController::class)->buy_book($innerRequest);
            $payload = json_decode($inner->getContent(), true) ?: [];
        } catch (\Throwable $e) {
            Log::error('Web checkout: buy_book delegation failed', [
                'msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            $inner = null;
            $payload = ['status' => 'error', 'message' => 'Buyurtmani rasmiylashtirishda xatolik yuz berdi. Qayta urinib ko\'ring.'];
        } finally {
            // Mehmon savati faqat shu bir martalik urinish uchun — muvaffaqiyatli
            // bo'lsa buy_book allaqachon tozalagan, muvaffaqiyatsiz bo'lsa shu
            // yerda tozalanadi (ortiqcha qator qolib ketmasin).
            DB::table('my_carts')->where('user_id', $guestUser->id)->delete();
        }

        if ($inner && ($payload['status'] ?? null) === 'success') {
            $orderId = (int) ($payload['order_id'] ?? 0);
            $amount = (int) (DB::table('solds')->where('id', $orderId)->value('amount') ?? $cartTotal);

            return response()->json([
                'success' => true,
                'order_code' => 'KC-'.str_pad((string) $orderId, 6, '0', STR_PAD_LEFT),
                'total_amount' => number_format($amount, 0, '.', ',').' UZS',
                'message' => 'Buyurtmangiz muvaffaqiyatli qabul qilindi!',
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $payload['message'] ?? 'Buyurtmani rasmiylashtirishda xatolik yuz berdi. Qayta urinib ko\'ring.',
        ], $inner?->getStatusCode() ?: 500);
    }

    /** "Jamshid Karimov" -> ['Jamshid', 'Karimov']. Familiyasiz bo'lsa lastname bo'sh qoladi. */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [
            Str::limit($parts[0] ?? 'Mijoz', 30, ''),
            Str::limit($parts[1] ?? '', 30, ''),
        ];
    }
}
