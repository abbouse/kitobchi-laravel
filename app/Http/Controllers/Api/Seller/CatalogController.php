<?php

namespace App\Http\Controllers\Api\Seller;

use App\Models\BookEdition;
use App\Models\BookEditionSubmission;
use App\Models\Books;
use App\Services\BranchStockService;
use App\Services\Catalog\BarcodeIsbnReader;
use App\Services\Catalog\BuyBoxService;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use App\Support\ProductArtikul;
use App\Support\ProductImageUrls;
use App\Support\ProductImageVariantGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Do'kon ilovasi: GLOBAL KATALOG orqali kitob qo'shish.
 *
 *   GET  catalog/lookup?isbn=...     ISBN skan → karta(lar) yoki "topilmadi"
 *   GET  catalog/search?q=...        nom/muallif bo'yicha qidiruv
 *   GET  catalog/editions/{id}       bitta karta
 *   POST catalog/offers              topilgan kartaga taklif (narx + qoldiq)
 *   POST catalog/submissions         topilmagan kitob: old + orqa muqova majburiy
 *   GET  catalog/submissions         do'kon arizalari holati
 *   POST catalog/editions/{id}/correction   kartadagi xato haqida tuzatish taklifi
 *
 * MUHIM: kitobning O'ZINIKI bo'lgan ma'lumotlari (nom, muallif, muqova, tavsif,
 * rasmlar) faqat katalog kartasida turadi va do'kon ularni o'zgartira olmaydi —
 * xato bo'lsa shu yerdan tuzatish TAKLIFI yuboriladi, qarorni admin qabul qiladi.
 */
class CatalogController extends ProductController
{
    public function lookup(Request $request, CatalogService $catalog): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $raw = (string) $request->query('isbn', '');
        $problem = Isbn::problem($raw);
        if ($problem !== null) {
            return response()->json([
                'success' => false,
                'code' => 'isbn_' . $problem,
                'message' => match ($problem) {
                    'checksum' => "ISBN noto'g'ri o'qildi (nazorat raqami mos emas). Qayta skan qiling yoki tekshirib kiriting.",
                    'empty' => 'ISBN kiriting.',
                    default => "ISBN 10 yoki 13 raqamdan iborat bo'lishi kerak.",
                },
            ], 422);
        }

        $storeSellerId = $this->storeSellerId();
        $isbn13 = Isbn::toIsbn13($raw);
        $editions = $catalog->findByIsbn($isbn13, $storeSellerId);
        $this->warmMyOffers($editions, $storeSellerId);

