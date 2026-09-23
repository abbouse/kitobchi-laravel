<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\BookEdition;
use App\Models\BookEditionSubmission;
use App\Models\Books;
use App\Models\Publisher;
use App\Models\Seller;
use App\Services\AuthorDirectoryService;
use App\Services\BranchStockService;
use App\Services\Catalog\BuyBoxService;
use App\Services\Catalog\CatalogService;
use App\Support\Isbn;
use App\Support\ProductArtikul;
use App\Support\ProductImageUrls;
use App\Support\ProductImageVariantGenerator;
use App\Support\SharedImageGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Boshqaruv: GLOBAL KATALOG (kitob kartalari, do'kon arizalari) va
 * kitob takliflarini qo'shish / arxivlash.
 */
class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly BuyBoxService $buyBox,
    ) {}

    // ══════════════════════════ KATALOG KARTALARI ══════════════════════════

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $tab = (string) $request->query('tab', 'all');

        $query = BookEdition::query()
            ->with(['publisher:id,name', 'category:id,name_uz'])
            ->when($tab === 'deleted', fn ($q) => $q->onlyTrashed(), fn ($q) => $q->where('status', '!=', BookEdition::STATUS_MERGED))
            ->when($tab === 'unverified', fn ($q) => $q->whereNull('verified_at')->where('status', BookEdition::STATUS_ACTIVE))
            ->when($tab === 'pending', fn ($q) => $q->where('status', BookEdition::STATUS_PENDING))
            ->when($tab === 'rejected', fn ($q) => $q->where('status', BookEdition::STATUS_REJECTED))
            ->when($tab === 'no_cover', fn ($q) => $q->whereNull('front_image')->where(fn ($w) => $w->whereNull('images')->orWhereRaw('JSON_LENGTH(images) = 0')))
            ->when($tab === 'duplicates', fn ($q) => $q->whereNotNull('isbn13')->whereIn('isbn13', BookEdition::query()
                ->usable()->whereNotNull('isbn13')->groupBy('isbn13')->havingRaw('COUNT(*) > 1')->select('isbn13')))
            ->when($search !== '', function ($q) use ($search) {
                $isbn13 = Isbn::toIsbn13($search);
                $q->where(fn ($w) => $w
                    ->where('id', ctype_digit($search) ? (int) $search : 0)
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->when($isbn13, fn ($i) => $i->orWhere('isbn13', $isbn13))
                    ->orWhere('isbn13', 'like', "%{$search}%"));
            })
            ->orderByRaw($tab === 'duplicates' ? 'isbn13, id' : 'id DESC');

        $editions = $query->paginate(30)->withQueryString();

        return Inertia::render('Catalog', [
            'editions' => collect($editions->items())->map(fn (BookEdition $e) => $this->editionRow($e))->values(),
            'pagination' => $this->pagination($editions),
            'filters' => ['search' => $search, 'tab' => $tab],
            'counts' => [
                'all' => BookEdition::query()->where('status', '!=', BookEdition::STATUS_MERGED)->count(),
                'unverified' => BookEdition::query()->whereNull('verified_at')->where('status', BookEdition::STATUS_ACTIVE)->count(),
                'pending' => BookEdition::query()->where('status', BookEdition::STATUS_PENDING)->count(),
                'rejected' => BookEdition::query()->where('status', BookEdition::STATUS_REJECTED)->count(),
                'deleted' => BookEdition::onlyTrashed()->count(),
                'submissions' => BookEditionSubmission::query()->where('status', BookEditionSubmission::STATUS_PENDING)->count(),
                'unlinked' => Books::query()->whereNull('edition_id')->count(),
            ],
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function show(int $edition): Response
    {
        $model = BookEdition::withTrashed()->with(['publisher:id,name', 'category:id,name_uz', 'mergedInto:id,title'])->findOrFail($edition);

        $offers = Books::query()
            ->where('edition_id', $model->id)
            ->withAvailableTotal()
            ->with('seller:id,shop_name,status,is_hidden,isVerified')
            ->orderByDesc('catalog_featured')
            ->orderBy('price')
            ->get();

        $candidates = collect();
        if ($model->isbn13) {
            $candidates = $candidates->merge(BookEdition::query()->usable()->where('isbn13', $model->isbn13)->where('id', '!=', $model->id)->limit(10)->get());
        }
        $candidates = $candidates->merge(
            BookEdition::query()->usable()->where('id', '!=', $model->id)
                ->where('title', 'like', '%' . Str::limit($model->title, 40, '') . '%')
                ->limit(10)->get()
        )->unique('id')->values();

        return Inertia::render('CatalogEdition', [
            'edition' => $this->editionRow($model, true),
            'offers' => $offers->map(fn (Books $b) => [
                'id' => $b->id,
                'artikul' => $b->artikul,
                'seller' => $b->seller?->shop_name ?: ('#' . $b->seller_id),
                'sellerId' => $b->seller_id,
                'sellerActive' => $b->seller && $b->seller->status === 'approved' && ! $b->seller->is_hidden,
                'price' => (int) $b->price,
                'discountPrice' => (int) ($b->discountPrice ?? 0),
                'effectivePrice' => BuyBoxService::effectivePrice($b),
                'stock' => (int) $b->count,
                'condition' => $b->condition ?? 'new',
                'featured' => (bool) $b->catalog_featured,
                'approved' => (int) $b->is_approved,
                'active' => (bool) $b->status,
                'hidden' => (bool) $b->is_hidden,
                'archived' => $b->archived_at !== null,
                'sold' => (int) ($b->totalSales ?? 0),
                'url' => route('boshqaruv.books', ['books_search' => $b->id, 'books_tab' => 'all']),
            ])->values(),
            'submissions' => BookEditionSubmission::query()->where('edition_id', $model->id)->latest('id')->limit(20)->get()
                ->map(fn ($s) => $this->submissionRow($s))->values(),
            'mergeCandidates' => $candidates->map(fn (BookEdition $e) => $this->editionRow($e))->values(),
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function store(Request $request, AuthorDirectoryService $authors): RedirectResponse
    {
        $data = $this->validateEdition($request, null);
        $data = $this->withAuthor($data, $authors);
        $images = $this->storeImages($request);

        $edition = BookEdition::create($data + [
            'front_image' => $images['front'] ?? null,
            'back_image' => $images['back'] ?? null,
            'images' => $images['all'],
            'status' => BookEdition::STATUS_ACTIVE,
            'source' => 'admin',
            'created_by_type' => 'admin',
            'created_by_id' => Auth::guard('panel')->id(),
            'verified_at' => now(),
        ]);

        return redirect()->route('boshqaruv.catalog.show', $edition->id)->with('success', "Kitob katalogga qo'shildi.");
    }

    public function update(Request $request, int $edition, AuthorDirectoryService $authors): RedirectResponse
    {
        $model = BookEdition::withTrashed()->findOrFail($edition);
        $data = $this->validateEdition($request, $model);
        $data = $this->withAuthor($data, $authors);

        // Rasmlar: saqlanadiganlar (tartib bilan) + yangi yuklanganlar
        $keep = array_values(array_filter((array) json_decode((string) $request->input('images_keep', '[]'), true), 'is_string'));
        $current = array_values(array_filter((array) ($model->images ?? []), 'is_string'));
        $keep = array_values(array_intersect($keep, array_merge($current, array_filter([$model->front_image, $model->back_image]))));
        $uploaded = $this->storeImages($request);

        $front = $uploaded['front'] ?? (in_array($model->front_image, $keep, true) ? $model->front_image : ($keep[0] ?? null));
        $back = $uploaded['back'] ?? (in_array($model->back_image, $keep, true) ? $model->back_image : null);
        $all = array_values(array_unique(array_filter(array_merge([$front, $back], $keep, $uploaded['extra']))));

        $model->update($data + [
            'front_image' => $front,
            'back_image' => $back,
            'images' => $all,
            'verified_at' => $model->verified_at ?? ($request->boolean('verify') ? now() : null),
        ]);

        // Karta — YAGONA manba: takliflardagi nusxa har doim shundan yangilanadi
        // (tanlov yo'q, aks holda do'kon kartalari darhol chetga chiqib ketardi).
        $synced = $this->catalog->syncOffers($model->fresh());
        $this->buyBox->touch((int) $model->id);

        return back()->with('success', "Kitob kartasi saqlandi" . ($synced ? " va {$synced} ta taklifga ko'chirildi." : '.'));
    }

    public function verify(int $edition): RedirectResponse
    {
        $model = BookEdition::withTrashed()->find($edition);
        if (! $model || $model->trashed()) {
            return back()->with('error', "Karta topilmadi (o'chirilgan).");
        }
        if (in_array($model->status, [BookEdition::STATUS_MERGED, BookEdition::STATUS_REJECTED], true)) {
            return back()->with('error', 'Bu karta tasdiqlanmaydi (birlashtirilgan yoki rad etilgan).');
        }

        DB::transaction(function () use ($model) {
            $model->forceFill(['status' => BookEdition::STATUS_ACTIVE, 'verified_at' => now()])->save();
            $this->approvePendingOffers($model, 'Katalog kartasi admin tomonidan tasdiqlandi.');
            $this->catalog->syncOffers($model->fresh());
            // FAQAT yangi kitob arizalari yopiladi — tuzatish takliflari
            // kartani tasdiqlash bilan hal bo'lmaydi va navbatda qoladi.
            BookEditionSubmission::query()
                ->where('edition_id', $model->id)
                ->where('type', BookEditionSubmission::TYPE_NEW)
                ->where('status', BookEditionSubmission::STATUS_PENDING)
                ->update(['status' => BookEditionSubmission::STATUS_APPROVED, 'reviewer_id' => Auth::guard('panel')->id(), 'reviewed_at' => now()]);
        });

        return back()->with('success', 'Karta tasdiqlandi, takliflar sotuvga chiqdi.');
    }

    public function merge(Request $request, int $edition): RedirectResponse
    {
        $data = $request->validate(['into_id' => 'required|integer']);
        $from = BookEdition::findOrFail($edition);
        $into = $this->catalog->resolve(BookEdition::find((int) $data['into_id']));
        if (! $into || $into->id === $from->id || ! $into->isUsable()) {
            return back()->with('error', 'Birlashtiriladigan karta topilmadi.');
        }

        $moved = DB::transaction(function () use ($from, $into) {
            $moved = $this->catalog->merge($from, $into);
            if ($into->status === BookEdition::STATUS_ACTIVE) {
                $this->approvePendingOffers($into, 'Mavjud katalog kartasiga birlashtirildi.');
            }
            BookEditionSubmission::query()
                ->where('edition_id', $into->id)
                ->where('type', BookEditionSubmission::TYPE_NEW)
                ->where('status', BookEditionSubmission::STATUS_PENDING)
                ->update(['status' => BookEditionSubmission::STATUS_MERGED, 'reviewer_id' => Auth::guard('panel')->id(), 'reviewed_at' => now()]);

            return $moved;
        });

        return redirect()->route('boshqaruv.catalog.show', $into->id)->with('success', "Birlashtirildi: {$moved} ta taklif ko'chirildi.");
    }

    public function destroy(int $edition): RedirectResponse
    {
        $model = BookEdition::findOrFail($edition);
        $activeOffers = Books::query()->where('edition_id', $model->id)->whereNull('archived_at')->count();
        if ($activeOffers > 0) {
            return back()->with('error', "Kartada {$activeOffers} ta faol taklif bor. Avval ularni boshqa kartaga birlashtiring yoki arxivlang.");
        }

        $model->delete();

        return redirect()->route('boshqaruv.catalog')->with('success', "Karta o'chirildi (qayta tiklash mumkin).");
    }

    public function restore(int $edition): RedirectResponse
    {
        BookEdition::onlyTrashed()->findOrFail($edition)->restore();

        return back()->with('success', 'Karta qayta tiklandi.');
    }

    /** Tanlov oynalari uchun (admin kitob qo'shish, birlashtirish). */
    public function search(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->catalog->search((string) $request->query('q', ''), 20)
                ->map(fn (BookEdition $e) => $this->editionRow($e))
                ->values(),
        ]);
    }

    // ══════════════════════════ DO'KON ARIZALARI ══════════════════════════

    public function submissions(Request $request): Response
    {
        $tab = (string) $request->query('tab', 'pending');
        // Navbat ikki xil: yangi kitob arizasi va kartadagi xatoni tuzatish taklifi
        $type = in_array($request->query('type'), [BookEditionSubmission::TYPE_NEW, BookEditionSubmission::TYPE_CORRECTION], true)
            ? (string) $request->query('type')
            : BookEditionSubmission::TYPE_NEW;
        $items = BookEditionSubmission::query()
            ->with(['seller:id,shop_name', 'edition' => fn ($q) => $q->with(['publisher:id,name', 'category:id,name_uz'])])
            ->where('type', $type)
            ->when($tab !== 'all', fn ($q) => $q->where('status', $tab))
            ->orderByRaw("CASE isbn_check WHEN 'mismatch' THEN 0 WHEN 'unreadable' THEN 1 WHEN 'no_isbn' THEN 2 ELSE 3 END")
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('CatalogSubmissions', [
            'submissions' => collect($items->items())->map(function (BookEditionSubmission $s) use ($type) {
                $row = $this->submissionRow($s);
                $row['edition'] = $s->edition ? $this->editionRow($s->edition, true) : null;
                // Dublikatlar faqat yangi kitob arizalarida kerak (tuzatish
                // taklifida karta allaqachon ma'lum) — har qator uchun qo'shimcha
                // so'rov qilinmaydi.
                $row['duplicates'] = $s->edition && $type === BookEditionSubmission::TYPE_NEW && filled($s->isbn13)
                    ? $this->catalog->findByIsbn($s->isbn13)
                        ->where('id', '!=', $s->edition_id)
                        ->loadMissing(['publisher:id,name', 'category:id,name_uz'])
                        ->map(fn ($e) => $this->editionRow($e))->values()
                    : [];

                return $row;
            })->values(),
            'pagination' => $this->pagination($items),
            'filters' => ['tab' => $tab, 'type' => $type],
            'counts' => collect(['pending', 'approved', 'rejected', 'merged'])
                ->mapWithKeys(fn ($s) => [$s => BookEditionSubmission::query()->where('type', $type)->where('status', $s)->count()])
                ->put('all', BookEditionSubmission::query()->where('type', $type)->count())
                ->all(),
            'typeCounts' => [
                BookEditionSubmission::TYPE_NEW => BookEditionSubmission::query()->where('type', BookEditionSubmission::TYPE_NEW)->where('status', BookEditionSubmission::STATUS_PENDING)->count(),
                BookEditionSubmission::TYPE_CORRECTION => BookEditionSubmission::query()->where('type', BookEditionSubmission::TYPE_CORRECTION)->where('status', BookEditionSubmission::STATUS_PENDING)->count(),
            ],
        ]);
    }

    public function approveSubmission(BookEditionSubmission $submission): RedirectResponse
    {
        if ($submission->status !== BookEditionSubmission::STATUS_PENDING || ! $submission->edition_id) {
            return back()->with('error', "Ariza allaqachon ko'rib chiqilgan.");
        }

        $edition = BookEdition::withTrashed()->find($submission->edition_id);
        if (! $edition || $edition->trashed() || in_array($edition->status, [BookEdition::STATUS_MERGED, BookEdition::STATUS_REJECTED], true)) {
            return back()->with('error', "Bu arizaning kartasi o'chirilgan yoki birlashtirilgan — arizani rad eting.");
        }

        // Tuzatish taklifi: karta tasdiqlanmaydi (u allaqachon faol) — admin
        // kartani o'zi tahrirlaydi, bu yerda ariza yopiladi.
        if ($submission->isCorrection()) {
            $submission->forceFill([
                'status' => BookEditionSubmission::STATUS_APPROVED,
                'reviewer_id' => Auth::guard('panel')->id(),
                'reviewed_at' => now(),
            ])->save();

            return back()->with('success', 'Tuzatish taklifi qabul qilindi.');
        }

        return $this->verify((int) $submission->edition_id);
    }

    public function rejectSubmission(Request $request, BookEditionSubmission $submission): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        if ($submission->status !== BookEditionSubmission::STATUS_PENDING) {
            return back()->with('error', "Ariza allaqachon ko'rib chiqilgan.");
        }

        // Tuzatish taklifini rad etish — kartaga ham, do'kon taklifiga ham tegmaydi
        if ($submission->isCorrection()) {
            // Dalil rasmlari kerak emas — diskda qolib ketmasin
            $proof = array_values(array_filter((array) ($submission->payload['images'] ?? []), 'is_string'));
            foreach ($proof as $path) {
                if (SharedImageGuard::canDelete($path)) {
                    Storage::disk('public')->delete($path);
                    ProductImageVariantGenerator::deleteForPath($path);
                }
            }

            $submission->forceFill([
                'status' => BookEditionSubmission::STATUS_REJECTED,
                'reject_reason' => $data['reason'],
                'reviewer_id' => Auth::guard('panel')->id(),
                'reviewed_at' => now(),
            ])->save();

            return back()->with('success', 'Tuzatish taklifi rad etildi.');
        }

        DB::transaction(function () use ($submission, $data) {
            $edition = $submission->edition;
            // Karta faqat shu ariza bilan ochilgan bo'lsa rad etiladi; allaqachon
            // faol kartada boshqa do'konlarning takliflari bor bo'lishi mumkin.
            $editionRejected = $edition && $edition->status === BookEdition::STATUS_PENDING;
            if ($editionRejected) {
                $edition->forceFill(['status' => BookEdition::STATUS_REJECTED])->save();
            }

            // Rad etish faqat shu arizaga tegishli takliflarga (sabab do'kon ilovasida ko'rinadi)
            $offers = Books::query()
                ->when(
                    $editionRejected,
                    fn ($q) => $q->where('edition_id', $edition->id),
                    fn ($q) => $q->whereKey($submission->book_id)
                )
                ->where('is_approved', '!=', 2)
                ->get();
            foreach ($offers as $offer) {
                $offer->updateQuietly([
                    'is_approved' => 2,
                    'ai_moderation_status' => 'manual_rejected',
                    'ai_moderation_checked_at' => now(),
                    'ai_moderation_note' => $data['reason'],
                    'ai_moderation_meta' => ['source' => 'catalog_submission_rejected', 'submission_id' => $submission->id],
                ]);
                $this->buyBox->afterModeration($offer);
            }

            $submission->forceFill([
                'status' => BookEditionSubmission::STATUS_REJECTED,
                'reject_reason' => $data['reason'],
                'reviewer_id' => Auth::guard('panel')->id(),
                'reviewed_at' => now(),
            ])->save();
        });

        return back()->with('success', 'Ariza rad etildi.');
    }

    public function mergeSubmission(Request $request, BookEditionSubmission $submission): RedirectResponse
    {
        if ($submission->status !== BookEditionSubmission::STATUS_PENDING || ! $submission->edition_id) {
            return back()->with('error', "Ariza allaqachon ko'rib chiqilgan.");
        }
        if ($submission->isCorrection()) {
            return back()->with('error', "Tuzatish taklifi birlashtirilmaydi — kartani tahrirlang.");
        }

        return $this->merge($request, (int) $submission->edition_id);
    }

    // ══════════════════════════ KITOB TAKLIFLARI ══════════════════════════

    /** Admin do'kon nomidan kitob qo'shadi: katalog kartasi + narx/qoldiq. */
    public function storeBook(Request $request, BranchStockService $stock, AuthorDirectoryService $authors): RedirectResponse
    {
        $request->validate([
            'seller_id' => 'required|integer|exists:sellers,id',
            'edition_id' => 'nullable|integer|exists:book_editions,id',
            'price' => 'required|integer|min:1',
            'discountPrice' => 'nullable|integer|min:0|lt:price',
            'count' => 'required|integer|min:0',
            'condition' => 'nullable|in:new,used_good,used_fair',
        ]);

        $seller = Seller::query()->findOrFail((int) $request->input('seller_id'));
        if ($seller->parent_id) {
            return back()->with('error', "Taklif asosiy do'kon nomidan qo'shiladi (filial/hodim emas).");
        }

        $edition = $request->filled('edition_id')
            ? $this->catalog->resolve(BookEdition::find((int) $request->input('edition_id')))
            : null;

        $book = DB::transaction(function () use ($request, $stock, $authors, $seller, $edition) {
            if (! $edition) {
                $data = $this->withAuthor($this->validateEdition($request, null), $authors);
                $images = $this->storeImages($request);
                if (empty($images['all'])) {
                    throw ValidationException::withMessages(['front_image' => 'Kamida muqova rasmi kerak.']);
                }
                $edition = BookEdition::create($data + [
                    'front_image' => $images['front'] ?? $images['all'][0],
                    'back_image' => $images['back'] ?? null,
                    'images' => $images['all'],
                    'status' => BookEdition::STATUS_ACTIVE,
                    'source' => 'admin',
                    'created_by_type' => 'admin',
                    'created_by_id' => Auth::guard('panel')->id(),
                    'verified_at' => now(),
                ]);
            }

            $book = Books::create($this->catalog->offerAttributes($edition) + [
                'edition_id' => $edition->id,
                'seller_id' => $seller->id,
                'condition' => (string) $request->input('condition', 'new'),
                'price' => (int) $request->input('price'),
                'discountPrice' => (int) $request->input('discountPrice', 0),
                'status' => true,
                'is_hidden' => false,
            ]);
            $book->forceFill([
                'artikul' => ProductArtikul::generate('book', (int) $book->id),
                'is_approved' => 1,
                'ai_moderation_status' => 'manual_approved',
                'ai_moderation_checked_at' => now(),
                'ai_moderation_note' => "Admin tomonidan qo'shildi.",
            ])->save();

            if (! empty($edition->tag_ids)) {
                $book->tags()->sync($edition->tag_ids);
            }

            $stock->setTotalFromLegacy('book', (int) $book->id, 0, (int) $seller->id, (int) $request->input('count'), null, [
                'actor_type' => 'admin', 'actor_id' => Auth::guard('panel')->id(), 'note' => "Admin qo'shdi",
            ]);

            return $book;
        });

        return redirect()->route('boshqaruv.books', ['books_search' => $book->id, 'books_tab' => 'all'])->with('success', "Kitob qo'shildi (#{$book->id}).");
    }

    /**
     * "O'chirish" = arxivlash: buyurtma tarixi, moliyaviy yozuvlar va sharhlar
     * saqlanadi; kitob sotuvdan, savatlardan va qidiruvdan olinadi.
     */
    public function archiveBook(Books $book, BranchStockService $stock): RedirectResponse
    {
        if ($book->archived_at) {
            return back()->with('error', 'Kitob allaqachon arxivda.');
        }

        DB::transaction(function () use ($book, $stock) {
            $book->forceFill([
                'archived_at' => now(),
                'archived_by' => Auth::guard('panel')->id(),
                'archived_state' => ['is_hidden' => (bool) $book->is_hidden, 'status' => (bool) $book->status],
                'is_hidden' => true,
                'status' => false,
            ])->save();

            $stock->setTotalFromLegacy('book', (int) $book->id, 0, (int) $book->seller_id, 0, null, [
                'actor_type' => 'admin', 'actor_id' => Auth::guard('panel')->id(), 'note' => 'Admin arxivladi',
            ]);

            DB::table('my_carts')->where('product_id', $book->id)->where('product_type', 'book')->delete();
        });

        try {
            $book->unsearchable();
        } catch (\Throwable) {
        }

        return back()->with('success', "Kitob arxivlandi (buyurtma tarixi saqlanadi).");
    }

    public function restoreBook(Books $book): RedirectResponse
    {
        // Arxivlashdan oldin do'kon o'zi yashirgan bo'lsa — o'sha holat qaytadi
        $previous = (array) ($book->archived_state ?? []);
        $book->forceFill([
            'archived_at' => null,
            'archived_by' => null,
            'archived_state' => null,
            'is_hidden' => (bool) ($previous['is_hidden'] ?? false),
            'status' => (bool) ($previous['status'] ?? true),
        ])->save();

        return back()->with('success', "Kitob arxivdan qaytarildi. Qoldiqni qayta kiriting.");
    }

    // ══════════════════════════ YORDAMCHILAR ══════════════════════════

    private function approvePendingOffers(BookEdition $edition, string $note): void
    {
        $offers = Books::query()->where('edition_id', $edition->id)->where('is_approved', 0)->get();
        foreach ($offers as $offer) {
            $offer->updateQuietly([
                'is_approved' => 1,
                'ai_moderation_status' => 'manual_approved',
                'ai_moderation_checked_at' => now(),
                'ai_moderation_note' => $note,
                'ai_moderation_next_retry_at' => null,
                'ai_moderation_meta' => ['source' => 'catalog_edition_verified', 'edition_id' => $edition->id],
            ]);
            $this->buyBox->afterModeration($offer);
        }
    }

    private function validateEdition(Request $request, ?BookEdition $edition): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'translator' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'publisher_id' => 'nullable|integer|exists:publishers,id',
            'category_id' => 'required|integer|exists:book_categories,id',
            'lang' => 'nullable|string|max:20',
            'langType' => 'nullable|string|max:10',
            'coverType' => 'nullable|string|max:10',
            'year' => 'nullable|integer|min:1800|max:' . (now()->year + 1),
            'pages' => 'nullable|integer|min:0|max:20000',
            'description' => 'nullable|string|max:10000',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'front_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'back_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $isbn13 = null;
        if (filled($data['isbn'] ?? null)) {
            $isbn13 = Isbn::toIsbn13($data['isbn']);
            if ($isbn13 === null) {
                throw ValidationException::withMessages(['isbn' => "ISBN noto'g'ri (nazorat raqami mos emas)."]);
            }
        }

        return [
            'title' => trim($data['title']),
            'author' => trim($data['author']),
            'translator' => $data['translator'] ?? null,
            'isbn13' => $isbn13,
            'isbn10' => Isbn::toIsbn10($isbn13),
            'publisher_id' => $data['publisher_id'] ?? null,
            'category_id' => (int) $data['category_id'],
            'lang' => $data['lang'] ?? $edition?->lang ?? "O'zbek",
            'langType' => $data['langType'] ?? $edition?->langType ?? 'Lotin',
            'coverType' => $data['coverType'] ?? $edition?->coverType ?? 'Yumshoq',
            'year' => $data['year'] ?? null,
            'pages' => $data['pages'] ?? null,
            'description' => $data['description'] ?? null,
            'tag_ids' => array_values(array_map('intval', $data['tag_ids'] ?? ($edition?->tag_ids ?? []))),
            'match_key' => CatalogService::matchKey($data['title'], $data['author'], $data['lang'] ?? $edition?->lang, $data['langType'] ?? $edition?->langType, $data['coverType'] ?? $edition?->coverType, $data['publisher_id'] ?? null),
        ];
    }

    private function withAuthor(array $data, AuthorDirectoryService $authors): array
    {
        $author = $authors->resolveOrCreateByName($data['author'] ?? null);
        $data['author_id'] = $author?->id;
        $data['author'] = $author?->name ?: ($data['author'] ?? null);

        return $data;
    }

    /** @return array{front:?string, back:?string, extra:array, all:array} */
    private function storeImages(Request $request): array
    {
        $save = function (?UploadedFile $file, string $role): ?string {
            if (! $file || ! $file->isValid()) {
                return null;
            }
            // XAVFSIZLIK: kengaytma fayl mazmunidan (mijoz nomidan emas)
            $extension = strtolower((string) $file->extension());
            $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
            $path = Storage::disk('public')->putFileAs('books', $file, 'cat_admin_' . $role . '_' . time() . '_' . Str::random(8) . '.' . $extension);
            ProductImageVariantGenerator::generateForPath($path);

            return $path;
        };

        $front = $save($request->file('front_image'), 'front');
        $back = $save($request->file('back_image'), 'back');
        $extra = [];
        foreach ((array) $request->file('images', []) as $i => $file) {
            if ($path = $save($file, 'extra' . $i)) {
                $extra[] = $path;
            }
        }

        return [
            'front' => $front,
            'back' => $back,
            'extra' => $extra,
            'all' => array_values(array_filter([$front, $back, ...$extra])),
        ];
    }

    private function editionRow(BookEdition $e, bool $full = false): array
    {
        $images = array_values(array_filter((array) ($e->images ?? []), 'is_string'));
        if ($e->front_image && ! in_array($e->front_image, $images, true)) {
            array_unshift($images, $e->front_image);
        }

        $row = [
            'id' => $e->id,
            'title' => $e->title,
            'author' => $e->author,
            'isbn' => $e->isbn13,
            'publisher' => $e->publisher?->name,
            'category' => $e->category?->name_uz,
            'cover' => ProductImageUrls::originalUrl($images[0] ?? null),
            'status' => $e->status,
            'verified' => $e->verified_at !== null,
            'source' => $e->source,
            'offersCount' => (int) $e->offers_count,
            'inStockOffers' => (int) $e->in_stock_offers_count,
            'minPrice' => $e->min_price,
            'deleted' => $e->trashed(),
            'createdAt' => optional($e->created_at)->format('Y-m-d H:i'),
            'url' => route('boshqaruv.catalog.show', $e->id),
        ];

        if ($full) {
            $row += [
                'translator' => $e->translator,
                'publisherId' => $e->publisher_id,
                'categoryId' => $e->category_id,
                'lang' => $e->lang,
                'langType' => $e->langType,
                'coverType' => $e->coverType,
                'year' => $e->year,
                'pages' => $e->pages,
                'description' => $e->description,
                'tagIds' => array_values((array) ($e->tag_ids ?? [])),
                'frontImage' => $e->front_image,
                'backImage' => $e->back_image,
                'frontUrl' => ProductImageUrls::originalUrl($e->front_image),
                'backUrl' => ProductImageUrls::originalUrl($e->back_image),
                'rawImages' => $images,
                'images' => array_map(fn ($p) => ProductImageUrls::originalUrl($p), $images),
                'mergedInto' => $e->mergedInto ? ['id' => $e->mergedInto->id, 'title' => $e->mergedInto->title, 'url' => route('boshqaruv.catalog.show', $e->mergedInto->id)] : null,
            ];
        }

        return $row;
    }

    private function submissionRow(BookEditionSubmission $s): array
    {
        return [
            'id' => $s->id,
            'type' => $s->type ?: BookEditionSubmission::TYPE_NEW,
            'status' => $s->status,
            'seller' => $s->seller?->shop_name ?: ('#' . $s->seller_id),
            'isbn' => $s->isbn13,
            'isbnCheck' => $s->isbn_check,
            'backIsbnServer' => $s->back_isbn_server,
            'backIsbnMethod' => $s->back_isbn_server_method,
            'backIsbnClient' => $s->back_isbn_client,
            'frontUrl' => ProductImageUrls::originalUrl($s->front_image),
            'backUrl' => ProductImageUrls::originalUrl($s->back_image),
            'payload' => $s->payload,
            // Tuzatish taklifi: do'kon nima xato deganini ko'rsatamiz
            'message' => $s->payload['message'] ?? null,
            'field' => $s->payload['field'] ?? null,
            'suggested' => $s->payload['suggested'] ?? null,
            'proofUrls' => collect((array) ($s->payload['images'] ?? []))
                ->filter('is_string')
                ->map(fn ($path) => ProductImageUrls::originalUrl($path))
                ->values()
                ->all(),
            'editionId' => $s->edition_id,
            'bookId' => $s->book_id,
            'rejectReason' => $s->reject_reason,
            'createdAt' => optional($s->created_at)->format('Y-m-d H:i'),
            'reviewedAt' => optional($s->reviewed_at)->format('Y-m-d H:i'),
            'approveUrl' => route('boshqaruv.catalog.submissions.approve', $s->id),
            'rejectUrl' => route('boshqaruv.catalog.submissions.reject', $s->id),
            'mergeUrl' => route('boshqaruv.catalog.submissions.merge', $s->id),
        ];
    }

    private function formOptions(): array
    {
        return [
            'categories' => BookCategories::query()->orderBy('name_uz')->get(['id', 'name_uz'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name_uz])->values()->all(),
            'publishers' => Publisher::query()->orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
            'sellers' => Seller::query()->whereNull('parent_id')->whereNotNull('shop_name')->where('status', 'approved')->orderBy('shop_name')->get(['id', 'shop_name'])->map(fn ($s) => ['id' => $s->id, 'name' => $s->shop_name])->values()->all(),
        ];
    }

    private function pagination($paginator): array
    {
        return [
            'page' => (int) $paginator->currentPage(),
            'totalPages' => (int) $paginator->lastPage(),
            'from' => (int) ($paginator->firstItem() ?? 0),
            'to' => (int) ($paginator->lastItem() ?? 0),
            'total' => (int) $paginator->total(),
        ];
    }
}