        return response()->json([
            'success' => true,
            'found' => $editions->isNotEmpty(),
            'isbn13' => $isbn13,
            'editions' => $editions->map(fn (BookEdition $e) => $this->editionPayload($e, $storeSellerId))->values(),
        ]);
    }

    public function search(Request $request, CatalogService $catalog): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['success' => true, 'editions' => []]);
        }

        $storeSellerId = $this->storeSellerId();

        $editions = $catalog->search($query, 30, $storeSellerId);
        $this->warmMyOffers($editions, $storeSellerId);

        return response()->json([
            'success' => true,
            'editions' => $editions->map(fn (BookEdition $e) => $this->editionPayload($e, $storeSellerId))->values(),
        ]);
    }

    public function show(int $id, CatalogService $catalog): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $edition = $this->selectableEdition($catalog, $id);
        if (! $edition) {
            return response()->json(['success' => false, 'message' => 'Kitob katalogda topilmadi'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->editionPayload($edition, $this->storeSellerId(), true),
        ]);
    }

    /** Katalogdagi kitobga do'kon taklifi: faqat narx, qoldiq va holat. */
    public function storeOffer(Request $request, CatalogService $catalog, BranchStockService $stock): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'edition_id' => 'required|integer',
            'price' => 'required|integer|min:1|max:100000000',
            'discountPrice' => 'nullable|integer|min:0|lt:price',
            'discountExpiresAt' => 'nullable|date|after:now',
            'count' => 'required|integer|min:0|max:100000',
            // Do'kondagi jismoniy kitob xarakteristikasi (ixtiyoriy tekshiruv)
            'coverType' => 'nullable|string|in:soft,hard',
            'language' => 'nullable|string|in:uz,ru,en,qq',
            'languageWrite' => 'nullable|string|in:cyrillic,latin',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $edition = $this->selectableEdition($catalog, (int) $request->input('edition_id'));
        if (! $edition) {
            return response()->json(['success' => false, 'message' => 'Kitob katalogda topilmadi'], 404);
        }

        // Do'kondagi kitob boshqa nashr bo'lsa (masalan qattiq muqova, karta esa
        // yumshoq) — bu kartaga ulanmaydi, ariza orqali alohida karta ochiladi.
        $diff = CatalogService::variantDifferences(
            $request->input('coverType'), $request->input('language'), $request->input('languageWrite'),
            $edition->coverType, $edition->lang, $edition->langType
        );
        if ($diff !== []) {
            return response()->json([
                'success' => false,
                'code' => 'variant_mismatch',
                'message' => "Bu karta boshqa nashr: " . $this->variantLabel($diff)
                    . ". Sizdagi kitob uchun alohida karta ochiladi — \"Bu boshqa nashr\" orqali ariza yuboring.",
                'differences' => array_keys($diff),
                'edition' => $this->editionPayload($edition, $this->storeSellerId()),
            ], 409);
        }

        $staff = Auth::guard('seller')->user();
        $storeSellerId = $this->storeSellerId();

        $existing = $this->activeOffer($storeSellerId, (int) $edition->id);
        if ($existing) {
            return response()->json([
                'success' => false,
                'code' => 'offer_exists',
                'message' => "Bu kitob do'koningizda allaqachon bor. Qoldiq yoki narxni o'zgartiring.",
                'offer' => $this->offerPayload($existing),
            ], 409);
        }

        $book = DB::transaction(function () use ($request, $catalog, $stock, $edition, $staff, $storeSellerId) {
            $book = Books::create($catalog->offerAttributes($edition) + [
                'edition_id' => $edition->id,
                'seller_id' => $storeSellerId,
                'price' => (int) $request->input('price'),
                'discountPrice' => (int) $request->input('discountPrice', 0),
                'discountExpiresAt' => $request->input('discountExpiresAt'),
                'status' => true,
                'is_hidden' => false,
            ]);

            $book->forceFill(['artikul' => ProductArtikul::generate('book', (int) $book->id)])->saveQuietly();

            $tagIds = array_values(array_filter(array_map('intval', (array) ($edition->tag_ids ?? []))));
            if (! empty($tagIds)) {
                $book->tags()->sync($tagIds);
            }

            $stock->setTotalFromLegacy(
                'book', (int) $book->id, 0, $storeSellerId,
                (int) $request->input('count'),
                $staff->seller_location_id ? (int) $staff->seller_location_id : null,
                ['actor_type' => 'seller', 'actor_id' => $staff->id, 'note' => 'Katalogdan qo\'shildi']
            );

            return $book;
        });

        $this->writeLog($staff, "Katalogdan kitob qo'shdi", $book->name);

        return response()->json([
            'success' => true,
            'message' => (int) $book->fresh()->is_approved === 1
                ? "Kitob do'koningizga qo'shildi va sotuvda."
                : "Kitob qo'shildi. Katalog kartasi tekshiruvdan o'tgach sotuvga chiqadi.",
            'offer' => $this->offerPayload($book->fresh()),
        ], 201);
    }

    /**
     * KATALOGGA QO'SHISH SO'ROVI.
     *
     * Do'kon kitob ma'lumotini kiritmaydi — u faqat ISBN'ni skanerlaydi va
     * old/orqa muqovani suratga oladi. Kartani admin ochadi (Boshqaruv →
     * Katalog → Arizalar), do'kon esa karta tayyor bo'lgach narx va qoldiqni
     * kiritadi. Orqa muqovadagi shtrix-kod server tomonda o'qilib, kiritilgan
     * ISBN bilan solishtiriladi.
     *
     * ESKI ILOVALAR: to'liq forma (name, author, pages…) yuboradigan versiyalar
     * avvalgidek ishlaydi — karta va taklif darhol ochiladi. Yangi ilova esa
     * faqat ISBN va ikki rasm yuboradi.
     */
    public function storeSubmission(Request $request, CatalogService $catalog, BranchStockService $stock, BarcodeIsbnReader $reader): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        // To'liq forma yuborilganmi (eski ilova) yoki qisqa so'rovmi (yangi ilova)
        $legacyForm = $request->filled('name');

        $required = fn (string $rules) => $legacyForm ? $rules : str_replace('required', 'nullable', $rules);

        $validator = Validator::make($request->all(), [
            // Yangi oqimda ISBN majburiy: karta aynan shu kitobga ochiladi
            'isbn' => ($legacyForm ? 'nullable' : 'required') . '|string|max:20',
            'back_isbn_client' => 'nullable|string|max:20',
            'front_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240|dimensions:max_width=8000,max_height=8000',
            'back_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240|dimensions:max_width=8000,max_height=8000',
            'images' => 'nullable|array|max:6',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240|dimensions:max_width=8000,max_height=8000',
            'note' => 'nullable|string|max:500',
            'name' => $required('required|string|max:255'),
            'author' => $required('required|string|max:255'),
            'translator' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'pages' => $required('required|integer|min:1|max:20000'),
            'year' => 'nullable|integer|min:1800|max:' . (now()->year + 1),
            'language' => $required('required|string|in:uz,ru,en,qq'),
            'languageWrite' => $required('required|string|in:cyrillic,latin'),
            'coverType' => $required('required|string|in:soft,hard'),
            'description' => $required('required|string|max:10000'),
            'category_id' => $required('required|integer|exists:book_categories,id'),
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:book_tags,id',
            'price' => $required('required|integer|min:1|max:100000000'),
            'discountPrice' => 'nullable|integer|min:0|lt:price',
            'count' => $required('required|integer|min:0|max:100000'),
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $isbnInput = trim((string) $request->input('isbn', ''));
        $isbn13 = null;
        if ($isbnInput !== '') {
            $isbn13 = Isbn::toIsbn13($isbnInput);
            if ($isbn13 === null) {
                return response()->json([
                    'success' => false,
                    'code' => 'isbn_invalid',
                    'message' => "ISBN noto'g'ri (nazorat raqami mos emas). Orqa muqovadagi raqamni tekshiring.",
                ], 422);
            }

            // Karta allaqachon bor — dublikat ochilmasin, taklif oqimiga yo'naltiramiz.
            // MUHIM: muqova/til/yozuv farq qilsa bu BOSHQA nashr — yangi karta ochiladi
            // (bitta ISBN ostida qattiq va yumshoq muqova aralashib ketmasligi uchun).
            // Qisqa so'rovda nom yo'q — shu ISBN bilan mos variantli birinchi karta.
            $candidates = $catalog->findByIsbn($isbn13, $this->storeSellerId());
            $match = $legacyForm
                ? $candidates->first(
                    fn (BookEdition $e) => CatalogService::titlesSimilar($request->input('name'), $e->title)
                        && CatalogService::sameVariant(
                            $request->input('coverType'), $request->input('language'), $request->input('languageWrite'),
                            $e->coverType, $e->lang, $e->langType
                        )
                )
                : $candidates->first(
                    fn (BookEdition $e) => CatalogService::sameVariant(
                        $request->input('coverType'), $request->input('language'), $request->input('languageWrite'),
                        $e->coverType, $e->lang, $e->langType
                    )
                );
            if ($match) {
                return response()->json([
                    'success' => false,
                    'code' => 'edition_exists',
                    'message' => "Bu kitob katalogda bor — faqat narx va qoldiqni kiriting.",
                    'edition' => $this->editionPayload($match, $this->storeSellerId()),
                ], 409);
            }

            // Shu ISBN bo'yicha ochiq so'rov bor — ikkinchisi kerak emas
            $open = BookEditionSubmission::query()
                ->where('seller_id', $this->storeSellerId())
                ->where('type', BookEditionSubmission::TYPE_NEW)
                ->where('isbn13', $isbn13)
                ->where('status', BookEditionSubmission::STATUS_PENDING)
                ->first();
            if ($open) {
                return response()->json([
                    'success' => false,
                    'code' => 'request_pending',
                    'message' => "Bu kitob bo'yicha so'rovingiz allaqachon ko'rib chiqilmoqda.",
                    'data' => $this->submissionPayload($open),
                ], 409);
            }
        }

        $front = $this->storeImage($request->file('front_image'), 'front');
        $back = $this->storeImage($request->file('back_image'), 'back');
        $extra = [];
        foreach ((array) $request->file('images', []) as $index => $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $extra[] = $this->storeImage($file, 'extra' . $index);
            }
        }

        // Orqa muqova ISBN tekshiruvi (zbar — sinxron; AI vision — javobdan keyin)
        $server = $isbn13 ? $reader->read($back, false) : ['isbn' => null, 'method' => null];
        $client = Isbn::toIsbn13($request->input('back_isbn_client'));
        if ($isbn13 && $server['isbn'] && $server['isbn'] !== $isbn13) {
            // Shtrix-kod aniq o'qildi va boshqa kitobniki — yuklangan rasmlarni o'chiramiz
            Storage::disk('public')->delete(array_filter([$front, $back, ...$extra]));

            return response()->json([
                'success' => false,
                'code' => 'isbn_mismatch',
                'message' => "Orqa muqovadagi ISBN ({$server['isbn']}) kiritilgan ISBN'dan farq qiladi.",
                'back_isbn' => $server['isbn'],
            ], 422);
        }

        $check = match (true) {
            $isbn13 === null => BookEditionSubmission::CHECK_NO_ISBN,
            ($server['isbn'] ?? $client) === $isbn13 => BookEditionSubmission::CHECK_MATCHED,
            $server['isbn'] === null && $client !== null && $client !== $isbn13 => BookEditionSubmission::CHECK_MISMATCH,
            default => BookEditionSubmission::CHECK_UNREADABLE,
        };

        $staff = Auth::guard('seller')->user();
        $storeSellerId = $this->storeSellerId();

        // ── QISQA SO'ROV: faqat ariza yoziladi ─────────────────────────────
        // Karta ochilmaydi (kitob ma'lumotini bilmaymiz) va taklif ham
        // yaratilmaydi (narx/qoldiq hali yo'q). Admin kartani ochgach, do'kon
        // "Arizalarim" ro'yxatidan narx va qoldiqni kiritadi.
        if (! $legacyForm) {
            $submission = BookEditionSubmission::create([
                'seller_id' => $storeSellerId,
                'type' => BookEditionSubmission::TYPE_NEW,
                'staff_id' => $staff->id,
                'isbn13' => $isbn13,
                'back_isbn_server' => $server['isbn'],
                'back_isbn_server_method' => $server['method'],
                'back_isbn_client' => $client,
                'isbn_check' => $check,
                'front_image' => $front,
                'back_image' => $back,
                'payload' => [
                    'isbn' => $isbnInput,
                    'note' => $request->filled('note') ? trim((string) $request->input('note')) : null,
                    // "Boshqa nashr" so'rovi: do'kondagi kitob muqovasi — admin
                    // kartani shu variant bilan ochadi
                    'coverType' => $request->input('coverType'),
                    'language' => $request->input('language'),
                    'languageWrite' => $request->input('languageWrite'),
                    'images' => $extra,
                ],
                'status' => BookEditionSubmission::STATUS_PENDING,
            ]);

            if ($isbn13 && $server['isbn'] === null) {
                $submissionId = $submission->id;
                dispatch(function () use ($submissionId) {
                    app(self::class)->refreshBackIsbn($submissionId);
                })->afterResponse();
            }

            $this->writeLog($staff, "Katalogga kitob qo'shishni so'radi", $isbn13 ?: $isbnInput);

            return response()->json([
                'success' => true,
                'message' => "So'rov yuborildi. Kitob katalogga qo'shilgach sizga xabar beramiz — keyin narx va qoldiqni kiritasiz.",
                'isbn_check' => $check,
                'data' => $this->submissionPayload($submission->fresh()),
            ], 201);
        }

        $author = $this->authorDirectory->resolveOrCreateByName($request->input('author'));
        $publisher = $this->publisherDirectory->resolveOrCreateByName($request->input('publisher'));

        [$edition, $book, $submission] = DB::transaction(function () use (
            $request, $catalog, $stock, $staff, $storeSellerId, $isbn13, $front, $back, $extra,
            $server, $client, $check, $author, $publisher
        ) {
            $title = trim((string) $request->input('name'));
            $edition = BookEdition::create([
                'isbn13' => $isbn13,
                'isbn10' => Isbn::toIsbn10($isbn13),
                'title' => $title,
                'author' => $author?->name ?: $request->input('author'),
                'author_id' => $author?->id,
                'translator' => $request->input('translator'),
                'publisher_id' => $publisher?->id,
                'category_id' => (int) $request->input('category_id'),
                'lang' => $request->input('language'),
                'langType' => $request->input('languageWrite'),
                'coverType' => $request->input('coverType'),
                'year' => $request->input('year') ?: null,
                'pages' => (int) $request->input('pages'),
                'description' => $request->input('description'),
                'front_image' => $front,
                'back_image' => $back,
                'images' => array_values(array_filter([$front, $back, ...$extra])),
                'tag_ids' => array_values(array_map('intval', (array) $request->input('tag_ids', []))),
                'status' => BookEdition::STATUS_PENDING,
                'source' => 'seller',
                'created_by_type' => 'seller',
                'created_by_id' => $storeSellerId,
                'match_key' => CatalogService::matchKey($title, $author?->name ?: $request->input('author'), $request->input('language'), $request->input('languageWrite'), $request->input('coverType'), $publisher?->id),
            ]);

            $book = Books::create($catalog->offerAttributes($edition) + [
                'edition_id' => $edition->id,
                'seller_id' => $storeSellerId,
                'price' => (int) $request->input('price'),
                'discountPrice' => (int) $request->input('discountPrice', 0),
                'status' => true,
                'is_hidden' => false,
            ]);
            $book->forceFill(['artikul' => ProductArtikul::generate('book', (int) $book->id)])->saveQuietly();
            if (! empty($edition->tag_ids)) {
                $book->tags()->sync($edition->tag_ids);
            }

            $stock->setTotalFromLegacy(
                'book', (int) $book->id, 0, $storeSellerId,
                (int) $request->input('count'),
                $staff->seller_location_id ? (int) $staff->seller_location_id : null,
                ['actor_type' => 'seller', 'actor_id' => $staff->id, 'note' => 'Yangi kitob arizasi']
            );

            $submission = BookEditionSubmission::create([
                'seller_id' => $storeSellerId,
                'type' => BookEditionSubmission::TYPE_NEW,
                'staff_id' => $staff->id,
                'edition_id' => $edition->id,
                'book_id' => $book->id,
                'isbn13' => $isbn13,
                'back_isbn_server' => $server['isbn'],
                'back_isbn_server_method' => $server['method'],
                'back_isbn_client' => $client,
                'isbn_check' => $check,
                'front_image' => $front,
                'back_image' => $back,
                'payload' => $request->only(['name', 'author', 'translator', 'publisher', 'pages', 'year', 'language', 'languageWrite', 'coverType', 'category_id', 'tag_ids', 'isbn', 'back_isbn_client']),
                'status' => BookEditionSubmission::STATUS_PENDING,
            ]);

            return [$edition, $book, $submission];
        });

        // zbar o'qiy olmagan bo'lsa — AI vision bilan qayta urinish (so'rovni kutdirmasdan)
        if ($isbn13 && $server['isbn'] === null) {
            $submissionId = $submission->id;
            dispatch(function () use ($submissionId) {
                app(self::class)->refreshBackIsbn($submissionId);
            })->afterResponse();
        }

        $this->writeLog($staff, "Katalogga yangi kitob yubordi (tekshiruvga)", $edition->title);

        return response()->json([
            'success' => true,
            'message' => "Kitob tekshiruvga yuborildi. Tasdiqlangach katalogga qo'shiladi.",
            'isbn_check' => $check,
            'submission' => $this->submissionPayload($submission->fresh()),
            'offer' => $this->offerPayload($book->fresh()),
        ], 201);
    }

    /**
     * Kartadagi xato haqida tuzatish taklifi. Do'kon hech narsani o'zgartirmaydi —
     * ariza moderatsiya navbatiga tushadi (Boshqaruv → Katalog → Arizalar).
     */
    public function storeCorrection(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'field' => 'nullable|string|in:name,author,translator,publisher,category,language,coverType,pages,year,description,images,isbn,other',
            'message' => 'required|string|min:5|max:2000',
            'suggested' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120|dimensions:max_width=8000,max_height=8000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $edition = $this->selectableEdition(app(CatalogService::class), $id);
        if (! $edition) {
            return response()->json(['success' => false, 'message' => 'Kitob katalogda topilmadi'], 404);
        }

        $storeSellerId = $this->storeSellerId();

        // Bir kartaga bir do'kondan bitta ochiq ariza yetarli (spam bo'lmasin)
        $open = BookEditionSubmission::query()
            ->where('seller_id', $storeSellerId)
            ->where('edition_id', $edition->id)
            ->where('type', BookEditionSubmission::TYPE_CORRECTION)
            ->where('status', BookEditionSubmission::STATUS_PENDING)
            ->first();
        if ($open) {
            return response()->json([
                'success' => false,
                'code' => 'correction_pending',
                'message' => "Bu kitob bo'yicha tuzatish taklifingiz allaqachon ko'rib chiqilmoqda.",
                'data' => $this->submissionPayload($open),
            ], 409);
        }

        $images = [];
        foreach ((array) $request->file('images', []) as $index => $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $images[] = $this->storeImage($file, 'fix' . $index);
            }
        }

        $staff = Auth::guard('seller')->user();
        $submission = BookEditionSubmission::create([
            'seller_id' => $storeSellerId,
            'type' => BookEditionSubmission::TYPE_CORRECTION,
            'staff_id' => $staff?->id,
            'edition_id' => $edition->id,
            'book_id' => Books::query()
                ->where('seller_id', $storeSellerId)
                ->where('edition_id', $edition->id)
                ->whereNull('archived_at')
                ->value('id'),
            'isbn13' => $edition->isbn13,
            'payload' => [
                'name' => $edition->title,
                'author' => $edition->author,
                'field' => $request->input('field') ?: 'other',
                'message' => trim((string) $request->input('message')),
                'suggested' => $request->filled('suggested') ? trim((string) $request->input('suggested')) : null,
                'images' => $images,
            ],
            'front_image' => $images[0] ?? null,
            'status' => BookEditionSubmission::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Tuzatish taklifi yuborildi. Admin tekshirgach karta yangilanadi.",
            'data' => $this->submissionPayload($submission),
        ], 201);
    }

    public function submissions(Request $request): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $items = BookEditionSubmission::query()
            ->where('seller_id', $this->storeSellerId())
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->latest('id')
            ->paginate(min(50, max(5, (int) $request->query('per_page', 20))));

        return response()->json([
            'success' => true,
            'data' => collect($items->items())->map(fn ($s) => $this->submissionPayload($s))->values(),
            'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()],
        ]);
    }

    /** Javobdan keyin: AI vision orqali orqa muqova ISBN'ini qayta o'qish. */
    public function refreshBackIsbn(int $submissionId): void
    {
        $submission = BookEditionSubmission::find($submissionId);
        if (! $submission || ! $submission->back_image || $submission->back_isbn_server) {
            return;
        }

        $result = app(BarcodeIsbnReader::class)->read($submission->back_image, true);
        if (! $result['isbn']) {
            return;
        }

        $submission->forceFill([
            'back_isbn_server' => $result['isbn'],
            'back_isbn_server_method' => $result['method'],
            'isbn_check' => $result['isbn'] === $submission->isbn13
                ? BookEditionSubmission::CHECK_MATCHED
                : BookEditionSubmission::CHECK_MISMATCH,
        ])->save();
    }

    // ── Yordamchilar ────────────────────────────────────────────────────

    private function denyWithoutAccess(): ?JsonResponse
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasProductAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        return null;
    }

    /**
     * Kartani id bo'yicha ochadi. XAVFSIZLIK: boshqa do'konning tekshiruvdagi
     * arizasi (uning rasmi, tavsifi) begona do'konga ko'rinmaydi — shuning
     * uchun `selectableBySeller` qidiruv/lookup bilan bir xil qo'llanadi.
     */
    private function selectableEdition(CatalogService $catalog, int $id): ?BookEdition
    {
        $edition = $catalog->resolve(BookEdition::withTrashed()->find($id));
        if (! $edition || ! $edition->isUsable()) {
            return null;
        }

        return BookEdition::query()
            ->selectableBySeller($this->storeSellerId())
            ->whereKey($edition->id)
            ->exists() ? $edition : null;
    }

    /** "muqova: kartada yumshoq, sizda qattiq" ko'rinishidagi izoh. */
    private function variantLabel(array $differences): string
    {
        $names = [
            'cover' => ['muqova', ['hard' => 'qattiq', 'soft' => 'yumshoq']],
            'lang' => ['til', ['uz' => "o'zbek", 'ru' => 'rus', 'en' => 'ingliz', 'qq' => 'qoraqalpoq']],
            'script' => ['yozuv', ['latin' => 'lotin', 'cyrillic' => 'kirill']],
        ];

        $parts = [];
        foreach ($differences as $key => [$mine, $card]) {
            [$label, $map] = $names[$key];
            $parts[] = "{$label} (kartada: " . ($map[$card] ?? $card) . ', sizda: ' . ($map[$mine] ?? $mine) . ')';
        }

        return implode(', ', $parts);
    }

    private function storeSellerId(): int
    {
        return (int) $this->getStoreSellerId(Auth::guard('seller')->user());
    }

    private function activeOffer(int $sellerId, int $editionId): ?Books
    {
        return Books::query()
            ->where('seller_id', $sellerId)
            ->where('edition_id', $editionId)
            ->whereNull('archived_at')
            ->where('is_hidden', false)
            ->withAvailableTotal()
            ->first();
    }

    private function storeImage(UploadedFile $file, string $role): string
    {
        // XAVFSIZLIK: kengaytma mijoz yuborgan nomdan EMAS, fayl mazmunidan
        // aniqlanadi (aks holda .php nomli "rasm" public diskka tushishi mumkin).
        $extension = $this->safeImageExtension($file);
        $path = Storage::disk('public')->putFileAs('books', $file, 'cat_' . $role . '_' . time() . '_' . Str::random(8) . '.' . $extension);
        $path = str_replace('public/', '', (string) $path);
        ProductImageVariantGenerator::generateForPath($path);

        return $path;
    }

    private function safeImageExtension(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->extension());

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
    }

    /** @var array<int, array>|null Ro'yxat uchun oldindan yuklangan "mening takliflarim" */
    private ?array $myOffersByEdition = null;

    /** TEZLIK: ro'yxatdagi har karta uchun alohida so'rov o'rniga bitta so'rov. */
    private function warmMyOffers(\Illuminate\Support\Collection $editions, int $storeSellerId): void
    {
        $editions->loadMissing(['publisher:id,name', 'category:id,name_uz']);

        $this->myOffersByEdition = Books::query()
            ->where('seller_id', $storeSellerId)
            ->whereIn('edition_id', $editions->pluck('id')->all())
            ->whereNull('archived_at')
            ->withAvailableTotal()
            ->get()
            ->groupBy('edition_id')
            ->map(fn ($group) => $group->map(fn (Books $b) => $this->offerPayload($b))->values()->all())
            ->all();
    }

    private function editionPayload(BookEdition $edition, int $storeSellerId, bool $withDescription = false): array
    {
        $edition->loadMissing(['publisher:id,name', 'category:id,name_uz']);
        $images = array_values(array_filter((array) ($edition->images ?? []), 'is_string'));
        if ($edition->front_image && ! in_array($edition->front_image, $images, true)) {
            array_unshift($images, $edition->front_image);
        }
        $urls = ProductImageUrls::build($images);

        $mine = $this->myOffersByEdition !== null
            ? ($this->myOffersByEdition[$edition->id] ?? [])
            : Books::query()
                ->where('seller_id', $storeSellerId)
                ->where('edition_id', $edition->id)
                ->whereNull('archived_at')
                ->withAvailableTotal()
                ->get()
                ->map(fn (Books $b) => $this->offerPayload($b))
                ->values()
                ->all();

        $payload = [
            'id' => (int) $edition->id,
            'isbn' => $edition->isbn13,
            'name' => $edition->title,
            'author' => $edition->author,
            'translator' => $edition->translator,
            'publisher_id' => $edition->publisher_id,
            'publisher_name' => $edition->publisher?->name,
            'category_id' => $edition->category_id,
            'category_name' => $edition->category?->name_uz,
            'language' => $this->normalizeLanguageOut($edition->lang),
            'languageWrite' => $this->normalizeLangTypeOut($edition->langType),
            'coverType' => $this->normalizeCoverTypeOut($edition->coverType),
            'pages' => (int) ($edition->pages ?? 0),
            'year' => (int) ($edition->year ?? 0),
            'tag_ids' => array_values((array) ($edition->tag_ids ?? [])),
            'image' => $urls['medium'][0] ?? $urls['original'][0] ?? null,
            'images' => $urls['original'],
            'thumb_images' => $urls['thumb'],
            'status' => $edition->status,
            'verified' => $edition->status === BookEdition::STATUS_ACTIVE,
            'offers_count' => (int) $edition->offers_count,
            'min_price' => $edition->min_price,
            'my_offers' => $mine,
        ];

        if ($withDescription) {
            $payload['description'] = $edition->description;
        }

        return $payload;
    }

    private function offerPayload(Books $book): array
    {
        return [
            'id' => (int) $book->id,
            'edition_id' => (int) $book->edition_id,
            'artikul' => $book->artikul,
            'name' => $book->name,
            'price' => (int) $book->price,
            'discountPrice' => (int) ($book->discountPrice ?? 0),
            'count' => $book->count,
            'is_approved' => (int) $book->is_approved,
            'status' => (bool) $book->status,
            'catalog_featured' => (bool) ($book->catalog_featured ?? true),
        ];
    }

    private function submissionPayload(BookEditionSubmission $submission): array
    {
        // Karta ochilgan bo'lsa — do'kon shu yerdan narx/qoldiq kiritadi
        $edition = $submission->edition_id
            ? $this->catalogService()->resolve(BookEdition::withTrashed()->find($submission->edition_id))
            : null;
        $ready = $edition !== null
            && $edition->isUsable()
            && $submission->book_id === null;

        return [
            'id' => (int) $submission->id,
            'type' => $submission->type ?: BookEditionSubmission::TYPE_NEW,
            'status' => $submission->status,
            'isbn' => $submission->isbn13,
            'isbn_check' => $submission->isbn_check,
            'name' => $edition?->title ?? ($submission->payload['name'] ?? null),
            'author' => $edition?->author ?? ($submission->payload['author'] ?? null),
            'note' => $submission->payload['note'] ?? null,
            'front_image' => ProductImageUrls::originalUrl($submission->front_image),
            'back_image' => ProductImageUrls::originalUrl($submission->back_image),
            'edition_id' => $submission->edition_id,
            'book_id' => $submission->book_id,
            // Karta tayyor va do'konda hali taklif yo'q — ilovada "Narx kiriting"
            'offer_ready' => $ready,
            'edition' => $ready ? $this->editionPayload($edition, $this->storeSellerId()) : null,
            'message' => $submission->payload['message'] ?? null,
            'field' => $submission->payload['field'] ?? null,
            'reject_reason' => $submission->reject_reason,
            'created_at' => $submission->created_at?->toIso8601String(),
            'reviewed_at' => $submission->reviewed_at?->toIso8601String(),
        ];
    }

    private function catalogService(): CatalogService
    {
        return app(CatalogService::class);
    }
}
