<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Enums\CourierOrderStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\HubStaffRole;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\ApiClient;
use App\Models\ApiClientRequestLog;
use App\Models\Author;
use App\Models\Blogger;
use App\Models\BloggerShipment;
use App\Models\BookCategories;
use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\Books;
use App\Models\BotTicket;
use App\Models\CareerApplication;
use App\Models\CashbackSetting;
use App\Models\CommissionSetting;
use App\Models\ConnectedDevice;
use App\Models\CourierBanLog;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\CourierTask;
use App\Models\CourierTransaction;
use App\Models\CuratedCollection;
use App\Models\CuratedCollectionItem;
use App\Models\DeliveryService;
use App\Models\DeliveryZoneRule;
use App\Models\FavouriteProducts;
use App\Models\FcmNotifications;
use App\Models\GiftCertificate;
use App\Models\Gifts;
use App\Models\Hub;
use App\Models\HubStaff;
use App\Models\MarketNews;
use App\Models\Message;
use App\Models\MyCart;
use App\Models\MysteryBoxPlan;
use App\Models\MysteryBoxSubscription;
use App\Models\OrderRefund;
use App\Models\PlatformExpense;
use App\Models\Policy;
use App\Models\ProductViewLog;
use App\Models\ProjectSetting;
use App\Models\Promocode;
use App\Models\Publisher;
use App\Models\Reel;
use App\Models\Report;
use App\Models\SearchHistory;
use App\Models\Seller;
use App\Models\SellerAd;
use App\Models\SellerAiAction;
use App\Models\SellerBanLog;
use App\Models\SellerContractHistory;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\SellerSupportTicket;
use App\Models\SellerSupportTicketMessage;
use App\Models\SellerTransaction;
use App\Models\Sold;
use App\Models\SplitCategoryRule;
use App\Models\SplitUserProfile;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Models\StationeryVariant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use App\Models\Vacancy;
use App\Services\AdminOrderStatusSyncService;
use App\Services\DeliveryZoneResolverService;
use App\Services\FcmRecipientService;
use App\Services\HubRoleAccessService;
use App\Services\OpenAIService;
use App\Services\PayoutReportService;
use App\Services\SellerCancellationReasonCatalog;
use App\Services\SellerOrderSettlementService;
use App\Services\SellerPremiumService;
use App\Services\SplitProfileService;
use App\Support\AdminOrderStatusPresenter;
use App\Support\ProductArtikul;
use App\Support\ProductImageUrls;
use App\Support\ProductImageVariantGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    private const CONTENT_LOCALES = ['ru', 'en', 'ja'];

    public function login(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::guard('panel')->check()) {
            return redirect()->route('boshqaruv.dashboard');
        }

        return Inertia::render('Login');
    }

    public function authenticate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $creds = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (! Auth::guard('panel')->attempt(
            ['email' => $creds['email'], 'password' => $creds['password'], 'is_active' => true],
            $request->boolean('remember')
        )) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Email yoki parol noto'g'ri, yoki akkaunt bloklangan.");
        }

        $admin = Auth::guard('panel')->user();
        $admin->update([
            'last_login_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('boshqaruv.dashboard'));
    }

    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('panel')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('boshqaruv.login')
            ->with('success', 'Tizimdan chiqildi.');
    }

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard', [
            'dashboard' => $this->dashboardPayload(),
        ]);
    }

    public function live(): Response
    {
        return Inertia::render('LiveDashboard', [
            'metrics' => [
                'orders' => $this->tableCount('solds'),
                'users' => $this->tableCount('users'),
                'books' => $this->tableCount('books'),
                'sellers' => $this->tableCount('sellers'),
            ],
            'snapshot' => $this->liveSnapshot(),
            'liveEndpoint' => route('boshqaruv.live.data'),
        ]);
    }

    public function liveData(): JsonResponse
    {
        return response()->json($this->liveSnapshot());
    }

    public function page(string $component): Response
    {
        return Inertia::render($component, $this->pagePayload($component));
    }

    public function refreshSplitProfiles(Request $request, SplitProfileService $service): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if (! empty($data['user_id'])) {
            $service->refreshUser(User::query()->findOrFail((int) $data['user_id']));

            return back()->with('success', 'Foydalanuvchi split profili yangilandi.');
        }

        $count = $service->refreshAll();

        return back()->with('success', "{$count} ta foydalanuvchi split profili yangilandi.");
    }

    public function updateSplitSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'split_enabled' => 'nullable|boolean',
            'split_public_enabled' => 'nullable|boolean',
            'split_global_min_order_sum' => 'required|integer|min:1000',
            'split_global_max_order_sum' => 'required|integer|gte:split_global_min_order_sum',
            'split_global_min_limit' => 'required|integer|min:1000',
            'split_global_max_limit' => 'required|integer|gte:split_global_min_limit',
            'split_min_completed_orders' => 'required|integer|min:1|max:100',
            'split_min_account_age_days' => 'required|integer|min:1|max:3650',
            'split_min_card_age_days' => 'required|integer|min:1|max:3650',
            'split_min_reputation_score' => 'required|numeric|min:1|max:100',
            'split_max_active_contracts' => 'required|integer|min:1|max:2',
            'split_default_fee_percent' => 'required|numeric|min:0|max:30',
            'split_card_delete_lock_enabled' => 'nullable|boolean',
            'paylov_refund_sender_card_id' => 'nullable|string|max:255',
            'paylov_refund_service_id' => 'nullable|string|max:255',
        ]);

        $settings = ProjectSetting::query()->firstOrCreate([]);
        $settings->update([
            'split_enabled' => $request->boolean('split_enabled'),
            'split_public_enabled' => $request->boolean('split_public_enabled'),
            'split_upfront_percent' => 25,
            'split_term_days' => 60,
            'split_global_min_order_sum' => $request->integer('split_global_min_order_sum'),
            'split_global_max_order_sum' => $request->integer('split_global_max_order_sum'),
            'split_global_min_limit' => $request->integer('split_global_min_limit'),
            'split_global_max_limit' => $request->integer('split_global_max_limit'),
            'split_min_completed_orders' => $request->integer('split_min_completed_orders'),
            'split_min_account_age_days' => $request->integer('split_min_account_age_days'),
            'split_min_card_age_days' => $request->integer('split_min_card_age_days'),
            'split_min_reputation_score' => round((float) $request->input('split_min_reputation_score'), 2),
            'split_max_active_contracts' => $request->integer('split_max_active_contracts'),
            'split_default_fee_percent' => round((float) $request->input('split_default_fee_percent'), 2),
            'split_card_delete_lock_enabled' => $request->boolean('split_card_delete_lock_enabled'),
            'paylov_refund_sender_card_id' => filled($data['paylov_refund_sender_card_id'] ?? null)
                ? trim((string) $data['paylov_refund_sender_card_id'])
                : null,
            'paylov_refund_service_id' => filled($data['paylov_refund_service_id'] ?? null)
                ? trim((string) $data['paylov_refund_service_id'])
                : null,
        ]);

        return back()->with('success', 'Split sozlamalari yangilandi.');
    }

    public function storeSplitCategoryRule(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'category_type' => 'required|in:book,stationery',
            'category_id' => 'required|integer|min:1',
            'enabled' => 'nullable|boolean',
            'fee_percent' => 'nullable|numeric|min:0|max:30',
            'min_order_sum_override' => 'nullable|integer|min:1000',
            'max_order_sum_override' => 'nullable|integer|min:1000',
            'upfront_percent_override' => 'nullable|integer|min:1|max:25',
        ]);

        if (
            filled($data['min_order_sum_override'] ?? null)
            && filled($data['max_order_sum_override'] ?? null)
            && (int) $data['max_order_sum_override'] < (int) $data['min_order_sum_override']
        ) {
            return back()->with('error', "Kategoriya uchun maksimal summa minimal summadan kichik bo'lishi mumkin emas.");
        }

        SplitCategoryRule::query()->updateOrCreate(
            [
                'category_type' => $data['category_type'],
                'category_id' => (int) $data['category_id'],
            ],
            [
                'enabled' => $request->boolean('enabled'),
                'fee_percent' => filled($data['fee_percent'] ?? null) ? round((float) $data['fee_percent'], 2) : null,
                'min_order_sum_override' => $data['min_order_sum_override'] ?? null,
                'max_order_sum_override' => $data['max_order_sum_override'] ?? null,
                'upfront_percent_override' => $data['upfront_percent_override'] ?? null,
            ],
        );

        return back()->with('success', 'Kategoriya split qoidasi saqlandi.');
    }

    public function destroySplitCategoryRule(SplitCategoryRule $splitCategoryRule): \Illuminate\Http\RedirectResponse
    {
        $splitCategoryRule->delete();

        return back()->with('success', 'Kategoriya split qoidasi o‘chirildi.');
    }

    public function userData(User $user): JsonResponse
    {
        return response()->json($this->userDetailPayload($user));
    }

    public function ticketData(BotTicket $ticket): JsonResponse
    {
        return response()->json($this->ticketDetailPayload($ticket));
    }

    public function sellerSupportTicketData(SellerSupportTicket $ticket): JsonResponse
    {
        return response()->json($this->sellerSupportTicketDetailPayload($ticket));
    }

    public function replySellerSupportTicket(Request $request, SellerSupportTicket $ticket): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        if ($ticket->status === 'closed') {
            return back()->with('error', 'Yopilgan seller murojaatiga javob yozib bo‘lmaydi.');
        }

        SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => Auth::guard('panel')->id(),
            'message' => $data['message'],
        ]);

        $ticket->update([
            'admin_id' => Auth::guard('panel')->id(),
            'status' => 'answered',
            'last_message_at' => now(),
            'seller_unread_count' => $ticket->seller_unread_count + 1,
            'admin_unread_count' => 0,
        ]);

        return back()->with('success', 'Seller murojaatiga javob yuborildi.');
    }

    public function closeSellerSupportTicket(Request $request, SellerSupportTicket $ticket): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'close_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $ticket->update([
            'admin_id' => Auth::guard('panel')->id(),
            'status' => 'closed',
            'closed_at' => now(),
            'close_reason' => $data['close_reason'] ?? null,
            'admin_unread_count' => 0,
        ]);

        return back()->with('success', 'Seller murojaati yopildi.');
    }

    public function chatData(int $conversation): JsonResponse
    {
        return response()->json($this->chatDetailPayload($conversation));
    }

    public function orderData(Sold $order): JsonResponse
    {
        $order->load(['user:id,name,lastname,phone_number,email,reputation_score,cash_on_delivery_allowed,cod_return_strikes', 'fulfillment.hub']);

        return response()->json($this->orderPayload($order));
    }

    public function bookClubData(BookClub $bookClub): JsonResponse
    {
        return response()->json($this->bookClubDetailPayload($bookClub));
    }

    public function destroyBookClub(BookClub $bookClub): \Illuminate\Http\RedirectResponse
    {
        $bookClub->update(['is_deleted' => true]);

        return back()->with('success', "Post o'chirildi.");
    }

    public function authorData(Author $author): JsonResponse
    {
        return response()->json($this->authorDetailPayload($author));
    }

    public function storeAuthor(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatedAuthorData($request);
        $data['image'] = $this->storeCatalogImage($request, 'authors', 'image_file', $data['image'] ?? null);

        Author::query()->create($data);

        return back()->with('success', "Muallif qo'shildi.");
    }

    public function updateAuthor(Request $request, Author $author): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatedAuthorData($request, $author);
        $nameChanged = ($data['name'] ?? $author->name) !== $author->name;

        if ($request->boolean('remove_image')) {
            $this->deleteStoredFile($author->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image_file')) {
            $this->deleteStoredFile($author->image);
            $data['image'] = $this->storeCatalogImage($request, 'authors', 'image_file');
        } elseif (array_key_exists('image', $data)) {
            $data['image'] = trim((string) ($data['image'] ?? '')) ?: null;
        }

        $author->update($data);

        if ($nameChanged) {
            Books::query()->where('author_id', $author->id)->update(['author' => $author->name]);
        }

        return back()->with('success', 'Muallif yangilandi.');
    }

    public function generateAuthorImagePrompt(Author $author): \Illuminate\Http\RedirectResponse
    {
        if (! $author->needs_ai_portrait) {
            return back()->with('error', 'Bu muallif uchun AI portret talab qilinmaydi.');
        }

        $prompt = "Photorealistic editorial portrait of author {$author->name}, shoulders-up, centered composition, soft natural studio lighting, clean neutral background, calm confident expression, realistic skin texture, high detail, bookstore catalog profile image, square 1:1 crop, no text, no watermark.";

        return back()
            ->with('success', 'AI portret prompti tayyorlandi.')
            ->with('author_ai_prompt', $prompt);
    }

    public function destroyAuthor(Author $author): \Illuminate\Http\RedirectResponse
    {
        Books::query()
            ->where('author_id', $author->id)
            ->update(['author_id' => null, 'author' => null]);

        $this->deleteStoredFile($author->image);
        $author->delete();

        return back()->with('success', "Muallif o'chirildi. Bog'langan kitoblar muallifsiz qoldirildi.");
    }

    public function publisherData(Publisher $publisher): JsonResponse
    {
        return response()->json($this->publisherDetailPayload($publisher));
    }

    public function storePublisher(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatedPublisherData($request);
        $data['image'] = $this->storeCatalogImage($request, 'publishers', 'image');

        Publisher::query()->create($data);

        return back()->with('success', "Nashriyot qo'shildi.");
    }

    public function updatePublisher(Request $request, Publisher $publisher): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatedPublisherData($request, $publisher);

        if ($request->boolean('remove_image')) {
            $this->deleteStoredFile($publisher->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            $this->deleteStoredFile($publisher->image);
            $data['image'] = $this->storeCatalogImage($request, 'publishers', 'image');
        } elseif (! $request->boolean('remove_image')) {
            unset($data['image']);
        }

        $publisher->update($data);

        return back()->with('success', 'Nashriyot yangilandi.');
    }

    public function destroyPublisher(Publisher $publisher): \Illuminate\Http\RedirectResponse
    {
        Books::query()->where('publisher_id', $publisher->id)->update(['publisher_id' => null]);

        $this->deleteStoredFile($publisher->image);
        $publisher->delete();

        return back()->with('success', "Nashriyot o'chirildi. Bog'langan kitoblar nashriyotsiz qoldirildi.");
    }

    public function storeBookCategory(Request $request): \Illuminate\Http\RedirectResponse
    {
        (new BookCategories)->forceFill($this->categoryData($request, 'book_categories'))->save();

        return back()->with('success', "Kitob kategoriyasi qo'shildi.");
    }

    public function updateBookCategory(Request $request, BookCategories $bookCategory): \Illuminate\Http\RedirectResponse
    {
        $bookCategory->forceFill($this->categoryData($request, 'book_categories'))->save();

        return back()->with('success', 'Kitob kategoriyasi yangilandi.');
    }

    public function destroyBookCategory(BookCategories $bookCategory): \Illuminate\Http\RedirectResponse
    {
        if ($bookCategory->books()->exists()) {
            return back()->with('error', "Bu kategoriyada kitoblar mavjud — o'chirib bo'lmaydi.");
        }

        $bookCategory->delete();

        return back()->with('success', "Kitob kategoriyasi o'chirildi.");
    }

    public function storeStationeryCategory(Request $request): \Illuminate\Http\RedirectResponse
    {
        (new StationeryCategory)->forceFill($this->categoryData($request, 'stationery_categories'))->save();

        return back()->with('success', "Kanstovar kategoriyasi qo'shildi.");
    }

    public function updateStationeryCategory(Request $request, StationeryCategory $stationeryCategory): \Illuminate\Http\RedirectResponse
    {
        $stationeryCategory->forceFill($this->categoryData($request, 'stationery_categories'))->save();

        return back()->with('success', 'Kanstovar kategoriyasi yangilandi.');
    }

    public function destroyStationeryCategory(StationeryCategory $stationeryCategory): \Illuminate\Http\RedirectResponse
    {
        if ($stationeryCategory->stationeries()->exists()) {
            return back()->with('error', "Bu kategoriyada mahsulotlar mavjud — o'chirib bo'lmaydi.");
        }

        $stationeryCategory->delete();

        return back()->with('success', "Kanstovar kategoriyasi o'chirildi.");
    }

    public function updateBook(Request $request, Books $book): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'translator' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'category_id' => ['required', 'exists:book_categories,id'],
            'publisher_id' => ['nullable', 'exists:publishers,id'],
            'seller_id' => ['nullable', 'exists:sellers,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0'],
            'discountPrice' => ['nullable', 'numeric', 'min:0'],
            'discountExpiresAt' => ['nullable', 'date'],
            'count' => ['required', 'integer', 'min:0'],
            'lang' => ['nullable', 'string', 'max:10'],
            'langType' => ['nullable', 'string', 'max:40'],
            'coverType' => ['nullable', 'string', 'max:40'],
            'year' => ['nullable', 'integer', 'min:0', 'max:2100'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
            'is_hidden' => ['nullable', 'boolean'],
            'recommended' => ['nullable', 'boolean'],
            'recommendedExpiresAt' => ['nullable', 'date'],
            'is_approved' => ['nullable', Rule::in([0, 1, 2, '0', '1', '2'])],
            'images_text' => ['nullable', 'string'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        $author = app(\App\Services\AuthorDirectoryService::class)->resolveOrCreateByName($request->input('author'));
        $data['artikul'] = $book->artikul ?: ProductArtikul::generate('book', (int) $book->id);
        $data['isbn'] = Books::normalizeIsbn($request->input('isbn'));
        $data['images'] = $this->syncCatalogImages($request, $book->images ?? [], 'images', 'images_text', 'books', 'admin_book');
        $data['status'] = $request->boolean('status');
        $data['is_hidden'] = $request->boolean('is_hidden');
        $data['recommended'] = $request->boolean('recommended');
        $data['author_id'] = $author?->id;
        $data['author'] = $author?->name ?: trim((string) $request->input('author'));

        $book->update($data);

        return back()->with('success', 'Kitob yangilandi.');
    }

    public function updateStationery(Request $request, Stationery $stationery): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'seller_id' => ['nullable', 'exists:sellers,id'],
            'barcode' => ['nullable', 'string', 'max:32'],
            'material' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:stationery_categories,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'discountExpiresAt' => ['nullable', 'date'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_approved' => ['nullable', Rule::in([0, 1, 2, '0', '1', '2'])],
            'status' => ['nullable', 'boolean'],
            'recommended' => ['nullable', 'boolean'],
            'recommendedExpiresAt' => ['nullable', 'date'],
            'is_hidden' => ['nullable', 'boolean'],
            'images_text' => ['nullable', 'string'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'variant_id.*' => ['nullable', 'integer', 'exists:stationery_variants,id'],
            'variant_color_name.*' => ['nullable', 'string', 'max:100'],
            'variant_stock.*' => ['nullable', 'integer', 'min:0'],
            'variant_image_existing.*' => ['nullable', 'string', 'max:1000'],
            'variant_image.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        $data['images'] = $this->syncCatalogImages($request, $stationery->images ?? [], 'images', 'images_text', 'stationery', 'admin_stationery');
        $data['artikul'] = $stationery->artikul ?: ProductArtikul::generate('stationery', (int) $stationery->id);
        $data['status'] = $request->boolean('status');
        $data['recommended'] = $request->boolean('recommended');
        $data['is_hidden'] = $request->boolean('is_hidden');

        $stationery->update($data);
        $this->syncStationeryVariants($request, $stationery);

        return back()->with('success', 'Kanselyariya mahsuloti yangilandi.');
    }

    public function storePromocode(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatedPromocodeData($request);
        $data['code'] = Str::upper($data['code']);
        $data['usedCount'] = 0;
        $data['max_discount_amount'] = $data['type'] === 'percent' ? ($data['max_discount_amount'] ?? null) : null;

        Promocode::query()->create($data);

        return back()->with('success', "Promokod qo'shildi.");
    }

    public function updatePromocode(Request $request, Promocode $promocode): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatedPromocodeData($request, $promocode);
        unset($data['code']);
        $data['max_discount_amount'] = $data['type'] === 'percent' ? ($data['max_discount_amount'] ?? null) : null;

        $promocode->update($data);

        return back()->with('success', 'Promokod yangilandi.');
    }

    public function destroyPromocode(Promocode $promocode): \Illuminate\Http\RedirectResponse
    {
        $promocode->delete();

        return back()->with('success', "Promokod o'chirildi.");
    }

    public function generatePromocode(): JsonResponse
    {
        return response()->json(['code' => Str::upper(Str::random(8))]);
    }

    public function bloggerData(Blogger $blogger): JsonResponse
    {
        $blogger->load(['shipments' => fn ($query) => $query->latest('scheduled_for')->latest('id'), 'shipments.items']);

        return response()->json($this->bloggerDetailPayload($blogger));
    }

    public function storeBlogger(Request $request): \Illuminate\Http\RedirectResponse
    {
        Blogger::query()->create($this->validatedBloggerData($request));

        return back()->with('success', "Bloger qo'shildi.");
    }

    public function updateBlogger(Request $request, Blogger $blogger): \Illuminate\Http\RedirectResponse
    {
        $blogger->update($this->validatedBloggerData($request));

        return back()->with('success', 'Bloger yangilandi.');
    }

    public function destroyBlogger(Blogger $blogger): \Illuminate\Http\RedirectResponse
    {
        $blogger->delete();

        return back()->with('success', "Bloger o'chirildi.");
    }

    public function storeBloggerShipment(Request $request, Blogger $blogger): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'scheduled_for' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items_text' => ['required', 'string'],
        ]);
        $items = $this->shipmentItems((string) $data['items_text']);
        if ($items === []) {
            return back()->with('error', 'Kamida bitta item kiriting.');
        }

        $shipment = $blogger->shipments()->create([
            'scheduled_for' => $data['scheduled_for'],
            'status' => BloggerShipment::STATUS_PENDING,
            'note' => $data['note'] ?? null,
        ]);
        $this->syncBloggerShipmentItems($shipment, $items);

        return back()->with('success', "Jo'natma qo'shildi.");
    }

    public function updateBloggerShipment(Request $request, Blogger $blogger, BloggerShipment $shipment): \Illuminate\Http\RedirectResponse
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);
        $data = $request->validate([
            'scheduled_for' => ['required', 'date'],
            'status' => ['required', Rule::in([BloggerShipment::STATUS_PENDING, BloggerShipment::STATUS_DELIVERED])],
            'note' => ['nullable', 'string', 'max:1000'],
            'items_text' => ['required', 'string'],
        ]);
        $items = $this->shipmentItems((string) $data['items_text']);
        if ($items === []) {
            return back()->with('error', 'Kamida bitta item kiriting.');
        }

        $shipment->update([
            'scheduled_for' => $data['scheduled_for'],
            'status' => $data['status'],
            'delivered_at' => $data['status'] === BloggerShipment::STATUS_DELIVERED ? ($shipment->delivered_at ?: now()) : null,
            'note' => $data['note'] ?? null,
        ]);
        $this->syncBloggerShipmentItems($shipment, $items);

        return back()->with('success', "Jo'natma yangilandi.");
    }

    public function destroyBloggerShipment(Blogger $blogger, BloggerShipment $shipment): \Illuminate\Http\RedirectResponse
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);
        $shipment->delete();

        return back()->with('success', "Jo'natma o'chirildi.");
    }

    public function updateAdModeration(Request $request, SellerAd $ad): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['approve', 'reject'])]]);
        $ad->update(['moderation' => $data['action'] === 'approve' ? 'approved' : 'rejected']);

        return back()->with('success', $data['action'] === 'approve' ? 'Reklama tasdiqlandi.' : 'Reklama rad etildi.');
    }

    public function destroyAd(SellerAd $ad): \Illuminate\Http\RedirectResponse
    {
        $ad->delete();

        return back()->with('success', "Reklama o'chirildi.");
    }

    public function storeMarketNews(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->marketNewsData($request);
        if ($request->hasFile('imgUrl')) {
            $data['imgUrl'] = $request->file('imgUrl')->store('blog', 'public');
        }
        MarketNews::create($data);

        return back()->with('success', 'Market yangiligi yaratildi.');
    }

    public function updateMarketNews(Request $request, MarketNews $news): \Illuminate\Http\RedirectResponse
    {
        $data = $this->marketNewsData($request);
        if ($request->hasFile('imgUrl')) {
            if ($news->imgUrl) {
                Storage::disk('public')->delete($news->imgUrl);
            }
            $data['imgUrl'] = $request->file('imgUrl')->store('blog', 'public');
        }
        $news->update($data);

        return back()->with('success', 'Market yangiligi yangilandi.');
    }

    public function toggleMarketNews(MarketNews $news): \Illuminate\Http\RedirectResponse
    {
        $news->update(['status' => ! $news->status]);

        return back()->with('success', $news->status ? 'Market yangiligi faollashtirildi.' : 'Market yangiligi yashirildi.');
    }

    public function destroyMarketNews(MarketNews $news): \Illuminate\Http\RedirectResponse
    {
        if ($news->imgUrl) {
            Storage::disk('public')->delete($news->imgUrl);
        }
        $news->delete();

        return back()->with('success', "Market yangiligi o'chirildi.");
    }

    public function translateContent(Request $request): JsonResponse
    {
        $supportedLocales = array_merge(['uz'], self::CONTENT_LOCALES);

        $data = $request->validate([
            'source_locale' => ['required', Rule::in($supportedLocales)],
            'target_locales' => ['required', 'array', 'min:1'],
            'target_locales.*' => ['required', Rule::in(self::CONTENT_LOCALES)],
            'texts' => ['required', 'array'],
            'texts.title' => ['nullable', 'string', 'max:255'],
            'texts.subtitle' => ['nullable', 'string', 'max:255'],
            'texts.description' => ['nullable', 'string', 'max:12000'],
        ]);

        $texts = collect($data['texts'])
            ->map(fn ($value) => filled($value) ? trim((string) $value) : null)
            ->filter(fn ($value) => filled($value))
            ->all();

        if ($texts === []) {
            throw ValidationException::withMessages([
                'texts' => "Tarjima uchun kamida bitta UZ matn kiriting.",
            ]);
        }

        $translations = $this->translateTextsWithAi(
            $texts,
            (string) $data['source_locale'],
            array_values(array_unique($data['target_locales'])),
        );

        return response()->json([
            'status' => 'success',
            'data' => $translations,
        ]);
    }

    public function collectionBookSearch(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $type = strtolower((string) $request->input('type', 'book'));
        if (! in_array($type, ['book', 'stationery'], true)) {
            $type = 'book';
        }

        if ($type === 'stationery') {
            $data = $this->searchStationeryForCollection($search);
        } else {
            $data = $this->searchBooksForCollection($search);
        }

        return response()->json([
            'status' => 'success',
            'type' => $type,
            'data' => $data,
        ]);
    }

    private function searchBooksForCollection(string $search): array
    {
        return Books::query()
            ->with('seller:id,shop_name')
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->where('count', '>', 0)
            ->whereHas('seller', fn ($sellerQuery) => $sellerQuery
                ->where('status', 'approved')
                ->where('is_hidden', 0))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('artikul', 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest('updated_at')
            ->limit(20)
            ->get(['id', 'name', 'author', 'artikul', 'price', 'discountPrice', 'count', 'seller_id', 'images'])
            ->map(fn (Books $book) => [
                'id' => $book->id,
                'productType' => 'book',
                'name' => $book->name,
                'author' => $book->author,
                'artikul' => $book->artikul,
                'seller' => $book->seller?->shop_name,
                'price' => (int) (($book->discountPrice ?: $book->price) ?? 0),
                'base_price' => (int) ($book->price ?? 0),
                'stock' => (int) ($book->count ?? 0),
                'image' => $this->assetFromStorage(collect($book->images ?? [])->first()),
            ])->values()->all();
    }

    private function searchStationeryForCollection(string $search): array
    {
        if (! Schema::hasTable('stationeries')) {
            return [];
        }

        return Stationery::query()
            ->with(['seller:id,shop_name', 'variants:id,product_id,stock'])
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->where(function ($query) {
                $query->where('stock', '>', 0)
                    ->orWhereHas('variants', fn ($variantQuery) => $variantQuery->where('stock', '>', 0));
            })
            ->whereHas('seller', fn ($sellerQuery) => $sellerQuery
                ->where('status', 'approved')
                ->where('is_hidden', 0))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('artikul', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest('updated_at')
            ->limit(20)
            ->get(['id', 'name', 'artikul', 'price', 'discount_price', 'stock', 'seller_id', 'images'])
            ->map(function (Stationery $item) {
                $variantStock = (int) $item->variants->sum(fn ($variant) => (int) ($variant->stock ?? 0));
                $stock = max((int) ($item->stock ?? 0), $variantStock);

                return [
                    'id' => $item->id,
                    'productType' => 'stationery',
                    'name' => $item->name,
                    'author' => null,
                    'artikul' => $item->artikul,
                    'seller' => $item->seller?->shop_name,
                    'price' => (int) (($item->discount_price ?: $item->price) ?? 0),
                    'base_price' => (int) ($item->price ?? 0),
                    'stock' => $stock,
                    'image' => $this->assetFromStorage(collect($item->images ?? [])->first()),
                ];
            })->values()->all();
    }

    public function storeCollection(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->collectionData($request);
        $items = $data['items'];
        unset($data['items'], $data['items_json']);

        if ($request->hasFile('hero_image')) {
            $data['hero_image'] = $request->file('hero_image')->store('collections', 'public');
        }

        $collection = CuratedCollection::create($data);
        $this->syncCollectionItems($collection, $items);

        return back()->with('success', "To'plam yaratildi.");
    }

    public function updateCollection(Request $request, CuratedCollection $collection): \Illuminate\Http\RedirectResponse
    {
        $data = $this->collectionData($request, $collection);
        $items = $data['items'];
        unset($data['items'], $data['items_json']);

        if ($request->hasFile('hero_image')) {
            if ($collection->hero_image) {
                Storage::disk('public')->delete($collection->hero_image);
            }
            $data['hero_image'] = $request->file('hero_image')->store('collections', 'public');
        }

        $collection->update($data);
        $this->syncCollectionItems($collection, $items);

        return back()->with('success', "To'plam yangilandi.");
    }

    public function toggleCollection(CuratedCollection $collection): \Illuminate\Http\RedirectResponse
    {
        $collection->update(['is_active' => ! $collection->is_active]);

        return back()->with('success', $collection->is_active ? "To'plam faollashtirildi." : "To'plam yashirildi.");
    }

    public function destroyCollection(CuratedCollection $collection): \Illuminate\Http\RedirectResponse
    {
        if ($collection->hero_image) {
            Storage::disk('public')->delete($collection->hero_image);
        }

        $collection->delete();

        return back()->with('success', "To'plam o'chirildi.");
    }

    public function duplicateCollection(CuratedCollection $collection): \Illuminate\Http\RedirectResponse
    {
        $collection->load('items');

        $baseSlug = Str::slug($collection->slug.'-nusxa');
        $slug = $baseSlug;
        $suffix = 2;
        while (CuratedCollection::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        $copy = $collection->replicate([
            'slug',
            'hero_image',
            'is_active',
            'created_at',
            'updated_at',
        ]);
        $copy->slug = $slug;
        $copy->is_active = false; // Nusxa yashirin holatda yaratiladi.
        $copy->title_uz = trim(($collection->title_uz ?? 'To\'plam').' (nusxa)');
        // Hero rasm faylini ham nusxalaymiz (asl fayl o'chirilsa nusxa buzilmasin).
        if ($collection->hero_image && Storage::disk('public')->exists($collection->hero_image)) {
            $ext = pathinfo($collection->hero_image, PATHINFO_EXTENSION);
            $newPath = 'collections/'.Str::uuid().($ext ? '.'.$ext : '');
            Storage::disk('public')->copy($collection->hero_image, $newPath);
            $copy->hero_image = $newPath;
        }
        $copy->save();

        foreach ($collection->items as $item) {
            $copy->items()->create([
                'product_id' => $item->product_id,
                'product_type' => $item->product_type,
                'quantity' => $item->quantity,
                'sort_order' => $item->sort_order,
            ]);
        }

        return back()->with('success', "To'plamdan nusxa olindi (yashirin holatda). Tahrirlab faollashtiring.");
    }

    public function storeReel(Request $request): \Illuminate\Http\RedirectResponse
    {
        Reel::create($this->reelData($request));

        return back()->with('success', 'Reel yaratildi.');
    }

    public function updateReel(Request $request, Reel $reel): \Illuminate\Http\RedirectResponse
    {
        $reel->update($this->reelData($request));

        return back()->with('success', 'Reel yangilandi.');
    }

    public function destroyReel(Reel $reel): \Illuminate\Http\RedirectResponse
    {
        $reel->load('items');
        foreach ($reel->items as $item) {
            foreach (['video_720p', 'video_480p', 'video_360p'] as $field) {
                if ($item->{$field}) {
                    Storage::disk('public')->delete($item->{$field});
                }
            }
        }
        $reel->delete();

        return back()->with('success', "Reel o'chirildi.");
    }

    public function storePolicy(Request $request): \Illuminate\Http\RedirectResponse
    {
        $policy = Policy::create($this->policyData($request, null));
        $this->syncPolicyTranslations($policy, (array) $request->input('translations', []));

        return back()->with('success', "Siyosat qo'shildi.");
    }

    public function updatePolicy(Request $request, Policy $policy): \Illuminate\Http\RedirectResponse
    {
        $policy->update($this->policyData($request, $policy));
        $this->syncPolicyTranslations($policy, (array) $request->input('translations', []));

        return back()->with('success', 'Siyosat yangilandi.');
    }

    public function togglePolicy(Policy $policy): \Illuminate\Http\RedirectResponse
    {
        $policy->update(['is_active' => ! $policy->is_active]);

        return back()->with('success', $policy->is_active ? 'Siyosat faollashtirildi.' : 'Siyosat yashirildi.');
    }

    public function destroyPolicy(Policy $policy): \Illuminate\Http\RedirectResponse
    {
        $policy->delete();

        return back()->with('success', "Siyosat o'chirildi.");
    }

    public function storePushNotification(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'who' => ['required', Rule::in(['users', 'business', 'courier'])],
            'target_mode' => ['required', Rule::in(['audience', 'individual'])],
            'recipient' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('target_mode') === 'individual')],
        ]);

        $userType = $data['who'] === 'users' ? 'user' : $data['who'];
        $target = null;

        if ($data['target_mode'] === 'individual') {
            $target = $this->resolvePushRecipient($data['who'], (string) $data['recipient']);
            if (! $target) {
                return back()->withErrors([
                    'recipient' => 'Qabul qiluvchi topilmadi. ID, telefon, email yoki username-ni aniq kiriting.',
                ])->withInput();
            }
        }

        $recipientService = app(FcmRecipientService::class);
        $tokens = $target
            ? $recipientService->tokensFor($userType, (int) $target->id)
            : $recipientService->tokensForAudience($userType);

        if (empty($tokens)) {
            return back()->withErrors([
                'recipient' => $target
                    ? 'Ushbu qabul qiluvchining faol push qurilmasi topilmadi.'
                    : 'Tanlangan auditoriyada faol push qurilmasi topilmadi.',
            ])->withInput();
        }

        $who = $target
            ? ($data['who'] === 'users' ? (string) $target->id : "{$data['who']}:{$target->id}")
            : $data['who'];

        $notification = FcmNotifications::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'who' => $who,
            'source' => 'admin',
            'delivery_status' => 'sending',
            'is_read' => false,
        ]);

        $pushRequest = new Request([
            'app_key' => match ($data['who']) {
                'business' => 'business',
                'courier' => 'courier',
                default => 'kitobchi',
            },
            'title' => $notification->name,
            'body' => $notification->description,
            'tokens' => $tokens,
            'data' => [
                'type' => 'general',
                'notification_id' => (string) $notification->id,
                'target_mode' => $data['target_mode'],
            ],
        ]);

        $response = app(\App\Http\Controllers\PushController::class)->sendPush($pushRequest);
        $result = (array) $response->getData(true);

        $notification->update([
            'delivery_status' => ($result['success'] ?? false) ? 'sent' : 'failed',
            'sent_count' => (int) ($result['sent'] ?? 0),
            'failed_count' => (int) ($result['failed'] ?? count($tokens)),
        ]);

        if (! ($result['success'] ?? false)) {
            return back()->withErrors([
                'recipient' => (string) ($result['message'] ?? 'Push yuborilmadi.'),
            ])->withInput();
        }

        $targetLabel = $target ? $this->pushRecipientLabel($data['who'], $target) : count($tokens).' ta qurilma';

        return back()->with('success', "Push {$targetLabel} uchun yuborildi.");
    }

    private function resolvePushRecipient(string $audience, string $identifier): User|Seller|Couriers|null
    {
        $identifier = trim(ltrim($identifier, '#'));
        $digits = preg_replace('/\D+/', '', $identifier) ?? '';
        $phoneCandidates = array_values(array_unique(array_filter([
            $identifier,
            $digits,
            $digits !== '' ? '+'.$digits : null,
        ])));

        if ($audience === 'users') {
            return User::query()
                ->where(function ($query) use ($identifier, $phoneCandidates) {
                    if (ctype_digit($identifier)) {
                        $query->orWhereKey((int) $identifier);
                    }
                    $query->orWhereIn('phone_number', $phoneCandidates)
                        ->orWhere('email', $identifier)
                        ->orWhere('username', $identifier);
                })
                ->first();
        }

        $model = $audience === 'business' ? Seller::query() : Couriers::query();

        return $model
            ->where(function ($query) use ($identifier, $phoneCandidates) {
                if (ctype_digit($identifier)) {
                    $query->orWhereKey((int) $identifier);
                }
                $query->orWhereIn('phone_number', $phoneCandidates);
            })
            ->first();
    }

    private function pushRecipientLabel(string $audience, User|Seller|Couriers $recipient): string
    {
        return match ($audience) {
            'business' => trim((string) ($recipient->shop_name ?: "Seller #{$recipient->id}")),
            'courier' => trim((string) ($recipient->first_name.' '.$recipient->last_name)) ?: "Kuryer #{$recipient->id}",
            default => trim((string) ($recipient->name.' '.$recipient->lastname)) ?: "Foydalanuvchi #{$recipient->id}",
        };
    }

    public function destroyPushNotification(FcmNotifications $notification): \Illuminate\Http\RedirectResponse
    {
        $notification->delete();

        return back()->with('success', "Push bildirishnoma o'chirildi.");
    }

    public function storeVacancy(Request $request): \Illuminate\Http\RedirectResponse
    {
        $vacancy = Vacancy::create($this->vacancyData($request));
        $this->syncVacancyTranslations($vacancy, (array) $request->input('translations', []));

        return back()->with('success', "Vakansiya qo'shildi.");
    }

    public function updateVacancy(Request $request, Vacancy $vacancy): \Illuminate\Http\RedirectResponse
    {
        $vacancy->update($this->vacancyData($request));
        $this->syncVacancyTranslations($vacancy, (array) $request->input('translations', []));

        return back()->with('success', 'Vakansiya yangilandi.');
    }

    public function toggleVacancy(Vacancy $vacancy): \Illuminate\Http\RedirectResponse
    {
        $vacancy->update(['is_active' => ! $vacancy->is_active]);

        return back()->with('success', $vacancy->is_active ? 'Vakansiya faollashtirildi.' : 'Vakansiya yashirildi.');
    }

    public function destroyVacancy(Vacancy $vacancy): \Illuminate\Http\RedirectResponse
    {
        $vacancy->delete();

        return back()->with('success', "Vakansiya o'chirildi.");
    }

    public function storePanelAdmin(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['superadmin', 'admin', 'moderator'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        Admin::create($data);

        return back()->with('success', "Admin qo'shildi.");
    }

    public function updatePanelAdmin(Request $request, Admin $admin): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['superadmin', 'admin', 'moderator'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');
        $admin->update($data);

        return back()->with('success', 'Admin yangilandi.');
    }

    public function togglePanelAdmin(Admin $admin): \Illuminate\Http\RedirectResponse
    {
        $admin->update(['is_active' => ! $admin->is_active]);

        return back()->with('success', $admin->is_active ? 'Admin faollashtirildi.' : 'Admin bloklandi.');
    }

    public function destroyPanelAdmin(Admin $admin): \Illuminate\Http\RedirectResponse
    {
        abort_if(Auth::id() === $admin->id, 422, "O'zingizni o'chira olmaysiz.");
        $admin->delete();

        return back()->with('success', "Admin o'chirildi.");
    }

    public function storeApiClient(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->apiClientData($request);
        $credentials = ApiClient::generateCredentials();

        ApiClient::create($data + $credentials);
        $this->clearApiClientCache();

        return back()->with('success', 'API mijoz yaratildi.');
    }

    public function updateApiClient(Request $request, ApiClient $apiClient): \Illuminate\Http\RedirectResponse
    {
        $apiClient->update($this->apiClientData($request, false));
        $this->clearApiClientCache();

        return back()->with('success', 'API mijoz yangilandi.');
    }

    public function toggleApiClient(ApiClient $apiClient): \Illuminate\Http\RedirectResponse
    {
        $apiClient->update(['is_active' => ! $apiClient->is_active]);
        $this->clearApiClientCache();

        return back()->with('success', $apiClient->is_active ? 'API mijoz faollashtirildi.' : 'API mijoz o‘chirildi.');
    }

    public function regenerateApiClient(ApiClient $apiClient): \Illuminate\Http\RedirectResponse
    {
        $apiClient->update(['app_secret' => ApiClient::generateCredentials()['app_secret']]);
        $this->clearApiClientCache();

        return back()->with('success', 'API secret yangilandi.');
    }

    public function destroyApiClient(ApiClient $apiClient): \Illuminate\Http\RedirectResponse
    {
        $apiClient->delete();
        $this->clearApiClientCache();

        return back()->with('success', "API mijoz o'chirildi.");
    }

    public function updateSeller(Request $request, Seller $seller): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'firstname' => ['nullable', 'string', 'max:100'],
            'lastname' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['required', 'string', Rule::unique('sellers', 'phone_number')->ignore($seller->id)],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'activity_types' => ['nullable', 'array'],
            'activity_types.*' => ['string', Rule::in(['Kitob', 'Kanstovar', 'book', 'books', 'stationery', 'stationary', 'kitob', 'kanselyariya', 'Книги', 'Канцелярия'])],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'blocked'])],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'legal_type' => ['nullable', 'string', 'max:50'],
            'inn' => ['nullable', 'string', 'max:20'],
            'passport_series' => ['nullable', 'string', 'max:10'],
            'passport_number' => ['nullable', 'string', 'max:20'],
            'passport_issued_by' => ['nullable', 'string', 'max:150'],
            'passport_issued_at' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:30'],
            'bank_mfo' => ['nullable', 'string', 'max:10'],
            'bank_swift' => ['nullable', 'string', 'max:20'],
            'payment_card' => ['nullable', 'string', 'max:50'],
            'card_holder' => ['nullable', 'string', 'max:100'],
            'legal_address' => ['nullable', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:50'],
            'contract_signed' => ['nullable', 'boolean'],
            'contract_signed_at' => ['nullable', 'date'],
            'contract_expires_at' => ['nullable', 'date'],
            'contract_status' => ['nullable', Rule::in(['none', 'active', 'expiring', 'expired', 'terminated'])],
            'contract_notes' => ['nullable', 'string', 'max:2000'],
            'premium_action' => ['nullable', Rule::in(['keep', 'grant', 'revoke'])],
            'premium_plan' => ['nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $premiumAction = (string) ($data['premium_action'] ?? 'keep');
        $premiumPlan = $data['premium_plan'] ?? null;
        if ($premiumAction === 'grant' && ! $premiumPlan) {
            return back()->withErrors(['premium_plan' => 'Premium berish uchun tarifni tanlang.']);
        }

        unset($data['premium_action'], $data['premium_plan']);
        $data['activity_types'] = $this->normalizeSellerActivityTypes($request->input('activity_types', []));
        $data['contract_signed'] = $request->boolean('contract_signed');
        if (! $request->filled('password')) {
            unset($data['password']);
        }
        if ($request->hasFile('photo')) {
            if ($seller->photo) {
                Storage::disk('public')->delete($seller->photo);
            }
            $data['photo'] = $request->file('photo')->store('seller_photos', 'public');
        }

        $oldExpiry = $seller->contract_expires_at?->format('Y-m-d');
        $newExpiry = ! empty($data['contract_expires_at']) ? Carbon::parse($data['contract_expires_at'])->format('Y-m-d') : null;
        $contractChanged = $oldExpiry !== $newExpiry
            || $seller->contract_number !== ($data['contract_number'] ?? null)
            || $seller->contract_status !== ($data['contract_status'] ?? null)
            || (bool) $seller->contract_signed !== (bool) $data['contract_signed'];

        DB::transaction(function () use ($seller, $data, $premiumAction, $premiumPlan, $contractChanged, $oldExpiry, $newExpiry) {
            $seller->update($data);
            $premiumService = app(SellerPremiumService::class);
            if ($premiumAction === 'grant') {
                $premiumService->grantByAdmin($seller, (string) $premiumPlan);
            } elseif ($premiumAction === 'revoke') {
                $premiumService->revokeByAdmin($seller);
            }
            if ($contractChanged && Schema::hasTable('seller_contract_history')) {
                SellerContractHistory::create([
                    'seller_id' => $seller->id,
                    'action' => 'updated',
                    'contract_number' => $seller->contract_number,
                    'old_expires_at' => $oldExpiry,
                    'new_expires_at' => $newExpiry,
                    'notes' => 'Boshqaruv panelidan yangilandi.',
                    'performed_by' => Auth::id(),
                    'created_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Seller yangilandi.');
    }

    public function updateCourier(Request $request, Couriers $courier): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:25'],
            'last_name' => ['required', 'string', 'max:25'],
            'phone_number' => ['required', 'string', Rule::unique('couriers', 'phone_number')->ignore($courier->id)],
            'region' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['approved', 'pending', 'rejected', 'blocked'])],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'transport_type' => ['nullable', Rule::in(['foot', 'bicycle', 'motorcycle', 'car'])],
            'vehicle_brand' => ['nullable', 'string', 'max:50'],
            'vehicle_model' => ['nullable', 'string', 'max:50'],
            'vehicle_color' => ['nullable', 'string', 'max:30'],
            'vehicle_plate_number' => ['nullable', 'string', 'max:20'],
            'inn' => ['nullable', 'string', 'max:20'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'passport_series' => ['nullable', 'string', 'max:10'],
            'passport_number' => ['nullable', 'string', 'max:20'],
            'passport_issued_by' => ['nullable', 'string', 'max:150'],
            'passport_issued_at' => ['nullable', 'date'],
            'driver_license_number' => ['nullable', 'string', 'max:20'],
            'driver_license_issued_at' => ['nullable', 'date'],
            'driver_license_expires_at' => ['nullable', 'date'],
            'payment_card' => ['nullable', 'string', 'max:50'],
            'card_holder' => ['nullable', 'string', 'max:100'],
            'home_address' => ['nullable', 'string', 'max:255'],
            'verification_status' => ['nullable', Rule::in(['unverified', 'pending', 'verified', 'rejected'])],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if (! $request->filled('password')) {
            unset($data['password']);
        }
        if ($request->hasFile('photo')) {
            if ($courier->photo) {
                Storage::disk('public')->delete($courier->photo);
            }
            $data['photo'] = $request->file('photo')->store('courier_photos', 'public');
        }
        $oldVerification = $courier->verification_status;
        $newVerification = $data['verification_status'] ?? $oldVerification;
        if ($oldVerification !== $newVerification) {
            $data['verified_at'] = $newVerification === 'verified' ? now() : null;
        }
        $courier->update($data);

        return back()->with('success', 'Kuryer yangilandi.');
    }

    public function applyCourierPenalty(Request $request, CourierOrder $courierOrder): \Illuminate\Http\RedirectResponse
    {
        $rules = $this->courierPenaltyRules();
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys($rules))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $courierOrder->courier_id) {
            return back()->withErrors(['penalty' => 'Bu orderga kuryer biriktirilmagan.']);
        }

        $amount = $this->calculateCourierPenaltyAmount($courierOrder, $data['reason']);
        if ($amount <= 0) {
            return back()->withErrors(['penalty' => 'Jarima summasini hisoblab bo‘lmadi. Order yoki payout ma’lumotlarini tekshiring.']);
        }

        DB::transaction(function () use ($courierOrder, $amount, $data, $rules) {
            $lockedCourier = Couriers::query()->lockForUpdate()->findOrFail((int) $courierOrder->courier_id);
            $lockedOrder = CourierOrder::query()->lockForUpdate()->findOrFail($courierOrder->id);
            $rule = $rules[$data['reason']];
            $note = trim((string) ($data['note'] ?? ''));
            $description = "Buyurtma #{$lockedOrder->order_id} bo‘yicha jarima: {$rule['label']}";
            if ($note !== '') {
                $description .= ". Izoh: {$note}";
            }

            $lockedCourier->balance = (int) $lockedCourier->balance - $amount;
            $lockedCourier->save();

            CourierTransaction::query()->create([
                'courier_id' => $lockedCourier->id,
                'card' => '',
                'type' => 'expense',
                'category' => 'penalty',
                'order_id' => $lockedOrder->order_id,
                'courier_order_id' => $lockedOrder->id,
                'courier_task_id' => CourierTask::query()
                    ->where('order_id', $lockedOrder->order_id)
                    ->where('courier_id', $lockedCourier->id)
                    ->latest('id')
                    ->value('id'),
                'amount' => $amount,
                'commissionPercent' => 0,
                'commissionPrice' => 0,
                'netAmount' => $amount,
                'status' => 'approved',
                'description' => $description,
            ]);
        });

        return back()->with('success', number_format($amount, 0, '.', ' ').' so‘m jarima kuryer balansidan yechildi.');
    }

    public function approveCourierTransaction(CourierTransaction $courierTransaction): \Illuminate\Http\RedirectResponse
    {
        if (($courierTransaction->category ?: 'withdrawal') !== 'withdrawal') {
            return back()->with('error', "Bu kuryer tranzaksiyasi qo'lda tasdiqlanmaydi.");
        }

        if ($courierTransaction->status !== 'pending') {
            return back()->with('error', 'Faqat kutilayotgan arizani tasdiqlash mumkin.');
        }

        DB::transaction(function () use ($courierTransaction) {
            $lockedTransaction = CourierTransaction::query()->lockForUpdate()->find($courierTransaction->id);
            if (! $lockedTransaction || $lockedTransaction->status !== 'pending') {
                return;
            }

            $courier = Couriers::query()->lockForUpdate()->find($lockedTransaction->courier_id);
            $lockedTransaction->update(['status' => 'approved']);

            if ($courier) {
                $courier->total_withdrawal = (int) $courier->total_withdrawal + (int) ($lockedTransaction->netAmount ?? 0);
                $courier->save();
            }
        });

        return back()->with('success', "Kuryer to'lov arizasi tasdiqlandi.");
    }

    public function rejectCourierTransaction(CourierTransaction $courierTransaction): \Illuminate\Http\RedirectResponse
    {
        if (($courierTransaction->category ?: 'withdrawal') !== 'withdrawal') {
            return back()->with('error', "Bu kuryer tranzaksiyasi qo'lda rad etilmaydi.");
        }

        if ($courierTransaction->status !== 'pending') {
            return back()->with('error', 'Faqat kutilayotgan arizani rad etish mumkin.');
        }

        DB::transaction(function () use ($courierTransaction) {
            $lockedTransaction = CourierTransaction::query()->lockForUpdate()->find($courierTransaction->id);
            if (! $lockedTransaction || $lockedTransaction->status !== 'pending') {
                return;
            }

            $courier = Couriers::query()->lockForUpdate()->find($lockedTransaction->courier_id);
            $lockedTransaction->update([
                'status' => 'rejected',
                'rejected_desc' => $lockedTransaction->rejected_desc ?: 'Admin tomonidan rad etildi, summa balansga qaytarildi.',
            ]);

            if ($courier) {
                $courier->balance = (int) $courier->balance + (int) ($lockedTransaction->amount ?? 0);
                $courier->save();
            }
        });

        return back()->with('success', "Kuryer to'lov arizasi rad etildi va balansga qaytarildi.");
    }

    public function sellerTransactionReport(SellerTransaction $transaction, PayoutReportService $reportService)
    {
        $report = $reportService->seller($transaction);
        $report['lang'] = request('lang') === 'ru' ? 'ru' : 'uz';
        $pdf = Pdf::loadView('boshqaruv.pdf.payout-report', $report)->setPaper('a4');
        $filename = $report['lang'] === 'ru'
            ? "otchet-prodavtsa-{$transaction->id}.pdf"
            : "sotuvchi-hisobot-{$transaction->id}.pdf";

        return $pdf->download($filename);
    }

    public function courierTransactionReport(CourierTransaction $courierTransaction, PayoutReportService $reportService)
    {
        $report = $reportService->courier($courierTransaction);
        $report['lang'] = request('lang') === 'ru' ? 'ru' : 'uz';
        $pdf = Pdf::loadView('boshqaruv.pdf.payout-report', $report)->setPaper('a4');
        $filename = $report['lang'] === 'ru'
            ? "otchet-kurera-{$courierTransaction->id}.pdf"
            : "kuryer-hisobot-{$courierTransaction->id}.pdf";

        return $pdf->download($filename);
    }

    public function storeExpense(Request $request): \Illuminate\Http\RedirectResponse
    {
        PlatformExpense::create($this->expenseData($request) + ['created_by' => Auth::id()]);

        return back()->with('success', "Chiqim qo'shildi.");
    }

    public function updateExpense(Request $request, PlatformExpense $expense): \Illuminate\Http\RedirectResponse
    {
        $expense->update($this->expenseData($request));

        return back()->with('success', 'Chiqim yangilandi.');
    }

    public function destroyExpense(PlatformExpense $expense): \Illuminate\Http\RedirectResponse
    {
        $expense->delete();

        return back()->with('success', "Chiqim o'chirildi.");
    }

    private function marketNewsData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'title_uz' => ['nullable', 'string', 'max:255'],
            'title_ru' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ja' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'description_uz' => ['nullable', 'string', 'max:4000'],
            'description_ru' => ['nullable', 'string', 'max:4000'],
            'description_en' => ['nullable', 'string', 'max:4000'],
            'description_ja' => ['nullable', 'string', 'max:4000'],
            'align' => ['required', Rule::in(['top', 'center'])],
            'status' => ['nullable', 'boolean'],
            'action' => ['required', Rule::in(MarketNews::allowedActions())],
            'action_id' => ['nullable', 'integer', 'min:1'],
            'imgUrl' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $data = $this->normalizeLocalizedField($data, 'title', required: true);
        $data = $this->normalizeLocalizedField($data, 'description');

        $data['status'] = $request->boolean('status');
        if (in_array($data['action'], [MarketNews::ACTION_NEWS, MarketNews::ACTION_TO_BOTTOMSHEET], true)) {
            $data['action_id'] = null;
        }

        return $data;
    }

    private function normalizeLocalizedField(array $data, string $field, bool $required = false): array
    {
        $legacyValue = isset($data[$field]) ? trim((string) $data[$field]) : null;
        $uzValue = isset($data["{$field}_uz"]) ? trim((string) $data["{$field}_uz"]) : null;
        $resolvedUz = filled($uzValue) ? $uzValue : $legacyValue;

        if ($required && ! filled($resolvedUz)) {
            throw ValidationException::withMessages([
                "{$field}_uz" => $field === 'title'
                    ? 'Uzbekcha sarlavha majburiy.'
                    : 'Uzbekcha matn majburiy.',
            ]);
        }

        $data[$field] = filled($resolvedUz) ? $resolvedUz : null;
        $data["{$field}_uz"] = filled($resolvedUz) ? $resolvedUz : null;

        foreach (self::CONTENT_LOCALES as $locale) {
            $key = "{$field}_{$locale}";
            $value = isset($data[$key]) ? trim((string) $data[$key]) : null;
            $data[$key] = filled($value) ? $value : null;
        }

        return $data;
    }

    private function translateTextsWithAi(array $texts, string $sourceLocale, array $targetLocales): array
    {
        $system = <<<'PROMPT'
Sen Kitobchi boshqaruv paneli uchun professional tarjimonsan.

Vazifa:
- Berilgan matnlarni source_locale tilidan target_locales ro'yxatidagi tillarga tarjima qil.
- Marketplace va e-commerce uslubini saqla.
- Juda erkin ijod qilma, ma'noni aniq saqla.
- Qisqa sarlavhalarni qisqa qoldir.
- Emoji, izoh, markdown yoki qo'shimcha sharh yozma.
- Faqat JSON qaytar.

JSON formati:
{
  "ru": {"title": "...", "subtitle": "...", "description": "..."},
  "en": {"title": "...", "subtitle": "...", "description": "..."},
  "ja": {"title": "...", "subtitle": "...", "description": "..."}
}
PROMPT;

        $result = app(OpenAIService::class)->askJsonWithMessages([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode([
                'source_locale' => $sourceLocale,
                'target_locales' => array_values($targetLocales),
                'texts' => $texts,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        ], 1600, 0.2);

        $translations = [];

        foreach ($targetLocales as $locale) {
            $translated = [];
            $payload = is_array($result[$locale] ?? null) ? $result[$locale] : [];

            foreach (array_keys($texts) as $field) {
                $value = $payload[$field] ?? null;
                $translated[$field] = is_string($value) && filled(trim($value))
                    ? trim($value)
                    : null;
            }

            $translations[$locale] = $translated;
        }

        $hasAtLeastOne = collect($translations)
            ->flatten()
            ->contains(fn ($value) => filled($value));

        if (! $hasAtLeastOne) {
            throw ValidationException::withMessages([
                'texts' => "AI tarjima hozircha javob bermadi. Yana bir bor urinib ko'ring.",
            ]);
        }

        return $translations;
    }

    private function collectionData(Request $request, ?CuratedCollection $collection = null): array
    {
        $colorRule = ['required', 'regex:/^#?[0-9A-Fa-f]{6}$/'];

        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:160', Rule::unique('curated_collections', 'slug')->ignore($collection?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'custom_total_price' => ['nullable', 'integer', 'min:1000', 'max:2000000000'],
            'title_uz' => ['required', 'string', 'max:255'],
            'title_ru' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ja' => ['nullable', 'string', 'max:255'],
            'subtitle_uz' => ['nullable', 'string', 'max:255'],
            'subtitle_ru' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'subtitle_ja' => ['nullable', 'string', 'max:255'],
            'description_uz' => ['nullable', 'string', 'max:12000'],
            'description_ru' => ['nullable', 'string', 'max:12000'],
            'description_en' => ['nullable', 'string', 'max:12000'],
            'description_ja' => ['nullable', 'string', 'max:12000'],
            'gradient_from' => $colorRule,
            'gradient_to' => $colorRule,
            'button_bg_color' => $colorRule,
            'button_text_color' => $colorRule,
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'festive_effect' => ['nullable', 'boolean'],
            'items_json' => ['required', 'string'],
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['title_uz']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['custom_total_price'] = filled($request->input('custom_total_price'))
            ? max(1000, (int) $request->input('custom_total_price'))
            : null;
        // Bayramona effekt — ustun mavjud bo'lsagina saqlaymiz (migratsiya kechiksa 500 bermasin)
        if (Schema::hasColumn('curated_collections', 'festive_effect')) {
            $data['festive_effect'] = $request->boolean('festive_effect', true);
        } else {
            unset($data['festive_effect']);
        }
        $data['gradient_from'] = strtoupper((string) $data['gradient_from']);
        $data['gradient_to'] = strtoupper((string) $data['gradient_to']);
        $data['button_bg_color'] = strtoupper((string) $data['button_bg_color']);
        $data['button_text_color'] = strtoupper((string) $data['button_text_color']);

        $decodedItems = json_decode((string) $request->input('items_json'), true);
        if (! is_array($decodedItems)) {
            throw ValidationException::withMessages([
                'items_json' => "To'plam mahsulotlari noto'g'ri formatda yuborildi.",
            ]);
        }

        $items = collect($decodedItems)
            ->map(function ($item, $index) {
                $productId = (int) data_get($item, 'product_id', 0);
                if ($productId <= 0) {
                    return null;
                }

                $type = strtolower((string) data_get($item, 'product_type', 'book'));
                if (! in_array($type, ['book', 'stationery'], true)) {
                    $type = 'book';
                }

                return [
                    'product_id' => $productId,
                    'product_type' => $type,
                    'quantity' => max(1, (int) data_get($item, 'quantity', 1)),
                    'sort_order' => max(0, (int) data_get($item, 'sort_order', $index)),
                ];
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items_json' => "To'plam uchun kamida bitta mahsulot tanlang.",
            ]);
        }

        // Har bir mahsulot turini o'z jadvalidan tekshiramiz.
        $bookIds = $items->where('product_type', 'book')->pluck('product_id')->unique();
        $stationeryIds = $items->where('product_type', 'stationery')->pluck('product_id')->unique();

        $missing = [];

        if ($bookIds->isNotEmpty()) {
            $existingBookIds = Books::query()->whereIn('id', $bookIds->all())->pluck('id')
                ->map(fn ($id) => (int) $id);
            foreach ($bookIds->diff($existingBookIds)->values()->all() as $id) {
                $missing[] = "kitob #{$id}";
            }
        }

        if ($stationeryIds->isNotEmpty() && Schema::hasTable('stationeries')) {
            $existingStationeryIds = Stationery::query()->whereIn('id', $stationeryIds->all())->pluck('id')
                ->map(fn ($id) => (int) $id);
            foreach ($stationeryIds->diff($existingStationeryIds)->values()->all() as $id) {
                $missing[] = "kanselyariya #{$id}";
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'items_json' => "Ba'zi mahsulotlar topilmadi: ".implode(', ', $missing),
            ]);
        }

        $data['items'] = $items->all();

        return $data;
    }

    private function syncCollectionItems(CuratedCollection $collection, array $items): void
    {
        $collection->items()->delete();

        foreach ($items as $item) {
            $collection->items()->create([
                'product_id' => (int) $item['product_id'],
                'product_type' => in_array(($item['product_type'] ?? 'book'), ['book', 'stationery'], true)
                    ? $item['product_type']
                    : 'book',
                'quantity' => (int) $item['quantity'],
                'sort_order' => (int) $item['sort_order'],
            ]);
        }
    }

    private function vacancyData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(array_keys(Vacancy::iconOptions()))],
            'contract_type' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.contract_type' => ['nullable', 'string', 'max:120'],
            'translations.*.location' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function reelData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'order' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function policyData(Request $request, ?Policy $policy): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('policies', 'slug')->ignore($policy?->id)],
            'content' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_app' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_app'] = $request->boolean('show_in_app');

        return $data;
    }

    private function syncPolicyTranslations(Policy $policy, array $translations): void
    {
        if (! Schema::hasTable('policy_translations')) {
            return;
        }

        foreach (self::CONTENT_LOCALES as $locale) {
            $payload = $translations[$locale] ?? [];
            $title = trim((string) ($payload['title'] ?? ''));
            $content = trim((string) ($payload['content'] ?? ''));

            if ($title === '' && $content === '') {
                $policy->translations()->where('locale', $locale)->delete();

                continue;
            }

            $policy->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $title !== '' ? $title : null,
                    'content' => $content !== '' ? $content : null,
                ]
            );
        }
    }

    private function syncVacancyTranslations(Vacancy $vacancy, array $translations): void
    {
        if (! Schema::hasTable('vacancy_translations')) {
            return;
        }

        foreach (self::CONTENT_LOCALES as $locale) {
            $payload = $translations[$locale] ?? [];
            $title = trim((string) ($payload['title'] ?? ''));
            $contractType = trim((string) ($payload['contract_type'] ?? ''));
            $location = trim((string) ($payload['location'] ?? ''));
            $description = trim((string) ($payload['description'] ?? ''));

            if ($title === '' && $contractType === '' && $location === '' && $description === '') {
                $vacancy->translations()->where('locale', $locale)->delete();

                continue;
            }

            $vacancy->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $title !== '' ? $title : null,
                    'contract_type' => $contractType !== '' ? $contractType : null,
                    'location' => $location !== '' ? $location : null,
                    'description' => $description !== '' ? $description : null,
                ]
            );
        }
    }

    private function apiClientData(Request $request, bool $defaultActive = true): array
    {
        $hasRateLimitPerSecond = Schema::hasTable('api_clients') && Schema::hasColumn('api_clients', 'rate_limit_per_second');
        $hasRateLimitPerMinute = Schema::hasTable('api_clients') && Schema::hasColumn('api_clients', 'rate_limit_per_minute');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'rate_limit_per_second' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:500000'],
        ]);
        $abilities = trim((string) ($data['abilities'] ?? ''));
        $decoded = $abilities !== '' ? json_decode($abilities, true) : null;
        $data['abilities'] = is_array($decoded)
            ? array_values(array_unique(array_filter(array_map('strval', $decoded))))
            : collect(preg_split('/[\s,]+/', $abilities) ?: ['read'])
                ->map(fn ($ability) => strtolower(trim((string) $ability)))
                ->filter()
                ->unique()
                ->values()
                ->all();
        if ($data['abilities'] === []) {
            $data['abilities'] = ['read'];
        }
        $data['is_active'] = $request->boolean('is_active', $defaultActive);
        if ($hasRateLimitPerSecond) {
            $data['rate_limit_per_second'] = $data['rate_limit_per_second'] ?? 8;
        } else {
            unset($data['rate_limit_per_second']);
        }
        if ($hasRateLimitPerMinute) {
            $data['rate_limit_per_minute'] = $data['rate_limit_per_minute'] ?? 240;
        } else {
            unset($data['rate_limit_per_minute']);
        }

        return $data;
    }

    private function expenseData(Request $request): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(array_keys(PlatformExpense::CATEGORIES))],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'spent_at' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'order_id' => ['nullable', 'integer', 'exists:solds,id'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function pagePayload(string $component): array
    {
        return match ($component) {
            'Books' => $this->booksPagePayload(),
            'BookCategories' => ['categories' => $this->bookCategoriesPayload()],
            'stationery-categories' => ['categories' => $this->stationeryCategoriesPayload()],
            'Stationeries' => $this->stationeriesPagePayload(),
            'Authors' => ['authors' => $this->authorsPayload()],
            'Publishers' => ['publishers' => $this->publishersPayload()],
            'Users' => $this->usersPagePayload(),
            'Split' => $this->splitPagePayload(),
            'Orders' => $this->ordersPagePayload(),
            'SellerOrders' => [
                ...$this->sellersPagePayload(),
                'sellerCounts' => $this->sellerStatusCounts(),
                ...$this->sellerOrdersPagePayload(),
                'sellerOrderCounts' => $this->sellerOrderStatusCounts(),
                'sellerOrderStatuses' => AdminOrderStatusSyncService::SELLER_STATUSES,
            ],
            'CourierOrders' => [
                ...$this->couriersPagePayload(),
                'courierCounts' => $this->courierStatusCounts(),
                ...$this->courierOrdersPagePayload(),
                'courierOrderCounts' => $this->courierOrderStatusCounts(),
                'courierOrderStatuses' => AdminOrderStatusSyncService::COURIER_STATUSES,
            ],
            'Hubs' => $this->hubsPagePayload(),
            'Transaksiyalar' => $this->transactionsPagePayload(),
            'CommissionAudit' => $this->commissionAuditPagePayload(),
            'AuditLogs' => $this->auditLogsPagePayload(),
            'SellerAiActions' => $this->sellerAiActionsPagePayload(),
            'Expenses' => $this->expensesPagePayload(),
            'Promokodlar' => ['promocodes' => $this->promocodesPayload()],
            'Reklamalar' => ['ads' => $this->adsPayload()],
            'Blogerlar' => ['bloggers' => $this->bloggersPayload()],
            'GiftSertifikatlar' => ['giftCertificates' => $this->giftCertificatesPayload()],
            'MarketNewsPage' => [
                'news' => $this->marketNewsPayload(),
                'translateUrl' => route('boshqaruv.content.translate'),
            ],
            'CollectionsPage' => [
                ...$this->collectionsPagePayload(),
                'translateUrl' => route('boshqaruv.content.translate'),
            ],
            'ReelsPage' => ['reels' => $this->reelsPayload()],
            'Siyosatlar' => ['policies' => $this->policiesPayload()],
            'PushNotifications' => ['notifications' => $this->pushNotificationsPayload()],
            'SearchHistory' => $this->searchHistoryPagePayload(),
            'Sovgalar' => ['gifts' => $this->giftsPayload()],
            'Tickets' => $this->ticketsPagePayload(),
            'Shikoyatlar' => $this->complaintsPagePayload(),
            'ChatKuzatuv' => $this->conversationsPagePayload(),
            'Vakansiyalar' => ['vacancies' => $this->vacanciesPayload()],
            'KaryeraArizalari' => ['applications' => $this->careerApplicationsPayload()],
            'Adminlar' => ['admins' => $this->adminsPayload()],
            'ApiClients' => [
                'apiClients' => $this->apiClientsPayload(),
                'apiLogs' => $this->apiLogsPayload(),
                'apiClientsMeta' => $this->apiClientsMeta(),
            ],
            'Settings' => ['settings' => $this->settingsPayload()],
            'LogistikaPage' => $this->logisticsPagePayload(),
            'MysteryBoxPage' => ['mysteryBox' => $this->mysteryBoxPayload()],
            'BookClub' => ['bookClubPosts' => $this->bookClubPayload()],
            default => [],
        };
    }

    private function dashboardPayload(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $week = now()->startOfWeek();
        $lastWeek = now()->subWeek()->startOfWeek();
        $month = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $paid = fn () => $this->paidOrdersQuery();
        $period = fn ($start, $end = null) => [
            'revenue' => (float) $paid()->where('created_at', '>=', $start)->when($end, fn ($q) => $q->where('created_at', '<', $end))->sum('amount'),
            'orders' => (int) Sold::query()->where('created_at', '>=', $start)->when($end, fn ($q) => $q->where('created_at', '<', $end))->count(),
            'paidOrders' => (int) $paid()->where('created_at', '>=', $start)->when($end, fn ($q) => $q->where('created_at', '<', $end))->count(),
            'users' => Schema::hasTable('users') ? (int) User::query()->where('created_at', '>=', $start)->when($end, fn ($q) => $q->where('created_at', '<', $end))->count() : 0,
        ];

        $todayStats = $period($today);
        $yesterdayStats = $period($yesterday, $today);
        $weekStats = $period($week);
        $lastWeekStats = $period($lastWeek, $week);
        $monthStats = $period($month);
        $lastMonthStats = $period($lastMonth, $month);

        $totalOrders = $this->tableCount('solds');
        $paidCount = (int) $paid()->count();
        $finance = $this->marketplaceFinancialSnapshot();
        $grossRevenue = $finance['grossRevenue'];
        $mainCounts = [
            'all' => $totalOrders,
            'new' => $this->countStatuses(Sold::query(), ['pending', 'A']),
            'packing' => $this->countStatuses(Sold::query(), ['packing', 'P']),
            'onway' => $this->countStatuses(Sold::query(), ['in_delivery', 'B']),
            'done' => $this->countStatuses(Sold::query(), ['customer_received', 'D']),
            'cancelled' => $this->countStatuses(Sold::query(), ['cancelled', 'returned', 'F', 'R']),
        ];

        $sellerCounts = [
            'all' => $this->tableCount('seller_orders'),
            'payment_pending' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['payment_pending']) : 0,
            'new' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['new']) : 0,
            'accepted' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['accepted']) : 0,
            'handover' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['handed_to_courier']) : 0,
            'cancelled' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['cancelled']) : 0,
        ];

        $courierCounts = [
            'all' => $this->tableCount('courier_orders'),
            'pending' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['pending']) : 0,
            'in_delivery' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['in_delivery']) : 0,
            'delivered' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['delivered']) : 0,
            'customer_received' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['customer_received']) : 0,
            'rejected' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['cancelled', 'returned']) : 0,
        ];

        return [
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'metrics' => [
                'orders' => $totalOrders,
                'paidOrders' => $paidCount,
                'users' => $this->tableCount('users'),
                'premiumUsers' => Schema::hasColumn('users', 'is_premium') ? (int) User::query()->where('is_premium', true)->count() : 0,
                'onlineUsers' => Schema::hasColumn('users', 'last_seen_at') ? (int) User::query()->where('last_seen_at', '>=', now()->subMinutes(5))->count() : 0,
                'books' => $this->tableCount('books'),
                'stationeries' => $this->tableCount('stationeries'),
                'sellers' => $this->tableCount('sellers'),
                'pendingSellers' => Schema::hasTable('sellers') ? (int) Seller::query()->where('status', 'pending')->count() : 0,
                'couriers' => $this->tableCount('couriers'),
                'tickets' => $this->tableCount('bot_tickets'),
                'complaints' => $this->tableCount('reports'),
            ],
            'periods' => [
                'today' => $this->periodCard($todayStats),
                'yesterday' => $this->periodCard($yesterdayStats),
                'week' => $this->periodCard($weekStats),
                'lastWeek' => $this->periodCard($lastWeekStats),
                'month' => $this->periodCard($monthStats),
                'lastMonth' => $this->periodCard($lastMonthStats),
            ],
            'financial' => [
                'grossRevenue' => $grossRevenue,
                'monthRevenue' => $monthStats['revenue'],
                ...$finance,
                'avgOrderValue' => $paidCount > 0 ? round($grossRevenue / $paidCount) : 0,
                'netMargin' => $grossRevenue > 0 ? round($finance['platformProfit'] / $grossRevenue * 100, 1) : 0,
            ],
            'status' => [
                'main' => $mainCounts,
                'seller' => $sellerCounts,
                'courier' => $courierCounts,
            ],
            'business' => $this->dashboardBusinessKpis($totalOrders, $paidCount, $mainCounts),
            'salesByMonth' => $this->dashboardMonthlySales(),
            'hourlySales' => $this->liveHourlyChart($today),
            'categoryShare' => $this->dashboardCategoryShare(),
            'topProducts' => $this->liveTopProducts(),
            'recentOrders' => $this->liveRecentOrders(),
            'paymentSplit' => $this->paymentSplit([]),
            'deliverySplit' => $this->deliverySplit(),
            'regions' => $this->liveRegionStats(),
            'platformAnalysis' => $this->dashboardPlatformAnalysis(),
            'alerts' => $this->liveAlerts($mainCounts, $sellerCounts, $courierCounts),
        ];
    }

    private function dashboardPlatformAnalysis(): array
    {
        if (! Schema::hasTable('connected_devices')) {
            return [];
        }

        $platforms = [
            'android' => ['name' => 'Android', 'icon' => 'bi-android2', 'color' => '#10b981'],
            'ios' => ['name' => 'iOS', 'icon' => 'bi-apple', 'color' => '#111827'],
        ];
        $versionColumn = collect(['app_version', 'version', 'app_build'])
            ->first(fn ($column) => Schema::hasColumn('connected_devices', $column));
        $sessionColumn = Schema::hasColumn('users', 'total_seconds_spend') ? 'total_seconds_spend' : null;
        $latestDevices = DB::table('connected_devices as cd')
            ->join(DB::raw('(select user_id, max(updated_at) as latest_at from connected_devices where user_type = "user" and user_id is not null group by user_id) as latest_devices'), function ($join) {
                $join->on('cd.user_id', '=', 'latest_devices.user_id')
                    ->on('cd.updated_at', '=', 'latest_devices.latest_at');
            })
            ->where('cd.user_type', 'user')
            ->whereNotNull('cd.user_id')
            ->select(array_values(array_filter([
                'cd.user_id',
                'cd.platform',
                $versionColumn ? 'cd.'.$versionColumn.' as app_version' : null,
            ])))
            ->get()
            ->map(function ($device) {
                return [
                    'user_id' => (int) $device->user_id,
                    'platform' => $this->canonicalAppPlatform($device->platform ?? null),
                    'version' => isset($device->app_version) ? trim((string) $device->app_version) : null,
                ];
            })
            ->filter(fn ($device) => in_array($device['platform'], ['android', 'ios'], true))
            ->unique('user_id')
            ->values();

        $userPlatform = $latestDevices->pluck('platform', 'user_id')->all();
        $stats = collect($platforms)->mapWithKeys(fn ($meta, $key) => [$key => [
            ...$meta,
            'version' => $this->mostCommonAppVersion($latestDevices, $key),
            'activeUsers' => 0,
            'orders' => 0,
            'revenue' => 0.0,
            'buyers' => [],
            'conversion' => 0.0,
            'crashRate' => 0.0,
            'avgSessionSeconds' => 0,
        ]])->all();

        $latestDevices->each(function ($device) use (&$stats) {
            if (isset($stats[$device['platform']])) {
                $stats[$device['platform']]['activeUsersMap'][(int) $device['user_id']] = true;
            }
        });

        $this->paidOrdersQuery()
            ->whereNotNull('user_id')
            ->select(['id', 'user_id', 'amount'])
            ->chunkById(500, function ($orders) use (&$stats, $userPlatform) {
                foreach ($orders as $order) {
                    $platform = $userPlatform[(int) $order->user_id] ?? null;
                    if (! isset($stats[$platform])) {
                        continue;
                    }

                    $stats[$platform]['orders']++;
                    $stats[$platform]['revenue'] += (float) ($order->amount ?? 0);
                    $stats[$platform]['buyers'][(int) $order->user_id] = true;
                }
            });

        if ($sessionColumn) {
            User::query()
                ->whereIn('id', array_keys($userPlatform))
                ->select(['id', $sessionColumn])
                ->chunkById(500, function ($users) use (&$stats, $userPlatform, $sessionColumn) {
                    foreach ($users as $user) {
                        $platform = $userPlatform[(int) $user->id] ?? null;
                        if (! isset($stats[$platform])) {
                            continue;
                        }

                        $stats[$platform]['sessionSum'] = ($stats[$platform]['sessionSum'] ?? 0) + (int) ($user->{$sessionColumn} ?? 0);
                        $stats[$platform]['sessionUsers'] = ($stats[$platform]['sessionUsers'] ?? 0) + 1;
                    }
                });
        }

        return collect($stats)->map(function ($row) {
            $activeUsers = count($row['activeUsersMap'] ?? []);
            $buyers = count($row['buyers'] ?? []);
            $sessionUsers = (int) ($row['sessionUsers'] ?? 0);

            return [
                'name' => $row['name'],
                'icon' => $row['icon'],
                'color' => $row['color'],
                'version' => $row['version'],
                'activeUsers' => $activeUsers,
                'orders' => (int) $row['orders'],
                'revenue' => (float) $row['revenue'],
                'conversion' => $activeUsers > 0 ? round($buyers / $activeUsers * 100, 1) : 0.0,
                'crashRate' => (float) $row['crashRate'],
                'avgSessionSeconds' => $sessionUsers > 0 ? (int) round(($row['sessionSum'] ?? 0) / $sessionUsers) : 0,
            ];
        })->values()->all();
    }

    private function canonicalAppPlatform(mixed $platform): string
    {
        $value = Str::of((string) $platform)->lower()->replace([' ', '-', '_'], '')->value();

        return match (true) {
            str_contains($value, 'android') => 'android',
            str_contains($value, 'ios') || str_contains($value, 'iphone') || str_contains($value, 'ipad') => 'ios',
            default => 'other',
        };
    }

    private function mostCommonAppVersion(\Illuminate\Support\Collection $devices, string $platform): string
    {
        $version = $devices
            ->where('platform', $platform)
            ->pluck('version')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        if (! $version) {
            return 'Noma\'lum';
        }

        return Str::startsWith(Str::lower($version), 'v') ? $version : 'v'.$version;
    }

    private function marketplaceFinancialSnapshot(?Carbon $start = null, ?Carbon $end = null): array
    {
        $between = function ($query, string $column = 'created_at') use ($start, $end) {
            return $query
                ->when($start, fn ($builder) => $builder->where($column, '>=', $start))
                ->when($end, fn ($builder) => $builder->where($column, '<=', $end));
        };
        $paid = fn () => $between($this->paidOrdersQuery());
        $hasCollectionDiscountAmount = Schema::hasColumn('solds', 'collectionDiscountAmount');
        $grossRevenue = (float) $paid()->sum('amount');
        $deliveryIncome = (float) $paid()->sum('deliveryPrice');
        $promoDiscount = (float) $paid()->sum('discountAmount');
        $collectionDiscount = $hasCollectionDiscountAmount ? (float) $paid()->sum('collectionDiscountAmount') : 0.0;
        $cashback = (float) $paid()->sum('cashbackAmount');
        $giftDiscount = Schema::hasColumn('solds', 'giftCertAmount') ? (float) $paid()->sum('giftCertAmount') : 0;

        $commissionIncome = $commissionReversal = $sellerPayout = 0.0;
        if (Schema::hasTable('seller_transactions')) {
            $sellerTransactions = fn () => $between(SellerTransaction::query()->where('status', 'approved'));
            $commissionIncome = (float) $sellerTransactions()
                ->where('type', 'income')
                ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_SALE)
                ->sum('commissionPrice');
            $commissionReversal = (float) $sellerTransactions()
                ->where('type', 'expense')
                ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_REVERSAL)
                ->sum('commissionPrice');
            $sellerPayout = (float) $sellerTransactions()
                ->where('type', 'income')
                ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_SALE)
                ->sum('netAmount');
        }
        $commission = $commissionIncome - $commissionReversal;

        $courierPayout = Schema::hasTable('courier_orders')
            ? (float) $between($this->customerReceivedCourierQuery())
                ->sum(DB::raw('COALESCE(settled_amount, courierPrice, 0)'))
            : 0;

        $manualExpenses = Schema::hasTable('platform_expenses')
            ? (float) $between(PlatformExpense::query(), 'spent_at')->sum('amount')
            : 0;
        $settings = Schema::hasTable('project_settings') ? ProjectSetting::query()->first() : null;
        $providerPercent = (float) ($settings?->payment_provider_percent ?? 0);
        $providerTurnover = 0.0;
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'payment_type')) {
            $successfulTransactionIds = Transaction::query()
                ->selectRaw('MAX(id)')
                ->where('payment_type', 'order')
                ->whereIn('state', [2, '2', 'paid', 'success', 'performed', 'completed'])
                ->whereIn('order_id', $paid()->select('id'))
                ->groupBy('order_id');
            $paidProviderOrderIds = Transaction::query()
                ->whereIn('id', $successfulTransactionIds)
                ->select('order_id');
            $providerTurnover = (float) $paid()->whereIn('id', $paidProviderOrderIds)->sum('amount');
        }
        $providerFee = round($providerTurnover * $providerPercent / 100, 2);
        $contributionBeforeTax = $commission + $deliveryIncome - $promoDiscount - $collectionDiscount - $cashback - $courierPayout - $manualExpenses - $providerFee;
        $taxMode = (string) ($settings?->tax_mode ?? 'fixed');
        $tax = $taxMode === 'profit_percent'
            ? round(max(0, $contributionBeforeTax) * (float) ($settings?->tax_profit_percent ?? 0) / 100, 2)
            : (float) ($settings?->tax_fixed_uzs ?? 0);

        return [
            'grossRevenue' => $grossRevenue,
            'deliveryIncome' => $deliveryIncome,
            'commission' => $commission,
            'commissionIncome' => $commissionIncome,
            'commissionReversal' => $commissionReversal,
            'promoDiscount' => $promoDiscount,
            'collectionDiscount' => $collectionDiscount,
            'cashback' => $cashback,
            'giftDiscount' => $giftDiscount,
            'courierPayout' => $courierPayout,
            'sellerPayout' => $sellerPayout,
            'manualExpenses' => $manualExpenses,
            'providerTurnover' => $providerTurnover,
            'providerPercent' => $providerPercent,
            'providerFee' => $providerFee,
            'taxMode' => $taxMode,
            'tax' => $tax,
            'contributionBeforeTax' => $contributionBeforeTax,
            'platformProfit' => $contributionBeforeTax - $tax,
        ];
    }

    private function periodCard(array $stats): array
    {
        return [
            'revenue' => (float) ($stats['revenue'] ?? 0),
            'orders' => (int) ($stats['orders'] ?? 0),
            'users' => (int) ($stats['users'] ?? 0),
            'aov' => ((int) ($stats['paidOrders'] ?? 0)) > 0 ? round(((float) ($stats['revenue'] ?? 0)) / (int) $stats['paidOrders']) : 0,
        ];
    }

    private function dashboardBusinessKpis(int $totalOrders, int $paidOrders, array $mainCounts): array
    {
        $buyingUsers = (int) $this->paidOrdersQuery()->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $repeatBuyers = (int) $this->paidOrdersQuery()
            ->whereNotNull('user_id')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
        $cardUsers = Schema::hasTable('user_cards') && Schema::hasColumn('user_cards', 'is_verified')
            ? (int) UserCard::query()->where('is_verified', true)->distinct('user_id')->count('user_id')
            : 0;
        $revenue = (float) $this->paidOrdersQuery()->sum('amount');
        $completed = (int) ($mainCounts['done'] ?? 0);
        $cancelled = (int) ($mainCounts['cancelled'] ?? 0);

        return [
            'paidRate' => $totalOrders > 0 ? round($paidOrders / $totalOrders * 100, 1) : 0,
            'completionRate' => $totalOrders > 0 ? round($completed / $totalOrders * 100, 1) : 0,
            'cancellationRate' => $totalOrders > 0 ? round($cancelled / $totalOrders * 100, 1) : 0,
            'repeatBuyerRate' => $buyingUsers > 0 ? round($repeatBuyers / $buyingUsers * 100, 1) : 0,
            'buyingUsers' => $buyingUsers,
            'repeatBuyers' => $repeatBuyers,
            'cardUsers' => $cardUsers,
            'avgOrdersPerBuyer' => $buyingUsers > 0 ? round($paidOrders / $buyingUsers, 2) : 0,
            'avgRevenuePerBuyer' => $buyingUsers > 0 ? round($revenue / $buyingUsers) : 0,
        ];
    }

    private function booksPagePayload(): array
    {
        $search = trim((string) request('books_search', ''));
        $tab = (string) request('books_tab', 'pending');
        $books = Books::query()
            ->with(['authorProfile:id,name', 'category:id,name_uz', 'publisher:id,name', 'seller:id,shop_name,firstname,lastname,phone_number,status,isVerified,is_hidden'])
            ->when($tab === 'pending', fn ($query) => $query->where('is_approved', 0))
            ->when($tab === 'active', fn ($query) => $query->where('is_approved', 1))
            ->when($tab === 'rejected', fn ($query) => $query->where('is_approved', 2))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('id', $search)
                ->orWhere('artikul', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('isbn', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhereHas('authorProfile', fn ($author) => $author->where('name', 'like', "%{$search}%"))
                ->orWhereHas('category', fn ($category) => $category->where('name_uz', 'like', "%{$search}%"))
                ->orWhereHas('seller', fn ($seller) => $seller->where('shop_name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(24, ['*'], 'books_page')
            ->withQueryString();

        return [
            'books' => $books->getCollection()->map(function (Books $book) {
                $images = collect($book->images ?? [])
                    ->filter(fn ($image) => is_string($image) && trim($image) !== '')
                    ->map(fn (string $image) => ProductImageUrls::originalUrl($image))
                    ->filter()
                    ->values();
                $recentOrders = $this->recentProductOrders('book', (int) $book->id);
                $sellerOrders = $this->recentSellerOrdersForProduct('book', (int) $book->id);

                return [
                    'id' => $book->id,
                    'artikul' => $book->artikul,
                    'title' => $book->name,
                    'author' => $book->authorProfile?->name ?: ($book->author ?: 'Noma\'lum'),
                    'translator' => $book->translator,
                    'isbn' => $book->isbn,
                    'category' => $book->category?->name_uz ?: 'Kitob',
                    'publisher' => $book->publisher?->name,
                    'price' => (float) ($book->price ?? 0),
                    'discountPrice' => $book->discountPrice !== null ? (float) $book->discountPrice : null,
                    'discountExpiresAt' => $this->dateTime($book->discountExpiresAt),
                    'stock' => (int) ($book->count ?? 0),
                    'sold' => (int) ($book->totalSales ?? 0),
                    'totalClients' => (int) ($book->totalClients ?? 0),
                    'totalRevenue' => (float) ($book->totalRevenue ?? 0),
                    'totalSalesWeek' => (int) ($book->totalSalesWeek ?? 0),
                    'views' => (int) ($book->views ?? 0),
                    'cover' => $images->first(),
                    'images' => $images->all(),
                    'rawImages' => array_values(is_array($book->images) ? $book->images : (json_decode((string) $book->images, true) ?: [])),
                    'status' => (int) ($book->is_approved ?? 0),
                    'statusLabel' => match ((int) ($book->is_approved ?? 0)) {
                        1 => 'Faol katalogda',
                        2 => 'Rad etilgan',
                        default => 'Moderatsiya kutilmoqda',
                    },
                    'active' => (bool) ($book->status ?? false),
                    'hidden' => (bool) ($book->is_hidden ?? false),
                    'recommended' => (bool) ($book->recommended ?? false),
                    'recommendedExpiresAt' => $this->dateTime($book->recommendedExpiresAt),
                    'categoryId' => $book->category_id,
                    'publisherId' => $book->publisher_id,
                    'sellerId' => $book->seller_id,
                    'seller' => $book->seller ? [
                        'id' => $book->seller->id,
                        'name' => $book->seller->shop_name ?: trim(($book->seller->firstname ?? '').' '.($book->seller->lastname ?? '')),
                        'phone' => $book->seller->phone_number,
                        'status' => $book->seller->status,
                        'verified' => (bool) $book->seller->isVerified,
                        'hidden' => (bool) $book->seller->is_hidden,
                        'url' => route('boshqaruv.sellers'),
                    ] : null,
                    'lang' => $book->lang,
                    'langType' => $book->langType,
                    'coverType' => $book->coverType,
                    'pages' => $book->pages,
                    'year' => $book->year,
                    'description' => $book->description,
                    'mediaCount' => $images->count(),
                    'recentOrders' => $recentOrders,
                    'sellerOrders' => $sellerOrders,
                    'createdAt' => optional($book->created_at)->format('Y-m-d H:i'),
                    'updatedAt' => optional($book->updated_at)->format('Y-m-d H:i'),
                    'showUrl' => route('boshqaruv.books', ['books_search' => $book->id]),
                    'editUrl' => route('boshqaruv.books.update', $book),
                    'moderateUrl' => route('boshqaruv.books.moderate', $book),
                ];
            })
                ->values()
                ->all(),
            'bookPagination' => $this->paginationMeta($books),
            'bookCounts' => [
                'all' => (int) Books::query()->count(),
                'pending' => (int) Books::query()->where('is_approved', 0)->count(),
                'active' => (int) Books::query()->where('is_approved', 1)->count(),
                'rejected' => (int) Books::query()->where('is_approved', 2)->count(),
            ],
            'bookFilters' => ['search' => $search, 'tab' => $tab],
            'bookFormOptions' => [
                'categories' => BookCategories::query()->orderBy('name_uz')->get(['id', 'name_uz'])->map(fn ($category) => ['id' => $category->id, 'name' => $category->name_uz])->values()->all(),
                'publishers' => Publisher::query()->orderBy('name')->get(['id', 'name'])->map(fn ($publisher) => ['id' => $publisher->id, 'name' => $publisher->name])->values()->all(),
                'sellers' => Seller::query()->whereNotNull('shop_name')->orderBy('shop_name')->get(['id', 'shop_name'])->map(fn ($seller) => ['id' => $seller->id, 'name' => $seller->shop_name])->values()->all(),
            ],
        ];
    }

    private function productsPayload(): array
    {
        $items = collect();

        if (Schema::hasTable('books')) {
            Books::query()
                ->with(['category:id,name_uz', 'seller:id,shop_name'])
                ->latest()
                ->get()
                ->each(function (Books $book) use ($items) {
                    $items->push([
                        'id' => 'book-'.$book->id,
                        'rawId' => $book->id,
                        'artikul' => $book->artikul,
                        'title' => $book->name,
                        'type' => 'Kitob',
                        'category' => $book->category?->name_uz ?: 'Kitob',
                        'seller' => $book->seller?->shop_name,
                        'price' => (float) ($book->discountPrice ?? $book->price ?? 0),
                        'stock' => (int) ($book->count ?? 0),
                        'sold' => (int) ($book->totalSales ?? 0),
                        'revenue' => (float) ($book->totalRevenue ?? 0),
                        'status' => $book->status ? 'Active' : 'Inactive',
                        'approved' => (bool) $book->is_approved,
                        'image' => ProductImageUrls::originalUrl(collect($book->images ?? [])->first()),
                        'showUrl' => route('boshqaruv.books', ['books_search' => $book->id]),
                        'editUrl' => route('boshqaruv.books', ['books_search' => $book->id]),
                        'moderateUrl' => route('boshqaruv.books.moderate', $book),
                    ]);
                });
        }

        if (Schema::hasTable('stationeries')) {
            Stationery::query()
                ->with(['category', 'seller:id,shop_name'])
                ->latest()
                ->get()
                ->each(function (Stationery $stationery) use ($items) {
                    $items->push([
                        'id' => 'stationery-'.$stationery->id,
                        'rawId' => $stationery->id,
                        'artikul' => $stationery->artikul,
                        'title' => $stationery->name,
                        'type' => 'Kanselyariya',
                        'category' => $stationery->category?->name_uz ?: $stationery->category?->name_ru ?: $stationery->category?->slug ?: 'Kanselyariya',
                        'seller' => $stationery->seller?->shop_name,
                        'price' => (float) ($stationery->price ?? 0),
                        'stock' => (int) ($stationery->stock ?? 0),
                        'sold' => (int) ($stationery->totalSales ?? 0),
                        'revenue' => (float) ($stationery->totalRevenue ?? 0),
                        'status' => $stationery->status ? 'Active' : 'Inactive',
                        'approved' => (bool) $stationery->is_approved,
                        'image' => $this->assetFromStorage(collect($stationery->images ?? [])->first()),
                        'showUrl' => route('boshqaruv.stationeries', ['stationeries_search' => $stationery->id]),
                        'editUrl' => route('boshqaruv.stationeries', ['stationeries_search' => $stationery->id]),
                        'moderateUrl' => route('boshqaruv.stationery.moderate', $stationery->id),
                    ]);
                });
        }

        if (Schema::hasTable('gifts')) {
            Gifts::query()
                ->with('seller:id,shop_name')
                ->latest()
                ->get()
                ->each(function (Gifts $gift) use ($items) {
                    $items->push([
                        'id' => 'gift-'.$gift->id,
                        'rawId' => $gift->id,
                        'artikul' => $gift->artikul,
                        'title' => $gift->name,
                        'type' => "Sovg'a",
                        'category' => "Sovg'a",
                        'seller' => $gift->seller?->shop_name,
                        'price' => (float) ($gift->priceFrom ?? 0),
                        'stock' => (int) ($gift->stock ?? 0),
                        'sold' => (int) ($gift->totalSales ?? 0),
                        'revenue' => (float) ($gift->totalRevenue ?? 0),
                        'status' => $gift->status ? 'Active' : 'Inactive',
                        'approved' => (bool) $gift->is_approved,
                        'image' => $this->assetFromStorage(collect($gift->images ?? [])->first()),
                        'showUrl' => route('boshqaruv.sovgalar'),
                        'editUrl' => route('boshqaruv.sovgalar'),
                    ]);
                });
        }

        return $items->sortByDesc('rawId')->values()->all();
    }

    private function usersPagePayload(): array
    {
        $tab = (string) request('users_tab', 'all');
        $search = trim((string) request('users_search', ''));
        $query = User::query()
            ->select('users.*')
            ->withCount('cards')
            ->selectSub(
                Sold::query()->selectRaw('count(*)')->whereColumn('solds.user_id', 'users.id'),
                'orders_count'
            )
            ->selectSub(
                Sold::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('solds.user_id', 'users.id')
                    ->where(fn ($query) => $query
                        ->where('payment_status_code', PaymentStatusCode::PAID->value)
                        ->orWhere(fn ($fallback) => $fallback
                            ->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::PAID->legacy())
                        )
                    ),
                'spent_total'
            );

        match ($tab) {
            'online' => $query->where('last_seen_at', '>=', now()->subMinutes(5)),
            'active' => $query->where('isVerified', true),
            'pending' => $query->where(fn ($builder) => $builder->where('isVerified', false)->orWhereNull('isVerified')),
            'premium' => $query->where('is_premium', true),
            'buyers' => $query->whereExists(fn ($builder) => $builder->selectRaw('1')->from('solds')->whereColumn('solds.user_id', 'users.id')),
            'with_cards' => $query->whereExists(fn ($builder) => $builder->selectRaw('1')->from('user_cards')->whereColumn('user_cards.user_id', 'users.id')),
            'no_cards' => $query->whereNotExists(fn ($builder) => $builder->selectRaw('1')->from('user_cards')->whereColumn('user_cards.user_id', 'users.id')),
            'support' => $query->where('isSupport', true),
            'blocked' => $query->where('status', 'blocked')->where(fn ($builder) => $builder->whereNull('blocked_until')->orWhere('blocked_until', '>', now())),
            default => null,
        };

        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('id', $search)
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $users = $query->latest()->paginate(20, ['*'], 'users_page')->withQueryString();

        return [
            'users' => $users->getCollection()->map(function (User $user) {
                $lastSeenAt = $user->last_seen_at ? \Illuminate\Support\Carbon::parse($user->last_seen_at) : null;

                return [
                    'id' => $user->id,
                    'name' => trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: 'Foydalanuvchi',
                    'firstName' => $user->name,
                    'lastName' => $user->lastname,
                    'email' => $user->email ?? '',
                    'phone' => $this->formatPhone($user->phone_number ?? $user->phone ?? ''),
                    'rawPhone' => $user->phone_number ?? $user->phone ?? '',
                    'avatar' => $this->assetFromStorage($user->avatar),
                    'orders' => (int) ($user->orders_count ?? 0),
                    'cards' => (int) ($user->cards_count ?? 0),
                    'spent' => (float) ($user->spent_total ?? 0),
                    'status' => $user->isBlocked() ? 'blocked' : ($user->isVerified ? 'active' : 'pending'),
                    'position' => $user->position ?: 'reader',
                    'staffRole' => $user->staff_role,
                    'verified' => (bool) $user->isVerified,
                    'premium' => (bool) $user->is_premium,
                    'online' => $lastSeenAt?->gte(now()->subMinutes(5)) ?? false,
                    'lastSeenAt' => $lastSeenAt?->format('Y-m-d H:i'),
                    'joined' => optional($user->created_at)->format('Y-m-d'),
                    'joinedLabel' => $this->dateTime($user->created_at),
                    'dataUrl' => route('boshqaruv.users.data', $user),
                    'blockUrl' => route('boshqaruv.users.block', $user),
                    'unblockUrl' => route('boshqaruv.users.unblock', $user),
                    'verifyUrl' => route('boshqaruv.users.verify', $user),
                    'premiumUrl' => route('boshqaruv.users.premium', $user),
                ];
            })
                ->values()
                ->all(),
            'userCounts' => $this->userStatusCounts(),
            'userPagination' => $this->paginationMeta($users),
            'userFilters' => ['tab' => $tab, 'search' => $search],
        ];
    }

    private function userStatusCounts(): array
    {
        $buyers = Sold::query()->whereNotNull('user_id')->distinct()->count('user_id');

        return [
            'all' => (int) User::query()->count(),
            'online' => (int) User::query()->where('last_seen_at', '>=', now()->subMinutes(5))->count(),
            'active' => (int) User::query()->where('isVerified', true)->count(),
            'pending' => (int) User::query()->where(fn ($query) => $query->where('isVerified', false)->orWhereNull('isVerified'))->count(),
            'premium' => (int) User::query()->where('is_premium', true)->count(),
            'buyers' => (int) $buyers,
            'with_cards' => Schema::hasTable('user_cards') ? (int) UserCard::query()->distinct()->count('user_id') : 0,
            'no_cards' => Schema::hasTable('user_cards') ? (int) User::query()->whereNotExists(fn ($query) => $query->selectRaw('1')->from('user_cards')->whereColumn('user_cards.user_id', 'users.id'))->count() : 0,
            'support' => (int) User::query()->where('isSupport', true)->count(),
            'blocked' => (int) User::query()
                ->where('status', 'blocked')
                ->where(fn ($query) => $query->whereNull('blocked_until')->orWhere('blocked_until', '>', now()))
                ->count(),
        ];
    }

    private function userDetailPayload(User $user): array
    {
        $orders = Sold::query()->where('user_id', $user->id);
        $paidOrders = (clone $orders)->where(fn ($query) => $query
            ->where('payment_status_code', PaymentStatusCode::PAID->value)
            ->orWhere(fn ($fallback) => $fallback
                ->whereNull('payment_status_code')
                ->where('paymentStatus', PaymentStatusCode::PAID->legacy())
            )
        );
        $cards = Schema::hasTable('user_cards') ? UserCard::query()->where('user_id', $user->id)->latest()->take(8)->get() : collect();
        $devices = Schema::hasTable('connected_devices') ? ConnectedDevice::query()->where('user_id', $user->id)->where('user_type', 'user')->latest()->take(8)->get() : collect();
        $addresses = Schema::hasTable('locations') ? DB::table('locations')->where('user_id', $user->id)->where('isDeleted', false)->latest()->take(8)->get() : collect();
        $followers = $this->userFollowPayload($user->id, false);
        $following = $this->userFollowPayload($user->id, true);
        $giftCertificates = Schema::hasTable('gift_certificates') ? GiftCertificate::query()
            ->where(fn ($query) => $query->where('buyer_user_id', $user->id)->orWhere('recipient_user_id', $user->id))
            ->latest()->take(8)->get() : collect();
        $subscriptions = Schema::hasTable('mystery_box_subscriptions') ? MysteryBoxSubscription::query()
            ->with(['plan:id,name_uz,months,books_per_month', 'deliveries'])
            ->where('user_id', $user->id)->latest()->take(6)->get() : collect();
        $searchHistory = Schema::hasTable('search_histories') ? SearchHistory::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(12)
            ->get() : collect();
        $splitProfile = Schema::hasTable('split_user_profiles')
            ? app(SplitProfileService::class)->refreshUser($user, true)
            : null;
        $favourites = Schema::hasTable('favourite_products') ? FavouriteProducts::query()
            ->where('user_id', $user->id)
            ->with('variant')
            ->latest()
            ->take(20)
            ->get() : collect();
        $cartItems = Schema::hasTable('my_carts') ? MyCart::query()
            ->where('user_id', $user->id)
            ->with('variant')
            ->latest()
            ->take(20)
            ->get() : collect();
        $cartHasPriceItem = Schema::hasTable('my_carts') && Schema::hasColumn('my_carts', 'priceItem');

        return [
            'profile' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'firstName' => $user->name,
                'lastName' => $user->lastname,
                'avatar' => $this->assetFromStorage($user->avatar),
                'phone' => $this->formatPhone($user->phone_number),
                'rawPhone' => $user->phone_number,
                'email' => $user->email,
                'username' => $user->username,
                'telegramId' => $user->telegram_id,
                'locale' => $user->locale ?: 'uz',
                'position' => $user->position ?: 'reader',
                'staffRole' => $user->staff_role,
                'roleTitle' => $user->role_title,
                'roleEmoji' => $user->role_emoji,
                'bio' => $user->bio,
                'verified' => (bool) $user->isVerified,
                'premium' => (bool) $user->is_premium,
                'premiumUntil' => $this->dateTime($user->premium_until),
                'support' => (bool) $user->isSupport,
                'aiLimit' => (int) ($user->ai_limit ?? 0),
                'lastSeenAt' => $this->dateTime($user->last_seen_at),
                'joined' => optional($user->created_at)->format('Y-m-d H:i'),
                'spentSeconds' => (int) ($user->total_seconds_spend ?? 0),
                'balance' => (float) ($user->real_balance ?? 0),
                'cashback' => (float) ($user->cashback ?? 0),
                'blocked' => $user->isBlocked(),
                'blockLabel' => $user->activeBlockLabel(),
                'blockReason' => $user->block_reason,
            ],
            'stats' => [
                'orders' => (int) (clone $orders)->count(),
                'paidOrders' => (int) (clone $paidOrders)->count(),
                'spent' => (float) (clone $paidOrders)->sum('amount'),
                'cards' => $cards->count(),
                'devices' => $devices->count(),
                'addresses' => $addresses->count(),
                'followers' => $followers->count(),
                'following' => $following->count(),
                'giftCertificates' => $giftCertificates->count(),
                'mysteryBoxes' => $subscriptions->count(),
                'searches' => $searchHistory->sum(fn (SearchHistory $history) => max(1, (int) ($history->search_count ?? 1))),
                'favourites' => $favourites->count(),
                'cartItems' => $cartItems->count(),
                'cartQuantity' => $cartItems->sum(fn (MyCart $item) => max(1, (int) ($item->count_item ?? 1))),
                'cartTotal' => $cartItems->sum(function (MyCart $item) use ($cartHasPriceItem) {
                    $quantity = max(1, (int) ($item->count_item ?? 1));
                    $unitPrice = $cartHasPriceItem && $item->priceItem !== null
                        ? (float) $item->priceItem
                        : $this->userProductPrice((string) $item->product_type, (int) $item->product_id);

                    return $unitPrice * $quantity;
                }),
            ],
            'orders' => (clone $orders)->latest()->take(12)->get()->map(fn (Sold $order) => [
                'id' => $order->id,
                'status' => $order->status_code ?? $order->status,
                'payment' => $order->payment_status_code ?? $order->paymentStatus,
                'delivery' => $order->deliveryType,
                'amount' => (float) ($order->amount ?? 0),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'url' => route('boshqaruv.orders', ['orders_search' => $order->id, 'orders_tab' => 'all']),
            ])->values(),
            'cards' => $cards->map(fn (UserCard $card) => [
                'id' => $card->id,
                'vendor' => $card->vendor ?: $card->processing ?: 'Card',
                'number' => $card->masked_number,
                'name' => $card->card_name,
                'expires' => $card->expire_date,
                'phone' => $this->formatPhone($card->phone_number),
                'verified' => (bool) $card->is_verified,
                'default' => (bool) $card->is_default,
                'temporary' => (bool) $card->is_temporary,
                'destroyUrl' => route('boshqaruv.users.cards.destroy', [$user, $card]),
            ])->values(),
            'devices' => $devices->map(fn (ConnectedDevice $device) => [
                'id' => $device->id,
                'name' => $device->device_name,
                'platform' => $device->platform,
                'deviceId' => $device->device_id,
                'push' => (bool) $device->fcm_token,
                'date' => optional($device->created_at)->format('Y-m-d H:i'),
            ])->values(),
            'addresses' => $addresses->map(fn ($address) => [
                'id' => $address->id,
                'address' => $address->fullAddress,
                'main' => (int) $address->id === (int) $user->mainAddressID,
                'lat' => $address->lat,
                'lon' => $address->lon,
                'mapLinks' => $this->mapLinks($address->lat, $address->lon, $address->fullAddress),
            ])->values(),
            'followers' => $followers,
            'following' => $following,
            'searchHistory' => $searchHistory->map(fn (SearchHistory $history) => [
                'id' => $history->id,
                'text' => $history->text,
                'resultCount' => (int) ($history->result_count ?? 0),
                'resultName' => $history->result_name,
                'resultType' => $history->result_type,
                'searchCount' => (int) ($history->search_count ?? 0),
                'draft' => (bool) $history->is_draft,
                'date' => optional($history->created_at)->format('Y-m-d H:i'),
                'url' => route('boshqaruv.search-history', ['search_history_search' => $history->text ?: $user->phone_number]),
            ])->values(),
            'favourites' => $favourites->map(function (FavouriteProducts $favourite) {
                $product = $this->userProductPayload(
                    (string) $favourite->product_type,
                    (int) $favourite->product_id,
                    $favourite->variant,
                );

                return array_merge($product, [
                    'id' => $favourite->id,
                    'favouriteId' => $favourite->id,
                    'addedAt' => optional($favourite->created_at)->format('Y-m-d H:i'),
                ]);
            })->values(),
            'cartItems' => $cartItems->map(function (MyCart $item) use ($cartHasPriceItem) {
                $quantity = max(1, (int) ($item->count_item ?? 1));
                $product = $this->userProductPayload(
                    (string) $item->product_type,
                    (int) $item->product_id,
                    $item->variant,
                );
                $unitPrice = $cartHasPriceItem && $item->priceItem !== null
                    ? (float) $item->priceItem
                    : (float) $product['price'];

                return array_merge($product, [
                    'id' => $item->id,
                    'cartId' => $item->id,
                    'quantity' => $quantity,
                    'unitPrice' => $unitPrice,
                    'total' => $unitPrice * $quantity,
                    'addedAt' => optional($item->created_at)->format('Y-m-d H:i'),
                ]);
            })->values(),
            'giftCertificates' => $giftCertificates->map(fn (GiftCertificate $certificate) => [
                'id' => $certificate->id,
                'code' => $certificate->code,
                'role' => (int) $certificate->buyer_user_id === (int) $user->id ? 'Sotib olgan' : 'Qabul qilgan',
                'status' => $certificate->status_label,
                'amount' => (int) $certificate->nominal_uzs,
                'expires' => optional($certificate->expires_at)->format('Y-m-d'),
            ])->values(),
            'mysteryBoxes' => $subscriptions->map(fn (MysteryBoxSubscription $subscription) => [
                'id' => $subscription->id,
                'name' => $subscription->plan?->name_uz ?: 'Mystery Box obuna',
                'status' => $subscription->status_label,
                'booksPerMonth' => (int) $subscription->books_per_month,
                'totalMonths' => (int) $subscription->total_months,
                'deliveredMonths' => (int) $subscription->delivered_months,
                'price' => (int) $subscription->price_uzs,
                'progress' => $subscription->progress_pct,
                'nextDeliveryAt' => optional($subscription->next_delivery_at)->format('Y-m-d'),
                'showUrl' => route('admin.mystery-box.show', $subscription),
                'indexUrl' => route('boshqaruv.mystery-box'),
            ])->values(),
            'splitProfile' => $splitProfile ? [
                'eligible' => (bool) $splitProfile['eligible'],
                'confidenceScore' => (float) $splitProfile['confidence_score'],
                'computedLimit' => (int) $splitProfile['computed_limit'],
                'availableLimit' => (int) $splitProfile['available_limit'],
                'activeExposure' => (int) $splitProfile['active_exposure'],
                'reputationScore' => (float) $splitProfile['reputation_score'],
                'verifiedCardAgeDays' => (int) $splitProfile['verified_card_age_days'],
                'successfulCardPayments180d' => (int) $splitProfile['successful_card_payments_180d'],
                'completedOrdersAll' => (int) $splitProfile['completed_orders_all'],
                'codReturnStrikes' => (int) $splitProfile['cod_return_strikes'],
                'reasons' => array_values($splitProfile['eligibility_reasons'] ?? []),
                'lastRefreshedAt' => $splitProfile['last_refreshed_at'] ?? null,
            ] : null,
            'actions' => [
                'blockUrl' => route('boshqaruv.users.block', $user),
                'unblockUrl' => route('boshqaruv.users.unblock', $user),
                'verifyUrl' => route('boshqaruv.users.verify', $user),
                'premiumUrl' => route('boshqaruv.users.premium', $user),
            ],
        ];
    }

    private function userProductPayload(string $type, int $productId, ?StationeryVariant $variant = null): array
    {
        $type = strtolower(trim($type));
        $product = $this->userProductModel($type, $productId);
        $variantImage = $variant?->image_url;

        return [
            'productId' => $productId,
            'productType' => $type,
            'typeLabel' => $this->userProductTypeLabel($type),
            'name' => $product?->name ?? $product?->title ?? 'Mahsulot topilmadi',
            'image' => $variantImage ?: $this->productImageUrl($product),
            'price' => $this->userProductPrice($type, $productId, $product),
            'stock' => $variant ? (int) ($variant->stock ?? 0) : $this->userProductStock($type, $product),
            'seller' => $product?->seller?->shop_name,
            'status' => $product ? ($product->status ? 'Faol' : 'Nofaol') : 'Topilmadi',
            'approved' => $product ? (bool) ($product->is_approved ?? true) : false,
            'variant' => $variant ? [
                'id' => $variant->id,
                'name' => $variant->color_name ?: 'Variant',
                'stock' => (int) ($variant->stock ?? 0),
                'image' => $variantImage,
            ] : null,
            'url' => $this->userProductUrl($type, $productId),
        ];
    }

    private function userProductModel(string $type, int $productId)
    {
        if ($productId <= 0) {
            return null;
        }

        return match ($type) {
            'stationery' => Stationery::query()->with('seller:id,shop_name')->find($productId),
            'gift' => Gifts::query()->with('seller:id,shop_name')->find($productId),
            'book' => Books::query()->with('seller:id,shop_name')->find($productId),
            default => null,
        };
    }

    private function userProductPrice(string $type, int $productId, $product = null): float
    {
        $product ??= $this->userProductModel($type, $productId);

        if (! $product) {
            return 0.0;
        }

        return match ($type) {
            'stationery' => (float) (($product->discount_price ?? 0) > 0 ? $product->discount_price : ($product->price ?? 0)),
            'gift' => (float) ($product->priceFrom ?? $product->price ?? 0),
            default => (float) (($product->discountPrice ?? 0) > 0 ? $product->discountPrice : ($product->price ?? 0)),
        };
    }

    private function userProductStock(string $type, $product): int
    {
        if (! $product) {
            return 0;
        }

        return match ($type) {
            'stationery', 'gift' => (int) ($product->stock ?? 0),
            default => (int) ($product->count ?? 0),
        };
    }

    private function userProductTypeLabel(string $type): string
    {
        return match ($type) {
            'book' => 'Kitob',
            'stationery' => 'Kanselyariya',
            'gift' => "Sovg'a",
            'ebook' => 'Elektron kitob',
            'audiobook' => 'Audiokitob',
            default => $type ?: 'Mahsulot',
        };
    }

    private function userProductUrl(string $type, int $productId): ?string
    {
        if ($productId <= 0) {
            return null;
        }

        return match ($type) {
            'stationery' => route('boshqaruv.stationeries', ['stationeries_search' => $productId, 'stationeries_tab' => 'all']),
            'gift' => route('boshqaruv.sovgalar'),
            'book' => route('boshqaruv.books', ['books_search' => $productId, 'books_tab' => 'all']),
            default => null,
        };
    }

    private function userFollowPayload(int $userId, bool $following): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('user_follows')) {
            return collect();
        }

        $sourceColumn = $following ? 'follower_id' : 'following_id';
        $targetColumn = $following ? 'following_id' : 'follower_id';

        return DB::table('user_follows')
            ->where($sourceColumn, $userId)
            ->join('users', 'users.id', '=', 'user_follows.'.$targetColumn)
            ->select('users.id', 'users.name', 'users.lastname', 'users.avatar', 'users.phone_number')
            ->latest('user_follows.created_at')
            ->take(8)
            ->get()
            ->map(fn ($person) => [
                'id' => $person->id,
                'name' => trim(($person->name ?? '').' '.($person->lastname ?? '')) ?: 'Foydalanuvchi',
                'phone' => $this->formatPhone($person->phone_number),
                'avatar' => $this->assetFromStorage($person->avatar),
            ])
            ->values();
    }

    private function authorDetailPayload(Author $author): array
    {
        $author->loadCount('books');

        return [
            'id' => $author->id,
            'name' => $author->name,
            'image' => $author->display_image_url,
            'rawImage' => $author->image,
            'externalId' => $author->external_id,
            'slug' => $author->slug,
            'sourceUrl' => $author->source_url,
            'booksCount' => (int) ($author->books_count ?? 0),
            'hasMultipleAuthors' => (bool) $author->has_multiple_authors,
            'needsAiPortrait' => (bool) $author->needs_ai_portrait,
            'books' => $this->linkedBooksQuery()
                ->where('author_id', $author->id)
                ->latest('id')
                ->take(50)
                ->get()
                ->map(fn (Books $book) => $this->catalogBookRow($book))
                ->values()
                ->all(),
            'actions' => [
                'updateUrl' => route('boshqaruv.authors.update', $author),
                'destroyUrl' => route('boshqaruv.authors.destroy', $author),
                'generateImagePromptUrl' => route('boshqaruv.authors.generate-image-prompt', $author),
            ],
        ];
    }

    private function publisherDetailPayload(Publisher $publisher): array
    {
        $publisher->loadCount('books');

        return [
            'id' => $publisher->id,
            'name' => $publisher->name,
            'image' => $publisher->image_url,
            'rawImage' => $publisher->image,
            'booksCount' => (int) ($publisher->books_count ?? 0),
            'books' => $this->linkedBooksQuery()
                ->where('publisher_id', $publisher->id)
                ->latest('id')
                ->take(50)
                ->get()
                ->map(fn (Books $book) => $this->catalogBookRow($book))
                ->values()
                ->all(),
            'actions' => [
                'updateUrl' => route('boshqaruv.publishers.update', $publisher),
                'destroyUrl' => route('boshqaruv.publishers.destroy', $publisher),
            ],
        ];
    }

    private function linkedBooksQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Books::query()->with(['category:id,name_uz', 'seller:id,shop_name']);
    }

    private function catalogBookRow(Books $book): array
    {
        return [
            'id' => $book->id,
            'name' => $book->name,
            'author' => $book->author,
            'category' => $book->category?->name_uz,
            'seller' => $book->seller?->shop_name,
            'price' => (float) ($book->discountPrice ?: $book->price ?: 0),
            'stock' => (int) ($book->count ?? 0),
            'sold' => (int) ($book->totalSales ?? 0),
            'status' => $book->status ? 'Faol' : 'Nofaol',
            'approved' => (bool) $book->is_approved,
            'hidden' => (bool) $book->is_hidden,
        ];
    }

    private function validatedAuthorData(Request $request, ?Author $author = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('authors', 'name')->ignore($author?->id)],
            'image' => ['nullable', 'string', 'max:2048'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'remove_image' => ['nullable', 'boolean'],
            'external_id' => ['nullable', 'string', 'max:255', Rule::unique('authors', 'external_id')->ignore($author?->id)],
            'slug' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'string', 'max:2048'],
        ]);
    }

    private function validatedPublisherData(Request $request, ?Publisher $publisher = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('publishers', 'name')->ignore($publisher?->id)],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function categoryData(Request $request, string $table): array
    {
        $validated = $request->validate([
            'name_uz' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_ja' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name_uz' => $validated['name_uz'],
            'name_ru' => $validated['name_ru'],
            'name_en' => $validated['name_en'] ?? $validated['name_uz'],
            'name_ja' => $validated['name_ja'] ?? $validated['name_uz'],
            'slug' => Str::slug($validated['name_uz']),
            'is_active' => $request->boolean('is_active'),
        ];

        if (Schema::hasColumn($table, 'icon')) {
            $data['icon'] = $validated['icon'] ?? null;
        }

        return $data;
    }

    private function validatedPromocodeData(Request $request, ?Promocode $promocode = null): array
    {
        $rules = [
            'type' => ['required', Rule::in(['percent', 'fixed', 'uzs'])],
            'amount' => ['required', 'integer', 'min:1'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'per_user_limit' => ['nullable', 'integer', 'min:0'],
            'usesLimit' => ['nullable', 'integer', 'min:0'],
            'expires_at' => ['required', 'date'],
            'status' => ['required', 'boolean'],
        ];

        if ($promocode) {
            $rules['code'] = ['nullable', 'string', 'max:255'];
        } else {
            $rules['code'] = ['required', 'string', 'max:255', Rule::unique('promocodes', 'code')];
            $rules['expires_at'][] = 'after:now';
        }

        $data = $request->validate($rules);
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['per_user_limit'] = $data['per_user_limit'] ?? 1;
        $data['usesLimit'] = $data['usesLimit'] ?? 0;
        $data['type'] = $data['type'] === 'percent' ? 'percent' : 'uzs';

        return $data;
    }

    private function validatedBloggerData(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'telegram_url' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'active_until' => ['required', 'date'],
        ]);

        $data['instagram_url'] = $this->normalizeSocialLink($data['instagram_url'] ?? null, 'https://instagram.com/');
        $data['telegram_url'] = $this->normalizeSocialLink($data['telegram_url'] ?? null, 'https://t.me/');

        return $data;
    }

    private function normalizeSocialLink(?string $value, string $baseUrl): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($value, '@/');
    }

    private function bloggerDetailPayload(Blogger $blogger): array
    {
        return [
            'id' => $blogger->id,
            'firstName' => $blogger->first_name,
            'lastName' => $blogger->last_name,
            'name' => $blogger->full_name,
            'phone' => $blogger->phone_number,
            'address' => $blogger->address,
            'instagramUrl' => $blogger->instagram_url,
            'telegramUrl' => $blogger->telegram_url,
            'youtubeUrl' => $blogger->youtube_url,
            'tiktokUrl' => $blogger->tiktok_url,
            'activeUntil' => optional($blogger->active_until)->format('Y-m-d\TH:i'),
            'status' => $blogger->status_label,
            'shipments' => $blogger->shipments->map(fn (BloggerShipment $shipment) => [
                'id' => $shipment->id,
                'scheduledFor' => optional($shipment->scheduled_for)->format('Y-m-d\TH:i'),
                'status' => $shipment->status,
                'deliveredAt' => $this->dateTime($shipment->delivered_at),
                'note' => $shipment->note,
                'itemsText' => $shipment->items->pluck('name')->implode("\n"),
                'items' => $shipment->items->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values()->all(),
                'updateUrl' => route('boshqaruv.blogerlar.shipments.update', [$blogger, $shipment]),
                'destroyUrl' => route('boshqaruv.blogerlar.shipments.destroy', [$blogger, $shipment]),
            ])->values()->all(),
            'actions' => [
                'updateUrl' => route('boshqaruv.blogerlar.update', $blogger),
                'destroyUrl' => route('boshqaruv.blogerlar.destroy', $blogger),
                'shipmentStoreUrl' => route('boshqaruv.blogerlar.shipments.store', $blogger),
            ],
        ];
    }

    private function shipmentItems(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    private function syncBloggerShipmentItems(BloggerShipment $shipment, array $items): void
    {
        $shipment->items()->delete();

        foreach ($items as $index => $name) {
            $shipment->items()->create(['name' => $name, 'position' => $index + 1]);
        }
    }

    private function storeCatalogImage(Request $request, string $directory, string $field, ?string $fallback = null): ?string
    {
        if ($request->hasFile($field)) {
            return $request->file($field)->store($directory, 'public');
        }

        $fallback = trim((string) ($fallback ?? ''));

        return $fallback !== '' ? $fallback : null;
    }

    private function parseImagesText(?string $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function syncCatalogImages(Request $request, array|string|null $currentImages, string $fileField, string $textField, string $directory, string $prefix): array
    {
        $currentImages = is_array($currentImages) ? $currentImages : (json_decode((string) $currentImages, true) ?: []);
        $existingImages = $this->parseImagesText($request->input($textField));
        $deletedImages = array_diff($currentImages, $existingImages);
        foreach ($deletedImages as $image) {
            if (is_string($image) && ! str_starts_with($image, 'http')) {
                Storage::disk('public')->delete($image);
                ProductImageVariantGenerator::deleteForPath($image);
            }
        }

        $images = $existingImages;
        if ($request->hasFile($fileField)) {
            foreach ((array) $request->file($fileField) as $index => $image) {
                if (! $image || ! $image->isValid()) {
                    continue;
                }
                $filename = time()."_{$prefix}_{$index}.".$image->getClientOriginalExtension();
                $path = $image->storeAs($directory, $filename, 'public');
                $images[] = $path;
                ProductImageVariantGenerator::generateForPath($path);
            }
        }

        return array_values(array_unique($images));
    }

    private function syncStationeryVariants(Request $request, Stationery $item): void
    {
        $variantIds = (array) $request->input('variant_id', []);
        $variantNames = (array) $request->input('variant_color_name', []);
        $variantStocks = (array) $request->input('variant_stock', []);
        $variantExistingImages = (array) $request->input('variant_image_existing', []);
        $variantFiles = $request->file('variant_image', []);
        $seen = [];

        foreach ($variantNames as $index => $name) {
            $name = trim((string) $name);
            $stock = max(0, (int) ($variantStocks[$index] ?? 0));
            $variantId = (int) ($variantIds[$index] ?? 0);
            $imagePath = trim((string) ($variantExistingImages[$index] ?? ''));
            $file = $variantFiles[$index] ?? null;
            if ($file && $file->isValid()) {
                if ($imagePath !== '' && ! str_starts_with($imagePath, 'http')) {
                    Storage::disk('public')->delete($imagePath);
                    ProductImageVariantGenerator::deleteForPath($imagePath);
                }
                $imagePath = $file->storeAs('stationery/variants', time()."_variant_{$index}.".$file->getClientOriginalExtension(), 'public');
                ProductImageVariantGenerator::generateForPath($imagePath);
            }

            if ($name === '' && $stock === 0 && $imagePath === '') {
                continue;
            }

            $variant = $variantId > 0
                ? StationeryVariant::query()->where('product_id', $item->id)->find($variantId)
                : new StationeryVariant(['product_id' => $item->id]);

            if (! $variant) {
                continue;
            }

            $variant->fill([
                'product_id' => $item->id,
                'color_name' => $name,
                'stock' => $stock,
                'image_path' => $imagePath ?: null,
            ])->save();
            $seen[] = $variant->id;
        }

        StationeryVariant::query()
            ->where('product_id', $item->id)
            ->when($seen !== [], fn ($query) => $query->whereNotIn('id', $seen))
            ->get()
            ->each(function (StationeryVariant $variant) {
                if ($variant->image_path && ! str_starts_with($variant->image_path, 'http')) {
                    Storage::disk('public')->delete($variant->image_path);
                    ProductImageVariantGenerator::deleteForPath($variant->image_path);
                }
                $variant->delete();
            });
    }

    private function deleteStoredFile(?string $path): void
    {
        if (! is_string($path) || trim($path) === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete(ltrim($path, '/'));
    }

    private function authorsPayload(): array
    {
        if (! Schema::hasTable('authors')) {
            return [];
        }

        return Author::query()
            ->withCount('books')
            ->orderBy('name')
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'name' => $author->name,
                'books' => (int) ($author->books_count ?? 0),
                'externalId' => $author->external_id,
                'slug' => $author->slug,
                'sourceUrl' => $author->source_url,
                'bio' => $author->source_url ?: ($author->external_id ? 'External ID: '.$author->external_id : 'Muallif katalogi'),
                'image' => $author->display_image_url,
                'rawImage' => $author->image,
                'hasMultipleAuthors' => (bool) $author->has_multiple_authors,
                'needsAiPortrait' => (bool) $author->needs_ai_portrait,
                'dataUrl' => route('boshqaruv.authors.data', $author),
                'updateUrl' => route('boshqaruv.authors.update', $author),
                'destroyUrl' => route('boshqaruv.authors.destroy', $author),
                'generateImagePromptUrl' => route('boshqaruv.authors.generate-image-prompt', $author),
            ])
            ->values()
            ->all();
    }

    private function publishersPayload(): array
    {
        if (! Schema::hasTable('publishers')) {
            return [];
        }

        return Publisher::query()
            ->withCount('books')
            ->orderBy('name')
            ->get()
            ->map(fn (Publisher $publisher) => [
                'id' => $publisher->id,
                'name' => $publisher->name,
                'books' => (int) ($publisher->books_count ?? 0),
                'image' => $publisher->image_url,
                'rawImage' => $publisher->image,
                'dataUrl' => route('boshqaruv.publishers.data', $publisher),
                'updateUrl' => route('boshqaruv.publishers.update', $publisher),
                'destroyUrl' => route('boshqaruv.publishers.destroy', $publisher),
            ])
            ->values()
            ->all();
    }

    private function bookCategoriesPayload(): array
    {
        if (! Schema::hasTable('book_categories')) {
            return [];
        }

        return BookCategories::query()
            ->withCount('books')
            ->orderBy('name_uz')
            ->get()
            ->map(fn (BookCategories $category) => [
                'id' => $category->id,
                'name' => $category->name_uz ?: $category->name_ru ?: 'Kategoriya',
                'nameUz' => $category->name_uz,
                'nameRu' => $category->name_ru,
                'nameEn' => $category->name_en,
                'nameJa' => $category->name_ja,
                'icon' => Schema::hasColumn('book_categories', 'icon') ? $category->icon : null,
                'slug' => $category->slug,
                'active' => (bool) $category->is_active,
                'itemsCount' => (int) ($category->books_count ?? 0),
                'storeUrl' => route('boshqaruv.book-categories.store'),
                'updateUrl' => route('boshqaruv.book-categories.update', $category),
                'toggleUrl' => route('boshqaruv.book-categories.toggle', $category),
                'destroyUrl' => route('boshqaruv.book-categories.destroy', $category),
            ])
            ->values()
            ->all();
    }

    private function stationeryCategoriesPayload(): array
    {
        if (! Schema::hasTable('stationery_categories')) {
            return [];
        }

        return StationeryCategory::query()
            ->withCount('stationeries')
            ->orderBy('name_uz')
            ->get()
            ->map(fn (StationeryCategory $category) => [
                'id' => $category->id,
                'name' => $category->name_uz ?: $category->name_ru ?: 'Kategoriya',
                'nameUz' => $category->name_uz,
                'nameRu' => $category->name_ru,
                'nameEn' => $category->name_en,
                'nameJa' => $category->name_ja,
                'icon' => Schema::hasColumn('stationery_categories', 'icon') ? $category->icon : null,
                'slug' => $category->slug,
                'active' => (bool) $category->is_active,
                'itemsCount' => (int) ($category->stationeries_count ?? 0),
                'storeUrl' => route('boshqaruv.stationery-categories.store'),
                'updateUrl' => route('boshqaruv.stationery-categories.update', $category),
                'toggleUrl' => route('boshqaruv.stationery-categories.toggle', $category),
                'destroyUrl' => route('boshqaruv.stationery-categories.destroy', $category),
            ])
            ->values()
            ->all();
    }

    private function stationeriesPagePayload(): array
    {
        if (! Schema::hasTable('stationeries')) {
            return ['stationeries' => [], 'stationeryCounts' => [], 'stationeryPagination' => $this->emptyPagination()];
        }

        $tab = (string) request('stationeries_tab', 'pending');
        $search = trim((string) request('stationeries_search', ''));
        $query = Stationery::query()
            ->with(['category:id,name_uz', 'seller:id,shop_name', 'variants'])
            ->when($tab === 'pending', fn ($builder) => $builder->where(fn ($nested) => $nested->whereNull('is_approved')->orWhere('is_approved', 0)))
            ->when($tab === 'active', fn ($builder) => $builder->where('is_approved', 1))
            ->when($tab === 'rejected', fn ($builder) => $builder->where('is_approved', 2))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('id', $search)
                ->orWhere('artikul', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")
                ->orWhereHas('category', fn ($category) => $category->where('name_uz', 'like', "%{$search}%"))
                ->orWhereHas('seller', fn ($seller) => $seller->where('shop_name', 'like', "%{$search}%"))));
        $items = $query->latest()->paginate(24, ['*'], 'stationeries_page')->withQueryString();

        return [
            'stationeries' => $items->getCollection()->map(function (Stationery $item) {
                $images = collect($item->images ?? [])
                    ->filter()
                    ->map(fn (string $image) => ProductImageUrls::originalUrl($image))
                    ->filter()
                    ->values();

                return [
                    'id' => $item->id,
                    'artikul' => $item->artikul,
                    'name' => $item->name,
                    'categoryId' => $item->category_id,
                    'sellerId' => $item->seller_id,
                    'category' => $item->category?->name_uz ?: '—',
                    'seller' => $item->seller?->shop_name,
                    'price' => (float) ($item->price ?? 0),
                    'discountPrice' => $item->discount_price !== null ? (float) $item->discount_price : null,
                    'discountPercent' => (int) $item->discount_percent,
                    'discountExpiresAt' => $this->dateTime($item->discountExpiresAt),
                    'stock' => (int) ($item->stock ?? 0),
                    'variantStock' => (int) $item->variants->sum('stock'),
                    'sold' => (int) ($item->totalSales ?? 0),
                    'clients' => (int) ($item->totalClients ?? 0),
                    'revenue' => (float) ($item->totalRevenue ?? 0),
                    'views' => (int) ($item->views ?? 0),
                    'status' => (int) ($item->is_approved ?? 0),
                    'active' => (bool) ($item->status ?? false),
                    'hidden' => (bool) ($item->is_hidden ?? false),
                    'recommended' => (bool) ($item->recommended ?? false),
                    'icon' => $images->first(),
                    'images' => $images->all(),
                    'rawImages' => array_values(is_array($item->images) ? $item->images : (json_decode((string) $item->images, true) ?: [])),
                    'barcode' => $item->barcode,
                    'material' => $item->material,
                    'description' => $item->description,
                    'recommendedExpiresAt' => $this->dateTime($item->recommendedExpiresAt),
                    'createdAt' => $this->dateTime($item->created_at),
                    'updatedAt' => $this->dateTime($item->updated_at),
                    'variants' => $item->variants->map(fn ($variant) => [
                        'id' => $variant->id,
                        'name' => $variant->color_name ?: $variant->name ?: "Variant #{$variant->id}",
                        'stock' => (int) ($variant->stock ?? 0),
                        'price' => (float) ($variant->price ?? 0),
                        'image' => ProductImageUrls::originalUrl($variant->image_path),
                    ])->values()->all(),
                    'recentOrders' => $this->recentProductOrders('stationery', (int) $item->id),
                    'sellerOrders' => $this->recentSellerOrdersForProduct('stationery', (int) $item->id),
                    'editUrl' => route('boshqaruv.stationery.update', $item->id),
                    'moderateUrl' => route('boshqaruv.stationery.moderate', $item->id),
                ];
            })
                ->values()
                ->all(),
            'stationeryCounts' => [
                'all' => (int) Stationery::query()->count(),
                'pending' => (int) Stationery::query()->where(fn ($builder) => $builder->whereNull('is_approved')->orWhere('is_approved', 0))->count(),
                'active' => (int) Stationery::query()->where('is_approved', 1)->count(),
                'rejected' => (int) Stationery::query()->where('is_approved', 2)->count(),
            ],
            'stationeryPagination' => $this->paginationMeta($items),
            'stationeryFilters' => ['tab' => $tab, 'search' => $search],
            'stationeryFormOptions' => [
                'categories' => StationeryCategory::query()->orderBy('name_uz')->get(['id', 'name_uz'])->map(fn ($category) => ['id' => $category->id, 'name' => $category->name_uz])->values()->all(),
                'sellers' => Seller::query()->whereNotNull('shop_name')->orderBy('shop_name')->get(['id', 'shop_name'])->map(fn ($seller) => ['id' => $seller->id, 'name' => $seller->shop_name])->values()->all(),
            ],
        ];
    }

    private function ordersPagePayload(): array
    {
        $tab = (string) request('orders_tab', 'pending');
        $search = trim((string) request('orders_search', ''));
        $query = Sold::query()->with('user:id,name,lastname,phone_number,email');

        $statuses = match ($tab) {
            'pending' => [OrderStatusCode::PENDING, OrderStatusCode::PACKING],
            'shipped' => [OrderStatusCode::IN_DELIVERY],
            'paid' => [OrderStatusCode::DELIVERED, OrderStatusCode::CUSTOMER_RECEIVED],
            'returned' => [OrderStatusCode::RETURNED],
            'cancelled' => [OrderStatusCode::CANCELLED],
            default => [],
        };

        if ($statuses !== []) {
            $this->applyMainOrderStatuses($query, $statuses);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('id', $search)
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(25, ['*'], 'orders_page')->withQueryString();

        return [
            'orders' => $orders->getCollection()->map(fn (Sold $order) => $this->orderSummaryPayload($order))->values()->all(),
            'orderPagination' => $this->paginationMeta($orders),
            'orderCounts' => [
                'all' => Sold::query()->count(),
                'pending' => $this->mainOrderStatusCount([OrderStatusCode::PENDING, OrderStatusCode::PACKING]),
                'shipped' => $this->mainOrderStatusCount([OrderStatusCode::IN_DELIVERY]),
                'paid' => $this->mainOrderStatusCount([OrderStatusCode::DELIVERED, OrderStatusCode::CUSTOMER_RECEIVED]),
                'returned' => $this->mainOrderStatusCount([OrderStatusCode::RETURNED]),
                'cancelled' => $this->mainOrderStatusCount([OrderStatusCode::CANCELLED]),
            ],
            'orderFilters' => ['tab' => $tab, 'search' => $search],
        ];
    }

    private function applyMainOrderStatuses($query, array $statuses): void
    {
        $values = array_map(fn (OrderStatusCode $status) => $status->value, $statuses);
        $legacy = array_map(fn (OrderStatusCode $status) => $status->legacy(), $statuses);

        $query->where(function ($builder) use ($values, $legacy) {
            $builder->whereIn('status_code', $values)
                ->orWhere(fn ($fallback) => $fallback->whereNull('status_code')->whereIn('status', $legacy));
        });
    }

    private function mainOrderStatusCount(array $statuses): int
    {
        $query = Sold::query();
        $this->applyMainOrderStatuses($query, $statuses);

        return (int) $query->count();
    }

    private function orderSummaryPayload(Sold $order): array
    {
        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        $deliveryType = Sold::normalizeDeliveryTypeValue($order->deliveryType);

        return [
            'id' => '#'.$order->id,
            'rawId' => $order->id,
            'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
            'user' => $order->user ? [
                'name' => trim(($order->user->name ?? '').' '.($order->user->lastname ?? '')),
                'phone' => $order->user->phone_number,
                'email' => $order->user->email,
            ] : null,
            'items' => (int) collect($order->items ?? [])->sum(fn ($item) => (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? 1)),
            'total' => (float) ($order->amount ?? 0),
            'status' => OrderStatusCode::fromLegacy($order->status_code ?? $order->status)->value,
            'statusLabel' => AdminOrderStatusPresenter::mainOrder($order->status_code ?? $order->status),
            'date' => optional($order->created_at)->format('Y-m-d H:i'),
            'payment' => AdminOrderStatusPresenter::payment($paymentStatus->value),
            'paymentStatus' => AdminOrderStatusPresenter::paymentDetail($paymentStatus->value),
            'deliveryType' => match ($deliveryType) {
                'pickup' => "Do'kondan olib ketish",
                'postal' => 'Pochta orqali yuboriladi',
                default => 'Kuryer orqali yetkaziladi',
            },
            'dataUrl' => route('boshqaruv.orders.data', $order),
            'statusUrl' => route('boshqaruv.orders.status', $order),
            'labelUrl' => route('boshqaruv.orders.print.label', $order),
            'receiptUrl' => route('boshqaruv.orders.print.receipt', $order),
        ];
    }

    private function sellersPayload(): array
    {
        if (! Schema::hasTable('sellers')) {
            return [];
        }

        return Seller::query()
            ->withCount(['books', 'stationeries', 'orders', 'premiumSubscriptions'])
            ->with(['locations' => fn ($query) => $query->orderByDesc('is_main')->orderBy('id')->take(12)])
            ->where(fn ($query) => $query->whereNull('parent_id')->orWhere('parent_id', 0))
            ->latest()
            ->take(240)
            ->get()
            ->map(fn (Seller $seller) => $this->sellerPayload($seller))
            ->values()
            ->all();
    }

    private function sellersPagePayload(): array
    {
        if (! Schema::hasTable('sellers')) {
            return ['sellers' => [], 'sellerPagination' => $this->emptyPagination()];
        }

        $tab = (string) request('sellers_tab', 'pending');
        $search = trim((string) request('sellers_search', ''));
        $query = Seller::query()
            ->withCount(['books', 'stationeries', 'orders', 'premiumSubscriptions'])
            ->with(['locations' => fn ($builder) => $builder->orderByDesc('is_main')->orderBy('id')->take(12)])
            ->where(fn ($builder) => $builder->whereNull('parent_id')->orWhere('parent_id', 0))
            ->when($tab !== 'all', fn ($builder) => $builder->where('status', $tab))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('shop_name', 'like', "%{$search}%")
                ->orWhere('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('region', 'like', "%{$search}%")));
        $sellers = $query->latest()->paginate(12, ['*'], 'sellers_page')->withQueryString();

        return [
            'sellers' => $sellers->getCollection()->map(fn (Seller $seller) => $this->sellerPayload($seller))->values()->all(),
            'sellerPagination' => $this->paginationMeta($sellers),
            'sellerFilters' => ['tab' => $tab, 'search' => $search],
        ];
    }

    private function normalizeSellerActivityTypes(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        return collect($items)
            ->map(fn ($item) => mb_strtolower(trim((string) $item)))
            ->map(fn (string $item) => match ($item) {
                'kitob', 'book', 'books', 'книга', 'книги' => 'Kitob',
                'kanstovar', 'stationery', 'stationary', 'kanselyariya', 'канцелярия' => 'Kanstovar',
                default => null,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function sellerActivityTypeLabel(string $type): string
    {
        return match ($type) {
            'Kitob' => 'Kitob',
            'Kanstovar' => 'Kanselyariya',
            default => $type,
        };
    }

    private function sellerLegalTypeLabel(?string $type): string
    {
        return match ($type) {
            'individual' => 'Jismoniy shaxs',
            'entrepreneur' => 'YTT',
            'llc' => 'MChJ',
            'jsc' => 'AJ / OAJ',
            default => 'Tanlanmagan',
        };
    }

    private function sellerTransactionPaymentPurpose(SellerTransaction $transaction, array $report): ?string
    {
        $seller = $transaction->seller;
        $contractNumber = trim((string) ($seller?->contract_number ?? ''));
        if ($contractNumber === '') {
            return null;
        }

        $contractDate = $seller?->contract_signed_at
            ? Carbon::parse($seller->contract_signed_at)->format('d.m.Y')
            : Carbon::parse($transaction->created_at ?? now())->format('d.m.Y');
        $from = $this->formatPaymentPurposeDate($report['period']['from'] ?? null);
        $to = $this->formatPaymentPurposeDate($report['period']['to'] ?? null)
            ?: Carbon::parse($transaction->created_at ?? now())->format('d.m.Y');
        $period = $from ? "{$from} dan {$to} gacha" : "{$to} gacha";
        $invoiceNumber = $this->sellerTransactionInvoiceNumber($transaction);
        $invoiceDate = $this->sellerTransactionInvoiceDate($transaction);
        $invoice = $invoiceNumber ? ", {$invoiceDate} dagi {$invoiceNumber}-sonli hisobvaraq-faktura" : '';

        return "{$contractDate} sanadagi {$contractNumber}-sonli shartnomaga{$invoice} asosan internet ekvayring ({$period})";
    }

    private function formatPaymentPurposeDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private function sellerTransactionInvoiceNumber(SellerTransaction $transaction): ?string
    {
        $contractNumber = trim((string) ($transaction->seller?->contract_number ?? ''));
        if ($contractNumber === '' || ! $transaction->seller_id || ! $transaction->id) {
            return null;
        }

        $ordinal = SellerTransaction::query()
            ->where('seller_id', $transaction->seller_id)
            ->where(fn ($query) => $query->whereNull('category')->orWhere('category', 'withdrawal')->orWhere('category', 'seller_withdrawal'))
            ->where('id', '<=', $transaction->id)
            ->count();

        return $contractNumber.'-'.max(1, (int) $ordinal);
    }

    private function sellerTransactionInvoiceDate(SellerTransaction $transaction): string
    {
        return Carbon::parse($transaction->created_at ?? now())->format('d.m.Y');
    }

    private function sellerPayload(Seller $seller): array
    {
        $karmaSummary = app(\App\Services\SellerKarmaSummaryService::class)->cachedSummary($seller);
        $activityTypes = $this->normalizeSellerActivityTypes($seller->activity_types);
        $sellerIds = Seller::query()
            ->where('id', $seller->id)
            ->orWhere('parent_id', $seller->id)
            ->pluck('id');
        $warningCount = Schema::hasTable('seller_ban_logs') ? SellerBanLog::getWarningCount($seller->id) : 0;
        $transactions = Schema::hasTable('seller_transactions')
            ? SellerTransaction::query()->whereIn('seller_id', $sellerIds)->latest()->take(6)->get()
            : collect();
        $recentOrders = Schema::hasTable('seller_orders')
            ? SellerOrder::query()->with(['client:id,name,lastname,phone_number'])->whereIn('seller_id', $sellerIds)->latest()->take(6)->get()
            : collect();
        $banLogs = Schema::hasTable('seller_ban_logs')
            ? SellerBanLog::query()->where('seller_id', $seller->id)->latest()->take(6)->get()
            : collect();
        $documents = Schema::hasTable('seller_documents')
            ? $seller->documents()->take(20)->get()
            : collect();
        $contractHistory = Schema::hasTable('seller_contract_history')
            ? $seller->contractHistory()->take(20)->get()
            : collect();
        $totalRevenue = Schema::hasTable('seller_transactions')
            ? (float) SellerTransaction::query()
                ->whereIn('seller_id', $sellerIds)
                ->where('status', 'approved')
                ->where('type', 'income')
                ->sum('netAmount')
            : 0;

        return [
            'id' => $seller->id,
            'name' => $seller->shop_name ?: trim(($seller->firstname ?? '').' '.($seller->lastname ?? '')) ?: 'Seller',
            'shopName' => $seller->shop_name,
            'firstName' => $seller->firstname,
            'lastName' => $seller->lastname,
            'ownerName' => trim(($seller->firstname ?? '').' '.($seller->lastname ?? '')) ?: '—',
            'legalName' => $this->sellerLegalTypeLabel($seller->legal_type),
            'phone' => $seller->phone_number,
            'photo' => $this->assetFromStorage($seller->photo),
            'region' => $seller->region,
            'district' => $seller->district ?? null,
            'address' => $seller->address ?? $seller->legal_address,
            'activityTypes' => $activityTypes,
            'activityTypeLabels' => array_map(fn (string $type) => $this->sellerActivityTypeLabel($type), $activityTypes),
            'status' => $seller->status,
            'verified' => (bool) $seller->isVerified,
            'hidden' => (bool) $seller->is_hidden,
            'premium' => (bool) $seller->isPremiumShop,
            'premiumExpiresAt' => optional($seller->isPremiumExpiresAt)->format('Y-m-d'),
            'premiumPlans' => app(SellerPremiumService::class)->plans(),
            'rating' => (float) ($seller->rating ?? 0),
            'ratingReviewsCount' => (int) ($seller->rating_reviews_count ?? 0),
            'reputationScore' => (float) ($seller->reputation_score ?? 0),
            'karma' => (float) ($karmaSummary['karma'] ?? 0),
            'karmaCode' => $karmaSummary['karma_code'] ?? null,
            'karmaLabelUz' => $karmaSummary['karma_label_uz'] ?? null,
            'karmaLabelRu' => $karmaSummary['karma_label_ru'] ?? null,
            'karmaHintUz' => $karmaSummary['karma_hint_uz'] ?? null,
            'karmaHintRu' => $karmaSummary['karma_hint_ru'] ?? null,
            'productScore' => (float) ($karmaSummary['product_score'] ?? 0),
            'responseScore' => (float) ($karmaSummary['response_score'] ?? 0),
            'successScore' => (float) ($karmaSummary['success_score'] ?? 0),
            'catalogHealth' => (float) ($karmaSummary['catalog_health'] ?? 0),
            'balance' => (float) ($seller->balance ?? 0),
            'totalRevenue' => $totalRevenue,
            'commissionRate' => (float) ($seller->commission_percent ?? 0),
            'products' => (int) (($seller->books_count ?? 0) + ($seller->stationeries_count ?? 0)),
            'books' => (int) ($seller->books_count ?? 0),
            'stationeries' => (int) ($seller->stationeries_count ?? 0),
            'orders' => (int) ($seller->orders_count ?? 0),
            'warningCount' => (int) $warningCount,
            'legal' => [
                'type' => $seller->legal_type,
                'typeLabel' => $this->sellerLegalTypeLabel($seller->legal_type),
                'inn' => $seller->inn,
                'passport' => trim(($seller->passport_series ?? '').' '.($seller->passport_number ?? '')) ?: null,
                'passportIssuedBy' => $seller->passport_issued_by,
                'passportIssuedAt' => optional($seller->passport_issued_at)->format('Y-m-d'),
                'legalAddress' => $seller->legal_address,
            ],
            'bank' => [
                'name' => $seller->bank_name,
                'account' => $seller->masked_bank_account,
                'rawAccount' => $seller->bank_account,
                'mfo' => $seller->bank_mfo,
                'swift' => $seller->bank_swift,
                'card' => $seller->masked_card,
                'rawCard' => $seller->payment_card,
                'cardHolder' => $seller->card_holder,
            ],
            'contract' => [
                'number' => $seller->contract_number,
                'signed' => (bool) $seller->contract_signed,
                'signedAt' => optional($seller->contract_signed_at)->format('Y-m-d'),
                'status' => $seller->contract_computed_status,
                'rawStatus' => $seller->contract_status,
                'expiresAt' => optional($seller->contract_expires_at)->format('Y-m-d'),
                'daysRemaining' => $seller->contract_days_remaining,
                'notes' => $seller->contract_notes,
            ],
            'qr' => [
                'url' => $seller->qrUrl(),
                'imageUrl' => $this->qrImageUrl($seller->qrUrl()),
                'token' => $seller->qr_token,
                'rotatedAt' => optional($seller->qr_rotated_at)->format('Y-m-d H:i'),
            ],
            'locations' => $seller->locations->map(fn ($location) => [
                'id' => $location->id,
                'address' => $location->fullAddress,
                'description' => $location->description,
                'main' => (bool) $location->is_main,
                'lat' => $location->lat,
                'lon' => $location->lon,
                'mapLinks' => $this->mapLinks($location->lat, $location->lon, $location->fullAddress),
                'qrUrl' => $location->qr_url,
                'qrImageUrl' => $this->qrImageUrl($location->qr_url),
                'qrToken' => $location->qr_token,
                'rotatedAt' => optional($location->qr_rotated_at)->format('Y-m-d H:i'),
                'rotateUrl' => route('boshqaruv.sellers.locations.qr.rotate', [$seller, $location]),
            ])->values()->all(),
            'documents' => $documents->map(fn ($document) => [
                'id' => $document->id,
                'type' => $document->type,
                'typeLabel' => $document->type_label,
                'name' => $document->original_name,
                'description' => $document->description,
                'size' => (int) ($document->file_size_kb ?? 0),
                'url' => $document->file_url,
                'date' => $this->dateTime($document->created_at),
                'deleteUrl' => route('boshqaruv.sellers.documents.destroy', [$seller, $document]),
            ])->values()->all(),
            'contractHistory' => $contractHistory->map(fn (SellerContractHistory $history) => [
                'id' => $history->id,
                'action' => $history->action_label,
                'number' => $history->contract_number,
                'oldExpiresAt' => optional($history->old_expires_at)->format('Y-m-d'),
                'newExpiresAt' => optional($history->new_expires_at)->format('Y-m-d'),
                'notes' => $history->notes,
                'date' => $this->dateTime($history->created_at),
            ])->values()->all(),
            'recentOrders' => $recentOrders->map(fn (SellerOrder $order) => [
                'id' => $order->id,
                'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: 'Mijoz',
                'phone' => $order->client?->phone_number,
                'amount' => (float) ($order->amount ?? 0),
                'status' => (string) ($order->status_code ?? $order->status ?? ''),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'url' => route('boshqaruv.seller-orders'),
            ])->values()->all(),
            'transactions' => $transactions->map(fn (SellerTransaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => (float) ($transaction->amount ?? 0),
                'commission' => (float) ($transaction->commissionPrice ?? 0),
                'net' => (float) ($transaction->netAmount ?? 0),
                'status' => $transaction->status,
                'date' => optional($transaction->created_at)->format('Y-m-d H:i'),
            ])->values()->all(),
            'banLogs' => $banLogs->map(fn (SellerBanLog $log) => [
                'id' => $log->id,
                'title' => $log->title,
                'message' => $log->message,
                'type' => $log->type,
                'read' => (bool) $log->is_read,
                'date' => optional($log->created_at)->format('Y-m-d H:i'),
            ])->values()->all(),
            'actions' => [
                'approveUrl' => route('boshqaruv.sellers.approve', $seller),
                'rejectUrl' => route('boshqaruv.sellers.reject', $seller),
                'unblockUrl' => route('boshqaruv.sellers.unblock', $seller),
                'warnUrl' => route('boshqaruv.sellers.warn', $seller),
                'resetPasswordUrl' => route('boshqaruv.sellers.reset-password', $seller),
                'updateUrl' => route('boshqaruv.sellers.update', $seller),
                'rotateQrUrl' => route('boshqaruv.sellers.qr.rotate', $seller),
                'extendContractUrl' => route('boshqaruv.sellers.contract.extend', $seller),
                'uploadDocumentUrl' => route('boshqaruv.sellers.documents.store', $seller),
            ],
        ];
    }

    private function sellerStatusCounts(): array
    {
        if (! Schema::hasTable('sellers')) {
            return ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'blocked' => 0, 'all' => 0];
        }

        $base = fn () => Seller::query()->where(fn ($query) => $query->whereNull('parent_id')->orWhere('parent_id', 0));

        return [
            'pending' => (int) $base()->where('status', 'pending')->count(),
            'approved' => (int) $base()->where('status', 'approved')->count(),
            'rejected' => (int) $base()->where('status', 'rejected')->count(),
            'blocked' => (int) $base()->where('status', 'blocked')->count(),
            'all' => (int) $base()->count(),
        ];
    }

    private function sellerOrdersPayload(): array
    {
        if (! Schema::hasTable('seller_orders')) {
            return [];
        }

        return SellerOrder::query()
            ->with(['seller:id,shop_name,firstname,lastname,phone_number,photo', 'client:id,name,lastname,phone_number', 'courier:id,first_name,last_name,phone_number', 'order:id,user_id,amount,status,paymentStatus,deliveryPrice,deliveryType,items,address,created_at'])
            ->latest()
            ->take(240)
            ->get()
            ->map(fn (SellerOrder $order) => $this->sellerOrderPayload($order))
            ->values()
            ->all();
    }

    private function sellerOrdersPagePayload(): array
    {
        if (! Schema::hasTable('seller_orders')) {
            return ['sellerOrders' => [], 'sellerOrderPagination' => $this->emptyPagination()];
        }

        $tab = (string) request('seller_orders_tab', 'all');
        $search = trim((string) request('seller_orders_search', ''));
        $query = SellerOrder::query()
            ->with(['seller:id,shop_name,firstname,lastname,phone_number,photo', 'client:id,name,lastname,phone_number', 'courier:id,first_name,last_name,phone_number', 'order:id,user_id,amount,status,paymentStatus,deliveryPrice,deliveryType,items,address,created_at'])
            ->when($tab !== 'all', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('status_code', $tab)
                ->orWhere(fn ($fallback) => $fallback->whereNull('status_code')->where('status', SellerOrderStatusCode::fromLegacy($tab)->legacy()))))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('id', $search)->orWhere('order_id', $search)
                ->orWhereHas('seller', fn ($seller) => $seller->where('shop_name', 'like', "%{$search}%"))
                ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%"))));
        $orders = $query->latest()->paginate(25, ['*'], 'seller_orders_page')->withQueryString();

        return [
            'sellerOrders' => $orders->getCollection()->map(fn (SellerOrder $order) => $this->sellerOrderPayload($order))->values()->all(),
            'sellerOrderPagination' => $this->paginationMeta($orders),
            'sellerOrderFilters' => ['tab' => $tab, 'search' => $search],
        ];
    }

    private function sellerOrderPayload(SellerOrder $order): array
    {
        $address = collect($order->order?->address ?? $order->address ?? [])->first() ?: [];
        $addressPayload = $this->orderAddressPayload((array) $address);
        $items = collect($order->order?->items ?? [])
            ->filter(fn ($item) => (int) ($item['seller_id'] ?? 0) === (int) $order->seller_id)
            ->values()
            ->map(fn ($item) => [
                'name' => $item['name'] ?? 'Mahsulot',
                'type' => $item['type'] ?? 'book',
                'quantity' => (int) ($item['count_item'] ?? $item['quantity'] ?? 1),
                'price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
                'cover' => $item['cover'] ?? null,
                'author' => $item['author'] ?? null,
            ]);
        $statusCode = (string) ($order->status_code ?? SellerOrderStatusCode::fromLegacy($order->status ?? null)->value);
        $statusMeta = AdminOrderStatusSyncService::SELLER_STATUSES[$statusCode] ?? ['label' => $statusCode, 'badge' => 'badge-muted'];

        return [
            'id' => $order->id,
            'orderId' => $order->order_id,
            'sellerId' => $order->seller_id,
            'seller' => $order->seller?->shop_name ?: 'Seller',
            'sellerOwner' => trim(($order->seller?->firstname ?? '').' '.($order->seller?->lastname ?? '')) ?: 'Sotuvchi',
            'sellerPhone' => $order->seller?->phone_number,
            'sellerPhoto' => $this->assetFromStorage($order->seller?->photo),
            'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: ($address['fullName'] ?? 'Mijoz'),
            'customerPhone' => $order->client?->phone_number ?? ($address['phoneNumber'] ?? null),
            'courier' => trim(($order->courier?->first_name ?? '').' '.($order->courier?->last_name ?? '')) ?: ($order->courierName ?? '—'),
            'courierPhone' => $order->courier?->phone_number,
            'amount' => (float) ($order->amount ?? 0),
            'mainOrderAmount' => (float) ($order->order?->amount ?? 0),
            'deliveryPrice' => (float) ($order->order?->deliveryPrice ?? 0),
            'deliveryType' => $order->delivery_type ?: ($order->order?->deliveryType ?? '—'),
            'status' => $statusCode,
            'statusLabel' => $statusMeta['label'],
            'statusBadge' => $statusMeta['badge'],
            'acceptedAt' => optional($order->accepted_at)->format('Y-m-d H:i'),
            'date' => optional($order->created_at)->format('Y-m-d H:i'),
            'address' => $addressPayload,
            'summary' => [
                'itemsCount' => (int) $items->sum('quantity'),
                'itemsTotal' => (float) $items->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']),
            ],
            'items' => $items->all(),
            'showUrl' => route('boshqaruv.seller-orders'),
            'statusUrl' => route('boshqaruv.seller-orders.status', $order),
        ];
    }

    private function sellerOrderStatusCounts(): array
    {
        if (! Schema::hasTable('seller_orders')) {
            return ['all' => 0];
        }

        $counts = ['all' => (int) SellerOrder::query()->count()];
        foreach (array_keys(AdminOrderStatusSyncService::SELLER_STATUSES) as $status) {
            $counts[$status] = (int) SellerOrder::query()
                ->where(fn ($query) => $query
                    ->where('status_code', $status)
                    ->orWhere(fn ($fallback) => $fallback
                        ->whereNull('status_code')
                        ->where('status', SellerOrderStatusCode::fromLegacy($status)->legacy())
                    )
                )
                ->count();
        }

        return $counts;
    }

    private function couriersPayload(): array
    {
        if (! Schema::hasTable('couriers')) {
            return [];
        }

        return Couriers::query()
            ->withCount('orders')
            ->latest()
            ->take(240)
            ->get()
            ->map(fn (Couriers $courier) => $this->courierPayload($courier))
            ->values()
            ->all();
    }

    private function couriersPagePayload(): array
    {
        if (! Schema::hasTable('couriers')) {
            return ['couriers' => [], 'courierPagination' => $this->emptyPagination()];
        }

        $tab = (string) request('couriers_tab', 'pending');
        $search = trim((string) request('couriers_search', ''));
        $query = Couriers::query()
            ->withCount('orders')
            ->when($tab !== 'all', fn ($builder) => $builder->where('status', $tab))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('region', 'like', "%{$search}%")
                ->orWhere('vehicle_plate_number', 'like', "%{$search}%")));
        $couriers = $query->latest()->paginate(20, ['*'], 'couriers_page')->withQueryString();

        return [
            'couriers' => $couriers->getCollection()->map(fn (Couriers $courier) => $this->courierPayload($courier))->values()->all(),
            'courierPagination' => $this->paginationMeta($couriers),
            'courierFilters' => ['tab' => $tab, 'search' => $search],
        ];
    }

    private function courierPayload(Couriers $courier): array
    {
        $transactions = Schema::hasTable('courier_transactions')
            ? CourierTransaction::query()->where('courier_id', $courier->id)->latest()->take(6)->get()
            : collect();
        $recentOrders = Schema::hasTable('courier_orders')
            ? CourierOrder::query()->with(['user:id,name,lastname,phone_number'])->where('courier_id', $courier->id)->latest()->take(6)->get()
            : collect();
        $banLogs = Schema::hasTable('courier_ban_logs')
            ? CourierBanLog::query()->where('courier_id', $courier->id)->latest()->take(6)->get()
            : collect();
        $documents = Schema::hasTable('courier_documents')
            ? $courier->documents()->take(8)->get()
            : collect();
        $totalEarned = Schema::hasTable('courier_transactions')
            ? (float) CourierTransaction::query()
                ->where('courier_id', $courier->id)
                ->where('status', 'approved')
                ->where(fn ($query) => $query
                    ->where('type', 'income')
                    ->orWhereIn('category', ['order_delivery', 'hub_delivery']))
                ->sum('netAmount')
            : 0;

        return [
            'id' => $courier->id,
            'name' => $courier->full_name ?: 'Kuryer',
            'firstName' => $courier->first_name,
            'lastName' => $courier->last_name,
            'phone' => $courier->phone_number,
            'photo' => $this->assetFromStorage($courier->photo),
            'region' => $courier->region,
            'status' => $courier->status,
            'isOnline' => (bool) ($courier->is_online ?? false),
            'availabilityUpdatedAt' => $this->dateTime($courier->availability_updated_at),
            'verificationStatus' => $courier->verification_status,
            'verificationLabel' => $courier->verification_label,
            'verificationNotes' => $courier->verification_notes,
            'verifiedAt' => $this->dateTime($courier->verified_at),
            'transport' => $courier->transport_type,
            'transportLabel' => $courier->transport_label,
            'vehicle' => trim(($courier->vehicle_brand ?? '').' '.($courier->vehicle_model ?? '')) ?: '—',
            'vehicleBrand' => $courier->vehicle_brand,
            'vehicleModel' => $courier->vehicle_model,
            'vehicleColor' => $courier->vehicle_color,
            'plate' => $courier->vehicle_plate_number,
            'balance' => (float) ($courier->balance ?? 0),
            'reserved' => (float) ($courier->cod_reserved_amount ?? 0),
            'totalWithdrawal' => (float) ($courier->total_withdrawal ?? 0),
            'totalEarned' => $totalEarned,
            'orders' => (int) ($courier->orders_count ?? 0),
            'warningCount' => Schema::hasTable('courier_ban_logs') ? CourierBanLog::getWarningCount($courier->id) : 0,
            'joined' => $this->dateTime($courier->created_at),
            'location' => [
                'lat' => $courier->current_lat,
                'lon' => $courier->current_lon,
                'updatedAt' => $this->dateTime($courier->location_updated_at),
                'mapLinks' => $this->mapLinks($courier->current_lat, $courier->current_lon),
            ],
            'identity' => [
                'inn' => $courier->inn,
                'birthdate' => optional($courier->birthdate)->format('Y-m-d'),
                'passport' => trim(($courier->passport_series ?? '').' '.($courier->passport_number ?? '')) ?: null,
                'passportIssuedBy' => $courier->passport_issued_by,
                'passportIssuedAt' => optional($courier->passport_issued_at)->format('Y-m-d'),
                'license' => $courier->driver_license_number,
                'licenseIssuedAt' => optional($courier->driver_license_issued_at)->format('Y-m-d'),
                'licenseExpiresAt' => optional($courier->driver_license_expires_at)->format('Y-m-d'),
                'licenseDaysRemaining' => $courier->license_days_remaining,
            ],
            'payment' => [
                'card' => $courier->masked_card,
                'rawCard' => $courier->payment_card,
                'cardHolder' => $courier->card_holder,
                'homeAddress' => $courier->home_address,
            ],
            'recentOrders' => $recentOrders->map(fn (CourierOrder $order) => [
                'id' => $order->id,
                'orderId' => $order->order_id,
                'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
                'amount' => (float) ($order->amount ?? 0),
                'status' => (string) $order->status_code,
                'date' => $this->dateTime($order->created_at),
            ])->values()->all(),
            'transactions' => $transactions->map(fn (CourierTransaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => (float) ($transaction->amount ?? 0),
                'commission' => (float) ($transaction->commissionPrice ?? 0),
                'net' => (float) ($transaction->netAmount ?? 0),
                'status' => $transaction->status,
                'description' => $transaction->description,
                'date' => $this->dateTime($transaction->created_at),
            ])->values()->all(),
            'banLogs' => $banLogs->map(fn (CourierBanLog $log) => [
                'id' => $log->id,
                'title' => $log->title,
                'message' => $log->message,
                'type' => $log->type,
                'date' => $this->dateTime($log->created_at),
            ])->values()->all(),
            'documents' => $documents->map(fn ($document) => [
                'id' => $document->id,
                'type' => $document->type,
                'typeLabel' => $document->type_label,
                'name' => $document->original_name,
                'description' => $document->description,
                'size' => (int) ($document->file_size_kb ?? 0),
                'url' => $this->assetFromStorage($document->file_path),
                'date' => $this->dateTime($document->created_at),
                'deleteUrl' => route('boshqaruv.couriers.documents.destroy', [$courier, $document]),
            ])->values()->all(),
            'actions' => [
                'approveUrl' => route('boshqaruv.couriers.approve', $courier),
                'rejectUrl' => route('boshqaruv.couriers.reject', $courier),
                'unblockUrl' => route('boshqaruv.couriers.unblock', $courier),
                'warnUrl' => route('boshqaruv.couriers.warn', $courier),
                'resetPasswordUrl' => route('boshqaruv.couriers.reset-password', $courier),
                'updateUrl' => route('boshqaruv.couriers.update', $courier),
                'uploadDocumentUrl' => route('boshqaruv.couriers.documents.store', $courier),
            ],
        ];
    }

    private function courierStatusCounts(): array
    {
        if (! Schema::hasTable('couriers')) {
            return ['all' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'blocked' => 0];
        }

        return [
            'all' => (int) Couriers::query()->count(),
            'approved' => (int) Couriers::query()->where('status', 'approved')->count(),
            'pending' => (int) Couriers::query()->where('status', 'pending')->count(),
            'rejected' => (int) Couriers::query()->where('status', 'rejected')->count(),
            'blocked' => (int) Couriers::query()->where('status', 'blocked')->count(),
        ];
    }

    private function courierOrdersPayload(): array
    {
        if (! Schema::hasTable('courier_orders')) {
            return [];
        }

        return CourierOrder::query()
            ->with([
                'courier:id,first_name,last_name,phone_number,region,status,photo',
                'user:id,name,lastname,phone_number',
                'order:id,amount,status,paymentStatus,deliveryPrice,deliveryType,address,items,created_at',
            ])
            ->latest()
            ->take(240)
            ->get()
            ->map(fn (CourierOrder $order) => $this->courierOrderPayload($order))
            ->values()
            ->all();
    }

    private function courierOrdersPagePayload(): array
    {
        if (! Schema::hasTable('courier_orders')) {
            return ['courierOrders' => [], 'courierOrderPagination' => $this->emptyPagination()];
        }

        $tab = (string) request('courier_orders_tab', 'all');
        $search = trim((string) request('courier_orders_search', ''));
        $digits = preg_replace('/\D+/', '', $search) ?: $search;
        $query = CourierOrder::query()
            ->with(['courier:id,first_name,last_name,phone_number,region,status,photo', 'user:id,name,lastname,phone_number', 'order:id,amount,status,paymentStatus,deliveryPrice,deliveryType,address,items,created_at'])
            ->when($tab !== 'all', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('status_code', $tab)
                ->orWhere(fn ($fallback) => $fallback->whereNull('status_code')->where('status', CourierOrderStatusCode::fromLegacy($tab)->legacy()))))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('id', $search)->orWhere('order_id', $search)
                ->orWhere('amount', $search)
                ->orWhereHas('courier', fn ($courier) => $courier
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$digits}%"))
                ->orWhereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$digits}%"))));
        $orders = $query->latest()->paginate(25, ['*'], 'courier_orders_page')->withQueryString();

        return [
            'courierOrders' => $orders->getCollection()->map(fn (CourierOrder $order) => $this->courierOrderPayload($order))->values()->all(),
            'courierOrderPagination' => $this->paginationMeta($orders),
            'courierOrderFilters' => ['tab' => $tab, 'search' => $search],
        ];
    }

    private function courierOrderPayload(CourierOrder $order): array
    {
        $address = collect($order->order?->address ?? [])->first() ?: [];
        $addressPayload = $this->orderAddressPayload((array) $address);
        $activeTask = Schema::hasTable('courier_tasks') ? CourierTask::query()
            ->where('order_id', $order->order_id)
            ->when($order->courier_id, fn ($query) => $query->where('courier_id', $order->courier_id))
            ->latest('id')
            ->first() : null;
        $items = collect($order->order?->items ?? [])->map(fn ($item) => [
            'name' => $item['name'] ?? 'Mahsulot',
            'type' => $item['type'] ?? 'book',
            'quantity' => (int) ($item['count_item'] ?? $item['quantity'] ?? 1),
            'price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
            'author' => $item['author'] ?? null,
        ]);
        $statusCode = (string) ($order->status_code ?? CourierOrderStatusCode::fromLegacy($order->status ?? null)->value);
        $statusMeta = AdminOrderStatusSyncService::COURIER_STATUSES[$statusCode] ?? ['label' => $statusCode, 'badge' => 'badge-muted'];

        return [
            'id' => $order->id,
            'orderId' => $order->order_id,
            'courier' => trim(($order->courier?->first_name ?? '').' '.($order->courier?->last_name ?? '')) ?: 'Tayinlanmagan',
            'courierPhone' => $order->courier?->phone_number,
            'courierRegion' => $order->courier?->region,
            'courierStatus' => $order->courier?->status,
            'courierPhoto' => $this->assetFromStorage($order->courier?->photo),
            'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: ($address['fullName'] ?? 'Mijoz'),
            'customerPhone' => $order->user?->phone_number ?? ($address['phoneNumber'] ?? null),
            'amount' => (float) ($order->amount ?? 0),
            'mainOrderAmount' => (float) ($order->order?->amount ?? 0),
            'courierPrice' => (float) ($order->courierPrice ?? 0),
            'bonus' => (float) ($order->courierBonus ?? 0),
            'taskDistanceKm' => (float) ($activeTask?->distance_km ?? 0),
            'taskFeeAmount' => (float) ($activeTask?->fee_amount ?? 0),
            'taskBaseFeeAmount' => (float) ($activeTask?->base_fee_amount ?? 0),
            'taskDistanceFeeAmount' => (float) ($activeTask?->distance_fee_amount ?? 0),
            'taskBonusAmount' => (float) ($activeTask?->bonus_amount ?? 0),
            'taskLeg' => $activeTask?->leg,
            'settledAmount' => (float) ($order->settled_amount ?? 0),
            'settledAt' => $this->dateTime($order->settled_at),
            'pickedUpAt' => $this->dateTime($order->picked_up_at),
            'deliveryPrice' => (float) ($order->order?->deliveryPrice ?? 0),
            'deliveryType' => $order->order?->deliveryType,
            'paymentStatus' => $order->order?->paymentStatus,
            'status' => $statusCode,
            'statusLabel' => $statusMeta['label'],
            'statusBadge' => $statusMeta['badge'],
            'date' => $this->dateTime($order->created_at),
            'address' => $addressPayload,
            'summary' => [
                'itemsCount' => (int) $items->sum('quantity'),
                'itemsTotal' => (float) $items->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']),
            ],
            'penaltyUrl' => route('boshqaruv.courier-orders.penalty', $order),
            'penaltyRules' => $this->courierPenaltySuggestions($order),
            'items' => $items->all(),
            'statusUrl' => route('boshqaruv.courier-orders.status', $order),
        ];
    }

    private function courierPenaltySuggestions(CourierOrder $order): array
    {
        return collect($this->courierPenaltyRules())
            ->map(fn (array $rule, string $key) => [
                'key' => $key,
                'label' => $rule['label'],
                'description' => $rule['description'],
                'amount' => $this->calculateCourierPenaltyAmount($order, $key),
            ])
            ->values()
            ->all();
    }

    private function courierPenaltyRules(): array
    {
        return [
            'late_delivery' => [
                'label' => 'Kechikib yetkazish',
                'description' => 'SLA yoki kelishilgan vaqt buzilganda. Yengil jarima payoutdan hisoblanadi.',
                'base' => 'payout',
                'percent' => 25,
                'min' => 5000,
                'max' => 30000,
            ],
            'rude_behavior' => [
                'label' => 'Qo‘pol muomala',
                'description' => 'Mijozga qo‘pol muomala, aloqa madaniyati buzilganda.',
                'base' => 'payout',
                'percent' => 50,
                'min' => 10000,
                'max' => 75000,
            ],
            'wrong_status_or_qr' => [
                'label' => 'Noto‘g‘ri status yoki QR tartibi',
                'description' => 'QR tasdiqlamasdan topshirish, noto‘g‘ri status bosish yoki jarayonni buzish.',
                'base' => 'payout',
                'percent' => 30,
                'min' => 7000,
                'max' => 50000,
            ],
            'cash_issue' => [
                'label' => 'Naqd pul bo‘yicha muammo',
                'description' => 'COD pulini kechiktirish, noto‘g‘ri qaytim yoki inkassatsiya muammosi.',
                'base' => 'order_amount',
                'percent' => 20,
                'min' => 15000,
                'max' => 200000,
            ],
            'damaged_package' => [
                'label' => 'Paket yoki mahsulot shikastlangan',
                'description' => 'Yetkazish jarayonida qadoq yoki mahsulot shikastlanganida.',
                'base' => 'order_amount',
                'percent' => 10,
                'min' => 10000,
                'max' => 150000,
            ],
            'lost_item' => [
                'label' => 'Mahsulot yo‘qolgan',
                'description' => 'Mahsulot yo‘qolgan yoki mijozga yetib bormagan og‘ir holat.',
                'base' => 'order_amount',
                'percent' => 100,
                'min' => 0,
                'max' => 500000,
            ],
        ];
    }

    private function calculateCourierPenaltyAmount(CourierOrder $order, string $reason): int
    {
        $rule = $this->courierPenaltyRules()[$reason] ?? null;
        if (! $rule) {
            return 0;
        }

        $payout = max(0, (int) ($order->settled_amount ?: ((int) ($order->courierPrice ?? 0) + (int) ($order->courierBonus ?? 0))));
        $orderAmount = max(0, (int) ($order->order?->amount ?? $order->amount ?? 0));
        $base = $rule['base'] === 'order_amount' ? $orderAmount : $payout;
        if ($base <= 0) {
            $base = max($payout, $orderAmount);
        }

        $amount = (int) ceil($base * ((float) $rule['percent'] / 100));
        $amount = max((int) $rule['min'], $amount);
        if (! empty($rule['max'])) {
            $amount = min((int) $rule['max'], $amount);
        }
        if ($rule['base'] === 'order_amount' && $orderAmount > 0) {
            $amount = min($orderAmount, $amount);
        }

        return max(0, $amount);
    }

    private function courierOrderStatusCounts(): array
    {
        if (! Schema::hasTable('courier_orders')) {
            return ['all' => 0];
        }

        $counts = ['all' => (int) CourierOrder::query()->count()];
        foreach (array_keys(AdminOrderStatusSyncService::COURIER_STATUSES) as $status) {
            $counts[$status] = (int) CourierOrder::query()
                ->where(fn ($query) => $query
                    ->where('status_code', $status)
                    ->orWhere(fn ($fallback) => $fallback
                        ->whereNull('status_code')
                        ->where('status', CourierOrderStatusCode::fromLegacy($status)->legacy())
                    )
                )
                ->count();
        }

        return $counts;
    }

    private function hubsPayload(): array
    {
        if (! Schema::hasTable('hubs')) {
            return [];
        }

        return Hub::query()
            ->withCount(['staff', 'fulfillments', 'courierTasks'])
            ->orderBy('priority')
            ->get()
            ->map(fn (Hub $hub) => [
                'id' => $hub->id,
                'name' => $hub->name,
                'code' => $hub->code,
                'country' => $hub->country_code,
                'region' => $hub->region_name,
                'city' => $hub->city_name,
                'address' => $hub->address,
                'active' => (bool) $hub->is_active,
                'primary' => (bool) $hub->is_primary,
                'priority' => (int) $hub->priority,
                'staff' => (int) ($hub->staff_count ?? 0),
                'fulfillments' => (int) ($hub->fulfillments_count ?? 0),
                'courierTasks' => (int) ($hub->courier_tasks_count ?? 0),
                'supportsFirstMile' => (bool) $hub->supports_first_mile,
                'supportsLastMile' => (bool) $hub->supports_last_mile,
                'supportsPostal' => (bool) $hub->supports_postal_dispatch,
                'indexUrl' => route('boshqaruv.hubs'),
            ])
            ->values()
            ->all();
    }

    private function hubsPagePayload(): array
    {
        if (! Schema::hasTable('hubs')) {
            return ['hubs' => [], 'hubStaff' => [], 'hubRoles' => [], 'hubPermissions' => [], 'hubStats' => []];
        }

        $roleService = app(HubRoleAccessService::class);
        $hubs = Hub::query()
            ->withCount(['staff', 'fulfillments', 'courierTasks'])
            ->orderByDesc('is_primary')
            ->orderByDesc('is_active')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
        $staff = Schema::hasTable('hub_staff')
            ? HubStaff::query()->with('hub:id,name,code')->latest('id')->get()
            : collect();

        $fulfillment = $this->hubFulfillmentOverview();

        return [
            'hubs' => $hubs->map(fn (Hub $hub) => [
                'id' => $hub->id,
                'name' => $hub->name,
                'code' => $hub->code,
                'country' => $hub->country_code,
                'region' => $hub->region_name,
                'city' => $hub->city_name,
                'address' => $hub->address,
                'lat' => $hub->lat,
                'lon' => $hub->lon,
                'active' => (bool) $hub->is_active,
                'primary' => (bool) $hub->is_primary,
                'priority' => (int) $hub->priority,
                'staff' => (int) ($hub->staff_count ?? 0),
                'fulfillments' => (int) ($hub->fulfillments_count ?? 0),
                'courierTasks' => (int) ($hub->courier_tasks_count ?? 0),
                'supportsFirstMile' => (bool) $hub->supports_first_mile,
                'supportsLastMile' => (bool) $hub->supports_last_mile,
                'supportsPostal' => (bool) $hub->supports_postal_dispatch,
                'notes' => data_get($hub->meta ?? [], 'notes'),
                'pipeline' => $fulfillment['byHub'][$hub->id] ?? $this->emptyFulfillmentStages(),
                'updateUrl' => route('boshqaruv.hubs.update', $hub),
                'destroyUrl' => route('boshqaruv.hubs.destroy', $hub),
            ])->values()->all(),
            'hubFulfillment' => [
                'stages' => $fulfillment['stageMeta'],
                'totals' => $fulfillment['totals'],
                'recent' => $fulfillment['recent'],
            ],
            'hubStaff' => $staff->map(fn (HubStaff $member) => [
                'id' => $member->id,
                'hubId' => $member->hub_id,
                'hub' => $member->hub?->name,
                'hubCode' => $member->hub?->code,
                'name' => $member->full_name,
                'username' => $member->username,
                'phone' => $member->phone_number,
                'role' => $member->role,
                'active' => (bool) $member->is_active,
                'permissions' => $member->permissions ?? [],
                'effectivePermissions' => $roleService->effectivePermissions($member),
                'lastSeenAt' => $this->dateTime($member->last_seen_at),
                'updateUrl' => route('boshqaruv.hubs.staff.update', $member),
                'toggleUrl' => route('boshqaruv.hubs.staff.toggle', $member),
                'resetPasswordUrl' => route('boshqaruv.hubs.staff.reset-password', $member),
            ])->values()->all(),
            'hubRoles' => collect(HubStaffRole::cases())->map(fn (HubStaffRole $role) => [
                'value' => $role->value,
                'label' => $roleService->roleBlueprints()[$role->value]['label'] ?? $role->value,
                'description' => $roleService->roleBlueprints()[$role->value]['description'] ?? null,
                'permissions' => $roleService->roleBlueprints()[$role->value]['permissions'] ?? [],
            ])->values()->all(),
            'hubPermissions' => collect($roleService->permissionCatalog())->map(fn ($meta, $key) => [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'description' => $meta['description'] ?? null,
            ])->values()->all(),
            'hubStats' => [
                'total' => $hubs->count(),
                'active' => $hubs->where('is_active', true)->count(),
                'postal' => $hubs->where('supports_postal_dispatch', true)->count(),
                'firstMile' => $hubs->where('supports_first_mile', true)->count(),
                'staff' => $staff->count(),
            ],
            'hubActions' => [
                'storeUrl' => route('boshqaruv.hubs.store'),
                'staffStoreUrl' => route('boshqaruv.hubs.staff.store'),
            ],
        ];
    }

    /**
     * Fulfillment quvurini (pipeline) bosqichlarga ajratib qaytaradi.
     * Bosqichlar: inbound → qc → packing → dispatch → delivery.
     */
    private function fulfillmentStageMap(): array
    {
        return [
            'inbound' => ['label' => 'Kelayotgan', 'icon' => 'bi-truck', 'color' => '#2563EB', 'statuses' => ['picked_from_seller', 'arrived_at_hub']],
            'qc' => ['label' => 'Nazorat (QC)', 'icon' => 'bi-clipboard-check', 'color' => '#7C3AED', 'statuses' => ['qc_checked']],
            'packing' => ['label' => 'Qadoqlash', 'icon' => 'bi-box-seam', 'color' => '#D97706', 'statuses' => ['packed']],
            'dispatch' => ['label' => 'Jo‘natish', 'icon' => 'bi-send', 'color' => '#059669', 'statuses' => ['labeled', 'dispatched_to_post', 'assigned_last_mile']],
            'delivery' => ['label' => 'Yetkazishda', 'icon' => 'bi-geo-alt', 'color' => '#0891B2', 'statuses' => ['out_for_delivery']],
        ];
    }

    private function emptyFulfillmentStages(): array
    {
        $stages = array_fill_keys(array_keys($this->fulfillmentStageMap()), 0);
        $stages['exceptions'] = 0;
        $stages['open'] = 0;

        return $stages;
    }

    private function hubFulfillmentOverview(): array
    {
        $stageMap = $this->fulfillmentStageMap();
        $stageMeta = collect($stageMap)->map(fn ($meta, $key) => [
            'key' => $key,
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'color' => $meta['color'],
        ])->values()->all();

        $empty = [
            'byHub' => [],
            'stageMeta' => $stageMeta,
            'totals' => array_merge($this->emptyFulfillmentStages(), ['delivered' => 0, 'returned' => 0]),
            'recent' => [],
        ];

        if (! Schema::hasTable('order_fulfillments')) {
            return $empty;
        }

        // status_code → bosqich xaritasi
        $statusToStage = [];
        foreach ($stageMap as $stageKey => $meta) {
            foreach ($meta['statuses'] as $status) {
                $statusToStage[$status] = $stageKey;
            }
        }

        $grouped = \App\Models\OrderFulfillment::query()
            ->selectRaw('hub_id, status_code, COUNT(*) as total')
            ->groupBy('hub_id', 'status_code')
            ->get();

        $byHub = [];
        $totals = array_merge($this->emptyFulfillmentStages(), ['delivered' => 0, 'returned' => 0]);

        foreach ($grouped as $row) {
            $hubId = (int) $row->hub_id;
            $count = (int) $row->total;
            $status = (string) $row->status_code;

            if (! isset($byHub[$hubId])) {
                $byHub[$hubId] = $this->emptyFulfillmentStages();
            }

            if (isset($statusToStage[$status])) {
                $stageKey = $statusToStage[$status];
                $byHub[$hubId][$stageKey] += $count;
                $byHub[$hubId]['open'] += $count;
                $totals[$stageKey] += $count;
                $totals['open'] += $count;
            } elseif ($status === 'delivered') {
                $totals['delivered'] += $count;
            } elseif (in_array($status, ['returned', 'cancelled'], true)) {
                $totals['returned'] += $count;
            }
        }

        // Ochiq exception'lar (meta->exception->code)
        $exceptionRows = \App\Models\OrderFulfillment::query()
            ->selectRaw('hub_id, COUNT(*) as total')
            ->whereNotNull('meta->exception->code')
            ->whereNotIn('status_code', ['delivered', 'returned', 'cancelled'])
            ->groupBy('hub_id')
            ->get();

        foreach ($exceptionRows as $row) {
            $hubId = (int) $row->hub_id;
            $count = (int) $row->total;
            if (! isset($byHub[$hubId])) {
                $byHub[$hubId] = $this->emptyFulfillmentStages();
            }
            $byHub[$hubId]['exceptions'] = $count;
            $totals['exceptions'] += $count;
        }

        // So'nggi harakatlar
        $recent = \App\Models\OrderFulfillment::query()
            ->with(['hub:id,name,code'])
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($f) use ($stageMap, $statusToStage) {
                $status = (string) $f->status_code;
                $stageKey = $statusToStage[$status] ?? null;

                return [
                    'id' => $f->id,
                    'orderId' => $f->order_id ?? null,
                    'hub' => $f->hub?->name ?? '—',
                    'hubCode' => $f->hub?->code,
                    'status' => $status,
                    'stage' => $stageKey,
                    'stageLabel' => $stageKey ? ($stageMap[$stageKey]['label'] ?? $status) : ucfirst(str_replace('_', ' ', $status)),
                    'hasException' => (bool) data_get($f->meta ?? [], 'exception.code'),
                    'updatedAt' => $this->dateTime($f->updated_at),
                ];
            })
            ->values()
            ->all();

        return [
            'byHub' => $byHub,
            'stageMeta' => $stageMeta,
            'totals' => $totals,
            'recent' => $recent,
        ];
    }

    private function transactionsPagePayload(): array
    {
        $owner = request('transaction_owner') === 'courier' ? 'courier' : 'seller';
        $search = trim((string) request('transactions_search', ''));

        if ($owner === 'courier') {
            if (! Schema::hasTable('courier_transactions')) {
                return ['transactions' => [], 'transactionPagination' => $this->emptyPagination(), 'transactionTotals' => [], 'transactionFilters' => ['owner' => $owner, 'search' => $search]];
            }

            $baseQuery = CourierTransaction::query();
            $query = (clone $baseQuery)
                ->with('courier:id,first_name,last_name,phone_number,payment_card,balance,total_withdrawal')
                ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                    ->where('id', $search)
                    ->orWhere('order_id', $search)
                    ->orWhere('courier_order_id', $search)
                    ->orWhere('amount', $search)
                    ->orWhereHas('courier', fn ($courier) => $courier
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"))));
            $transactions = $query->latest()->paginate(30, ['*'], 'transactions_page')->withQueryString();

            return [
                'transactions' => $transactions->getCollection()->map(function (CourierTransaction $transaction) {
                    $report = app(PayoutReportService::class)->courier($transaction);
                    $base = CourierTransaction::query()->where('courier_id', $transaction->courier_id);
                    $nearby = $transaction->courier_id
                        ? (clone $base)->whereKeyNot($transaction->id)->latest()->take(6)->get()
                        : collect();

                    $courierName = trim(($transaction->courier?->first_name ?? '').' '.($transaction->courier?->last_name ?? '')) ?: 'Kuryer';

                    return [
                        'id' => $transaction->id,
                        'owner' => 'courier',
                        'user' => $courierName,
                        'phone' => $transaction->courier?->phone_number,
                        'courierId' => $transaction->courier_id,
                        'orderId' => $transaction->order_id,
                        'courierOrderId' => $transaction->courier_order_id,
                        'courierTaskId' => $transaction->courier_task_id,
                        'type' => $transaction->type ?: ($transaction->category ?: 'withdrawal'),
                        'category' => $transaction->category ?: 'withdrawal',
                        'amount' => (float) ($transaction->amount ?? 0),
                        'commissionPercent' => (float) ($transaction->commissionPercent ?? 0),
                        'commission' => (float) ($transaction->commissionPrice ?? 0),
                        'netAmount' => (float) ($transaction->netAmount ?? $transaction->amount ?? 0),
                        'status' => (string) ($transaction->status ?? 'pending'),
                        'statusLabel' => $transaction->status,
                        'date' => $this->dateTime($transaction->created_at),
                        'updatedAt' => $this->dateTime($transaction->updated_at),
                        'method' => $transaction->card ?: $transaction->courier?->masked_card ?: '—',
                        'note' => $transaction->description ?: $transaction->rejected_desc,
                        'ownerTotals' => [
                            'approvedCount' => (int) (clone $base)->where('status', 'approved')->count(),
                            'approvedSum' => (float) (clone $base)->where('status', 'approved')->sum('netAmount'),
                            'pendingSum' => (float) (clone $base)->where('status', 'pending')->sum('netAmount'),
                        ],
                        'breakdown' => [
                            'orders' => $report['totals']['orders'],
                            'products' => $report['totals']['products'],
                            'gross' => $report['totals']['gross'],
                            'commission' => $report['totals']['commission'],
                            'net' => $report['totals']['net'],
                            'basePayout' => $report['totals']['base_payout'],
                            'bonus' => $report['totals']['bonus'],
                            'periodFrom' => $report['period']['from'],
                            'periodTo' => $report['period']['to'],
                            'rows' => $report['rows'],
                        ],
                        'nearby' => $nearby->map(fn (CourierTransaction $row) => [
                            'id' => $row->id,
                            'amount' => (float) ($row->amount ?? 0),
                            'netAmount' => (float) ($row->netAmount ?? 0),
                            'status' => $row->status,
                            'date' => $this->dateTime($row->created_at),
                        ])->values()->all(),
                        'approveUrl' => route('boshqaruv.courier-transactions.approve', $transaction),
                        'rejectUrl' => route('boshqaruv.courier-transactions.reject', $transaction),
                        'reportUrl' => route('boshqaruv.courier-transactions.report', $transaction),
                    ];
                })->values()->all(),
                'transactionPagination' => $this->paginationMeta($transactions),
                'transactionTotals' => [
                    'all' => (int) (clone $baseQuery)->count(),
                    'income' => (float) (clone $baseQuery)->sum('amount'),
                    'commission' => (float) (clone $baseQuery)->sum('commissionPrice'),
                    'pending' => (int) (clone $baseQuery)->where('status', 'pending')->count(),
                    'approved' => (int) (clone $baseQuery)->where('status', 'approved')->count(),
                    'withdrawalPending' => (int) (clone $baseQuery)->where('category', 'withdrawal')->where('status', 'pending')->count(),
                ],
                'transactionFilters' => ['owner' => $owner, 'search' => $search],
            ];
        }

        if (! Schema::hasTable('seller_transactions')) {
            return ['transactions' => [], 'transactionPagination' => $this->emptyPagination(), 'transactionTotals' => [], 'transactionFilters' => ['owner' => $owner, 'search' => $search]];
        }

        $baseQuery = SellerTransaction::query()
            ->where(fn ($query) => $query->whereNull('category')->orWhere('category', 'withdrawal')->orWhere('category', 'seller_withdrawal'));
        $query = (clone $baseQuery)
            ->with('seller:id,shop_name,phone_number,legal_type,inn,legal_address,bank_name,bank_account,bank_mfo,bank_swift,payment_card,card_holder,contract_number,contract_signed,contract_signed_at,contract_expires_at,contract_status,contract_notes')
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('id', $search)
                ->orWhere('order_id', $search)
                ->orWhere('seller_order_id', $search)
                ->orWhere('amount', $search)
                ->orWhereHas('seller', fn ($seller) => $seller
                    ->where('shop_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%"))));
        $transactions = $query->latest()->paginate(30, ['*'], 'transactions_page')->withQueryString();

        return [
            'transactions' => $transactions->getCollection()->map(function (SellerTransaction $transaction) {
                $report = app(PayoutReportService::class)->seller($transaction);
                $seller = $transaction->seller;
                $base = SellerTransaction::query()
                    ->where('seller_id', $transaction->seller_id)
                    ->where(fn ($query) => $query->whereNull('category')->orWhere('category', 'withdrawal')->orWhere('category', 'seller_withdrawal'));
                $nearby = $transaction->seller_id
                    ? (clone $base)->whereKeyNot($transaction->id)->latest()->take(6)->get()
                    : collect();

                return [
                    'id' => $transaction->id,
                    'owner' => 'seller',
                    'user' => $transaction->seller?->shop_name ?: 'Seller',
                    'phone' => $transaction->seller?->phone_number,
                    'sellerId' => $transaction->seller_id,
                    'orderId' => $transaction->order_id,
                    'sellerOrderId' => $transaction->seller_order_id,
                    'type' => $transaction->type ?: ($transaction->category ?: 'payout'),
                    'category' => $transaction->category,
                    'amount' => (float) ($transaction->amount ?? 0),
                    'commissionPercent' => (float) ($transaction->commissionPercent ?? 0),
                    'commission' => (float) ($transaction->commissionPrice ?? 0),
                    'netAmount' => (float) ($transaction->netAmount ?? $transaction->amount ?? 0),
                    'status' => (string) ($transaction->status ?? 'pending'),
                    'statusLabel' => $transaction->status_label,
                    'date' => $this->dateTime($transaction->created_at),
                    'updatedAt' => $this->dateTime($transaction->updated_at),
                    'method' => $transaction->card ?: '—',
                    'note' => $transaction->description ?: $transaction->rejected_desc,
                    'recipient' => [
                        'legalType' => $seller?->legal_type,
                        'legalTypeLabel' => $this->sellerLegalTypeLabel($seller?->legal_type),
                        'inn' => $seller?->inn,
                        'legalAddress' => $seller?->legal_address,
                        'bankName' => $seller?->bank_name,
                        'bankAccount' => $seller?->bank_account,
                        'bankMfo' => $seller?->bank_mfo,
                        'bankSwift' => $seller?->bank_swift,
                        'card' => $seller?->payment_card,
                        'cardHolder' => $seller?->card_holder,
                    ],
                    'contract' => [
                        'number' => $seller?->contract_number,
                        'signed' => (bool) ($seller?->contract_signed ?? false),
                        'signedAt' => optional($seller?->contract_signed_at)->format('Y-m-d'),
                        'expiresAt' => optional($seller?->contract_expires_at)->format('Y-m-d'),
                        'status' => $seller?->contract_computed_status,
                        'rawStatus' => $seller?->contract_status,
                        'notes' => $seller?->contract_notes,
                        'invoiceNumber' => $this->sellerTransactionInvoiceNumber($transaction),
                        'invoiceDate' => $this->sellerTransactionInvoiceDate($transaction),
                        'paymentPurpose' => $this->sellerTransactionPaymentPurpose($transaction, $report),
                    ],
                    'ownerTotals' => [
                        'approvedCount' => (int) (clone $base)->where('status', 'approved')->count(),
                        'approvedSum' => (float) (clone $base)->where('status', 'approved')->sum('netAmount'),
                        'pendingSum' => (float) (clone $base)->where('status', 'pending')->sum('netAmount'),
                    ],
                    'breakdown' => [
                        'orders' => $report['totals']['orders'],
                        'products' => $report['totals']['products'],
                        'gross' => $report['totals']['gross'],
                        'commission' => $report['totals']['commission'],
                        'net' => $report['totals']['net'],
                        'basePayout' => $report['totals']['base_payout'],
                        'bonus' => $report['totals']['bonus'],
                        'periodFrom' => $report['period']['from'],
                        'periodTo' => $report['period']['to'],
                        'rows' => $report['rows'],
                    ],
                    'nearby' => $nearby->map(fn (SellerTransaction $row) => [
                        'id' => $row->id,
                        'amount' => (float) ($row->amount ?? 0),
                        'netAmount' => (float) ($row->netAmount ?? 0),
                        'status' => $row->status,
                        'date' => $this->dateTime($row->created_at),
                    ])->values()->all(),
                    'approveUrl' => route('boshqaruv.transactions.approve', $transaction),
                    'rejectUrl' => route('boshqaruv.transactions.reject', $transaction),
                    'reportUrl' => route('boshqaruv.transactions.report', $transaction),
                ];
            })->values()->all(),
            'transactionPagination' => $this->paginationMeta($transactions),
            'transactionTotals' => [
                'all' => (int) (clone $baseQuery)->count(),
                'income' => (float) (clone $baseQuery)->sum('amount'),
                'commission' => (float) (clone $baseQuery)->sum('commissionPrice'),
                'pending' => (int) (clone $baseQuery)->where('status', 'pending')->count(),
                'approved' => (int) (clone $baseQuery)->where('status', 'approved')->count(),
            ],
            'transactionFilters' => ['owner' => $owner, 'search' => $search],
        ];
    }

    private function commissionAuditPagePayload(): array
    {
        $owner = request('commission_audit_owner') === 'courier' ? 'courier' : 'seller';

        if ($owner === 'courier') {
            if (! Schema::hasTable('courier_transactions')) {
                return ['commissionAuditRows' => [], 'commissionAuditPagination' => $this->emptyPagination(), 'commissionAuditTotals' => [], 'commissionRules' => [], 'commissionAuditFilters' => ['owner' => $owner]];
            }

            $rows = CourierTransaction::query()
                ->with('courier:id,first_name,last_name,phone_number')
                ->latest()
                ->paginate(30, ['*'], 'commission_page')
                ->withQueryString();

            $taskIds = $rows->getCollection()
                ->pluck('courier_task_id')
                ->filter()
                ->unique()
                ->values();
            $tasks = $taskIds->isNotEmpty()
                ? CourierTask::query()->whereIn('id', $taskIds)->get()->keyBy('id')
                : collect();

            $collection = $rows->getCollection()->map(function (CourierTransaction $transaction) use ($tasks) {
                $category = $transaction->category ?: 'withdrawal';
                $task = $transaction->courier_task_id ? $tasks->get($transaction->courier_task_id) : null;
                $amount = (float) ($transaction->amount ?? 0);
                $netAmount = (float) ($transaction->netAmount ?? $transaction->amount ?? 0);
                $actualPercent = (float) ($transaction->commissionPercent ?? 0);
                $actualCommission = (float) ($transaction->commissionPrice ?? 0);
                $expectedPercent = 0.0;
                $expectedCommission = 0.0;
                $ruleSource = 'payout';
                $globalRule = null;
                $auditKind = 'payout';

                if ($category === 'withdrawal') {
                    $expected = $this->expectedWithdrawalCommission($amount);
                    $expectedPercent = $expected['percent'];
                    $expectedCommission = $expected['commission'];
                    $ruleSource = $expected['source'];
                    $globalRule = $expected['globalRule'];
                    $auditKind = 'withdrawal_commission';
                } elseif (in_array($category, ['order_delivery', 'hub_delivery'], true)) {
                    $expectedCommission = (float) ($task?->fee_amount ?? $netAmount);
                    $actualCommission = $netAmount;
                    $ruleSource = $task ? 'km_formula' : 'transaction';
                    $auditKind = 'km_payout';
                } else {
                    $expectedCommission = $netAmount;
                    $actualCommission = $netAmount;
                    $ruleSource = $category;
                    $auditKind = $category;
                }

                $balanceEffect = 0.0;
                if ($transaction->status === 'approved') {
                    $balanceEffect = ($transaction->type === 'income') ? $netAmount : -$netAmount;
                } elseif ($category === 'withdrawal' && $transaction->status === 'pending') {
                    $balanceEffect = -$amount;
                }

                $diffAmount = round($actualCommission - $expectedCommission);

                return [
                    'id' => $transaction->id,
                    'owner' => 'courier',
                    'seller' => trim(($transaction->courier?->first_name ?? '').' '.($transaction->courier?->last_name ?? '')) ?: 'Kuryer',
                    'phone' => $transaction->courier?->phone_number,
                    'courierId' => $transaction->courier_id,
                    'orderId' => $transaction->order_id,
                    'courierOrderId' => $transaction->courier_order_id,
                    'courierTaskId' => $transaction->courier_task_id,
                    'amount' => $amount,
                    'netAmount' => $netAmount,
                    'balanceEffect' => $balanceEffect,
                    'actualPercent' => $actualPercent,
                    'actualCommission' => $actualCommission,
                    'expectedPercent' => $expectedPercent,
                    'expectedCommission' => $expectedCommission,
                    'ruleSource' => $ruleSource,
                    'sellerRate' => 0,
                    'globalRule' => $globalRule,
                    'type' => $transaction->type,
                    'category' => $category,
                    'auditKind' => $auditKind,
                    'distanceKm' => (float) ($task?->distance_km ?? 0),
                    'baseFee' => (float) ($task?->base_fee_amount ?? 0),
                    'distanceFee' => (float) ($task?->distance_fee_amount ?? 0),
                    'bonus' => (float) ($task?->bonus_amount ?? 0),
                    'diffPercent' => round($actualPercent - $expectedPercent, 2),
                    'diffAmount' => $diffAmount,
                    'status' => $transaction->status,
                    'date' => $this->dateTime($transaction->created_at),
                    'ok' => abs($diffAmount) <= 1 && abs($actualPercent - $expectedPercent) < 0.01,
                ];
            })->values();

            return [
                'commissionAuditRows' => $collection->all(),
                'commissionAuditPagination' => $this->paginationMeta($rows),
                'commissionAuditTotals' => [
                    'rows' => (int) $rows->total(),
                    'mismatches' => (int) $collection->where('ok', false)->count(),
                    'sellerSpecific' => (int) $collection->where('ruleSource', 'km_formula')->count(),
                    'global' => (int) $collection->where('auditKind', 'withdrawal_commission')->count(),
                    'balanceAdded' => (float) $collection->where('balanceEffect', '>', 0)->sum('balanceEffect'),
                ],
                'commissionRules' => [
                    ...$this->commissionRulesPayload(),
                    ...$this->courierPayoutRulesPayload(),
                ],
                'commissionAuditFilters' => ['owner' => $owner],
            ];
        }

        if (! Schema::hasTable('seller_transactions')) {
            return ['commissionAuditRows' => [], 'commissionAuditPagination' => $this->emptyPagination(), 'commissionAuditTotals' => [], 'commissionRules' => [], 'commissionAuditFilters' => ['owner' => $owner]];
        }

        $query = SellerTransaction::query()
            ->with('seller:id,shop_name,phone_number,commission_percent')
            ->whereNotNull('commissionPercent')
            ->latest();

        $rows = $query->paginate(30, ['*'], 'commission_page')->withQueryString();
        $collection = $rows->getCollection()->map(function (SellerTransaction $transaction) {
            $sellerRate = (float) ($transaction->seller?->commission_percent ?? 0);
            $amount = (float) ($transaction->amount ?? 0);
            $expected = $this->expectedCommissionRule($transaction->seller, $amount);
            $actualPercent = (float) ($transaction->commissionPercent ?? 0);
            $actualCommission = (float) ($transaction->commissionPrice ?? 0);
            $expectedCommission = round($amount * $expected['percent'] / 100);
            $netAmount = (float) ($transaction->netAmount ?? max(0, $amount - $actualCommission));
            $balanceEffect = 0.0;
            if ($transaction->status === SellerTransaction::STATUS_APPROVED) {
                if ($transaction->type === 'income') {
                    $balanceEffect = $netAmount;
                } elseif ($transaction->type === 'expense') {
                    $balanceEffect = -$netAmount;
                }
            }

            return [
                'id' => $transaction->id,
                'seller' => $transaction->seller?->shop_name ?: 'Seller',
                'phone' => $transaction->seller?->phone_number,
                'sellerId' => $transaction->seller_id,
                'orderId' => $transaction->order_id,
                'sellerOrderId' => $transaction->seller_order_id,
                'amount' => $amount,
                'netAmount' => $netAmount,
                'balanceEffect' => $balanceEffect,
                'actualPercent' => $actualPercent,
                'actualCommission' => $actualCommission,
                'expectedPercent' => $expected['percent'],
                'expectedCommission' => $expectedCommission,
                'ruleSource' => $expected['source'],
                'sellerRate' => $sellerRate,
                'globalRule' => $expected['globalRule'],
                'type' => $transaction->type,
                'category' => $transaction->category,
                'diffPercent' => round($actualPercent - $expected['percent'], 2),
                'diffAmount' => round($actualCommission - $expectedCommission),
                'status' => $transaction->status,
                'date' => $this->dateTime($transaction->created_at),
                'ok' => abs($actualPercent - $expected['percent']) < 0.01 && abs($actualCommission - $expectedCommission) <= 1,
            ];
        })->values();

        return [
            'commissionAuditRows' => $collection->all(),
            'commissionAuditPagination' => $this->paginationMeta($rows),
            'commissionAuditTotals' => [
                'rows' => (int) $rows->total(),
                'mismatches' => (int) $collection->where('ok', false)->count(),
                'sellerSpecific' => (int) $collection->where('ruleSource', 'seller')->count(),
                'global' => (int) $collection->where('ruleSource', 'global')->count(),
                'balanceAdded' => (float) $collection->where('balanceEffect', '>', 0)->sum('balanceEffect'),
            ],
            'commissionRules' => $this->commissionRulesPayload(),
            'commissionAuditFilters' => ['owner' => $owner],
        ];
    }

    private function expectedWithdrawalCommission(float $amount): array
    {
        $rule = CommissionSetting::query()
            ->where('priceFrom', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('priceTo')
                    ->orWhere('priceTo', 0)
                    ->orWhere('priceTo', '>=', $amount);
            })
            ->orderByDesc('priceFrom')
            ->first();

        $percent = (float) ($rule?->percent ?? 0);

        return [
            'percent' => $percent,
            'commission' => round($amount * $percent / 100),
            'source' => $rule ? 'withdrawal_global' : 'withdrawal_none',
            'globalRule' => $rule ? [
                'id' => $rule->id,
                'from' => (float) $rule->priceFrom,
                'to' => (float) $rule->priceTo,
                'percent' => $percent,
            ] : null,
        ];
    }

    private function courierPayoutRulesPayload(): array
    {
        $settings = ProjectSetting::query()->first();
        $rules = collect($settings?->courier_bonus_rules ?? [])->map(fn ($rule, $index) => [
            'id' => 'courier-bonus-'.$index,
            'from' => (float) ($rule['from_km'] ?? 0),
            'to' => (float) ($rule['to_km'] ?? 0),
            'percent' => 0,
            'label' => 'Kuryer bonus',
            'bonus' => (float) ($rule['bonus_amount'] ?? 0),
        ])->values()->all();

        array_unshift($rules, [
            'id' => 'courier-base',
            'from' => 0,
            'to' => 0,
            'percent' => 0,
            'label' => 'Kuryer km payout',
            'base' => (float) ($settings?->courier_base_fee ?? 3000),
            'perKm' => (float) ($settings?->courier_price_per_km ?? 1500),
            'min' => (float) ($settings?->courier_min_fee ?? 5000),
        ]);

        return $rules;
    }

    private function expectedCommissionRule(?Seller $seller, float $amount): array
    {
        $sellerRate = (float) ($seller?->commission_percent ?? 0);
        if ($sellerRate > 0) {
            return [
                'percent' => min(100, $sellerRate),
                'source' => 'seller',
                'globalRule' => null,
            ];
        }

        $rule = CommissionSetting::query()
            ->where('priceFrom', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('priceTo')
                    ->orWhere('priceTo', 0)
                    ->orWhere('priceTo', '>=', $amount);
            })
            ->orderByDesc('priceFrom')
            ->first();

        return [
            'percent' => (float) ($rule?->percent ?? 0),
            'source' => 'global',
            'globalRule' => $rule ? [
                'id' => $rule->id,
                'from' => (float) $rule->priceFrom,
                'to' => (float) $rule->priceTo,
                'percent' => (float) $rule->percent,
            ] : null,
        ];
    }

    private function commissionRulesPayload(): array
    {
        return CommissionSetting::query()
            ->orderBy('priceFrom')
            ->get()
            ->map(fn (CommissionSetting $rule) => [
                'id' => $rule->id,
                'from' => (float) $rule->priceFrom,
                'to' => (float) $rule->priceTo,
                'percent' => (float) $rule->percent,
            ])
            ->values()
            ->all();
    }

    private function auditLogsPagePayload(): array
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return ['auditLogs' => [], 'auditLogPagination' => $this->emptyPagination(), 'auditLogTotals' => []];
        }

        $logs = AdminAuditLog::query()
            ->latest()
            ->paginate(40, ['*'], 'audit_page')
            ->withQueryString();

        return [
            'auditLogs' => $logs->getCollection()->map(fn (AdminAuditLog $log) => [
                'id' => $log->id,
                'admin' => $log->admin_name ?: 'Admin',
                'method' => $log->method,
                'route' => $log->route_name,
                'path' => $log->path,
                'action' => $log->action,
                'targetType' => $log->target_type,
                'targetId' => $log->target_id,
                'requestData' => $log->request_data ?? [],
                'ip' => $log->ip_address,
                'statusCode' => $log->status_code,
                'date' => $this->dateTime($log->created_at),
            ])->values()->all(),
            'auditLogPagination' => $this->paginationMeta($logs),
            'auditLogTotals' => [
                'all' => (int) AdminAuditLog::query()->count(),
                'today' => (int) AdminAuditLog::query()->where('created_at', '>=', now()->startOfDay())->count(),
                'failed' => (int) AdminAuditLog::query()->where('status_code', '>=', 400)->count(),
            ],
        ];
    }

    private function promocodesPayload(): array
    {
        if (! Schema::hasTable('promocodes')) {
            return [];
        }

        return Promocode::query()
            ->withCount('histories')
            ->latest()
            ->get()
            ->map(fn (Promocode $promocode) => [
                'id' => $promocode->id,
                'code' => $promocode->code,
                'type' => $promocode->type,
                'discount' => (int) ($promocode->amount ?? 0),
                'maxDiscount' => (int) ($promocode->max_discount_amount ?? 0),
                'minOrder' => (int) ($promocode->min_order_amount ?? 0),
                'used' => (int) ($promocode->usedCount ?? $promocode->histories_count ?? 0),
                'max' => (int) ($promocode->usesLimit ?? 0),
                'status' => $promocode->is_active ? 'Active' : 'Inactive',
                'expiresAt' => optional($promocode->expires_at)->format('Y-m-d'),
                'perUserLimit' => (int) ($promocode->per_user_limit ?? 1),
                'createUrl' => route('boshqaruv.promokodlar.store'),
                'generateUrl' => route('boshqaruv.promokodlar.generate'),
                'updateUrl' => route('boshqaruv.promokodlar.update', $promocode),
                'destroyUrl' => route('boshqaruv.promokodlar.destroy', $promocode),
            ])
            ->values()
            ->all();
    }

    private function adsPayload(): array
    {
        if (! Schema::hasTable('seller_ads')) {
            return [];
        }

        return SellerAd::query()
            ->with('seller:id,shop_name,phone_number')
            ->latest()
            ->get()
            ->map(fn (SellerAd $ad) => [
                'id' => $ad->id,
                'name' => $ad->seller?->shop_name ?: ($ad->description ?: 'Reklama'),
                'sellerPhone' => $ad->seller?->phone_number,
                'type' => $ad->type,
                'budget' => (float) ($ad->amount ?? 0),
                'spent' => 0,
                'clicks' => 0,
                'days' => (int) ($ad->days ?? 0),
                'status' => $ad->moderation ?: ($ad->paymentStatus ?: 'pending'),
                'paymentStatus' => $ad->paymentStatus,
                'expiresAt' => optional($ad->expire_at)->format('Y-m-d'),
                'image' => $this->assetFromStorage($ad->banner_img),
                'moderateUrl' => route('boshqaruv.ads.moderate', $ad),
                'destroyUrl' => route('boshqaruv.ads.destroy', $ad),
            ])
            ->values()
            ->all();
    }

    private function bloggersPayload(): array
    {
        if (! Schema::hasTable('bloggers')) {
            return [];
        }

        return Blogger::query()
            ->withCount('shipments')
            ->latest()
            ->get()
            ->map(fn (Blogger $blogger) => [
                'id' => $blogger->id,
                'name' => $blogger->full_name,
                'phone' => $blogger->phone_number,
                'address' => $blogger->address,
                'platforms' => array_keys($blogger->socialLinks()),
                'followers' => 0,
                'shipments' => (int) ($blogger->shipments_count ?? 0),
                'status' => $blogger->status_label,
                'activeUntil' => optional($blogger->active_until)->format('Y-m-d'),
                'firstName' => $blogger->first_name,
                'lastName' => $blogger->last_name,
                'instagramUrl' => $blogger->instagram_url,
                'telegramUrl' => $blogger->telegram_url,
                'youtubeUrl' => $blogger->youtube_url,
                'tiktokUrl' => $blogger->tiktok_url,
                'dataUrl' => route('boshqaruv.blogerlar.data', $blogger),
                'createUrl' => route('boshqaruv.blogerlar.store'),
                'updateUrl' => route('boshqaruv.blogerlar.update', $blogger),
                'destroyUrl' => route('boshqaruv.blogerlar.destroy', $blogger),
            ])
            ->values()
            ->all();
    }

    private function giftCertificatesPayload(): array
    {
        if (! Schema::hasTable('gift_certificates')) {
            return [];
        }

        return GiftCertificate::query()
            ->with(['buyer:id,name,lastname,phone_number', 'recipient:id,name,lastname,phone_number'])
            ->latest()
            ->get()
            ->map(fn (GiftCertificate $certificate) => [
                'id' => $certificate->id,
                'code' => $certificate->code,
                'amount' => (int) ($certificate->nominal_uzs ?? 0),
                'buyer' => trim(($certificate->buyer?->name ?? '').' '.($certificate->buyer?->lastname ?? '')) ?: '—',
                'recipient' => trim(($certificate->recipient?->name ?? '').' '.($certificate->recipient?->lastname ?? '')) ?: '—',
                'status' => $certificate->status,
                'statusLabel' => $certificate->status_label,
                'expires' => optional($certificate->expires_at)->format('Y-m-d'),
                'paidAt' => optional($certificate->paid_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.gift-certificates.show', $certificate),
                'statusUrl' => route('admin.gift-certificates.status', $certificate),
                'cancelUrl' => route('admin.gift-certificates.cancel', $certificate),
            ])
            ->values()
            ->all();
    }

    private function marketNewsPayload(): array
    {
        if (! Schema::hasTable('market_news')) {
            return [];
        }

        return MarketNews::query()
            ->latest()
            ->get()
            ->map(fn (MarketNews $news) => [
                'id' => $news->id,
                'title' => $news->localized('title', 'uz'),
                'titleUz' => $news->localized('title', 'uz'),
                'titleRu' => $news->localized('title', 'ru'),
                'titleEn' => $news->localized('title', 'en'),
                'titleJa' => $news->localized('title', 'ja'),
                'description' => $news->localized('description', 'uz'),
                'descriptionUz' => $news->localized('description', 'uz'),
                'descriptionRu' => $news->localized('description', 'ru'),
                'descriptionEn' => $news->localized('description', 'en'),
                'descriptionJa' => $news->localized('description', 'ja'),
                'align' => $news->align,
                'status' => $news->status ? 'Active' : 'Inactive',
                'active' => (bool) $news->status,
                'actionType' => $news->normalizedAction(),
                'actionId' => $news->action_id,
                'action' => $news->action_label,
                'image' => $this->assetFromStorage($news->imgUrl),
                'date' => optional($news->created_at)->format('Y-m-d'),
                'createUrl' => route('boshqaruv.market-news.store'),
                'updateUrl' => route('boshqaruv.market-news.update', $news),
                'toggleUrl' => route('boshqaruv.market-news.toggle', $news),
                'destroyUrl' => route('boshqaruv.market-news.destroy', $news),
            ])
            ->values()
            ->all();
    }

    private function collectionsPagePayload(): array
    {
        if (! Schema::hasTable('curated_collections') || ! Schema::hasTable('curated_collection_items')) {
            return ['collections' => []];
        }

        $hasStationery = Schema::hasTable('stationeries');

        return [
            'collections' => CuratedCollection::query()
                ->with(array_filter([
                    'items.book.seller:id,shop_name',
                    $hasStationery ? 'items.stationery.seller:id,shop_name' : null,
                ]))
                ->orderBy('sort_order')
                ->latest('id')
                ->get()
                ->map(function (CuratedCollection $collection) {
                    $items = $collection->items->map(function (CuratedCollectionItem $item) {
                        $isStationery = $item->product_type === 'stationery';
                        $product = $isStationery ? $item->stationery : $item->book;

                        $price = $isStationery
                            ? (int) (($product?->discount_price ?: $product?->price) ?? 0)
                            : (int) (($product?->discountPrice ?: $product?->price) ?? 0);
                        $stock = $isStationery
                            ? (int) ($product?->stock ?? 0)
                            : (int) ($product?->count ?? 0);
                        $fallbackName = $isStationery
                            ? "Kanselyariya #{$item->product_id}"
                            : "Kitob #{$item->product_id}";

                        $available = $product
                            && $stock >= (int) ($item->quantity ?? 1)
                            && (int) ($product->is_hidden ?? 0) === 0
                            && (int) ($product->is_approved ?? 0) === 1;

                        return [
                            'id' => $item->id,
                            'productId' => (int) $item->product_id,
                            'productType' => $isStationery ? 'stationery' : 'book',
                            'name' => $product?->name ?? $fallbackName,
                            'author' => $isStationery ? null : $product?->author,
                            'seller' => $product?->seller?->shop_name,
                            'quantity' => (int) ($item->quantity ?? 1),
                            'sortOrder' => (int) ($item->sort_order ?? 0),
                            'price' => $price,
                            'stock' => $stock,
                            'available' => (bool) $available,
                            'image' => $this->assetFromStorage(collect($product?->images ?? [])->first()),
                        ];
                    })->values();

                    return [
                        'id' => $collection->id,
                        'slug' => $collection->slug,
                        'isActive' => (bool) $collection->is_active,
                        'festiveEffect' => (bool) ($collection->festive_effect ?? true),
                        'sortOrder' => (int) ($collection->sort_order ?? 0),
                        'customTotalPrice' => $collection->custom_total_price !== null ? (int) $collection->custom_total_price : null,
                        'titleUz' => $collection->title_uz,
                        'titleRu' => $collection->title_ru,
                        'titleEn' => $collection->title_en,
                        'titleJa' => $collection->title_ja,
                        'subtitleUz' => $collection->subtitle_uz,
                        'subtitleRu' => $collection->subtitle_ru,
                        'subtitleEn' => $collection->subtitle_en,
                        'subtitleJa' => $collection->subtitle_ja,
                        'descriptionUz' => $collection->description_uz,
                        'descriptionRu' => $collection->description_ru,
                        'descriptionEn' => $collection->description_en,
                        'descriptionJa' => $collection->description_ja,
                        'heroImage' => $this->assetFromStorage($collection->hero_image),
                        'gradientFrom' => $collection->gradient_from,
                        'gradientTo' => $collection->gradient_to,
                        'buttonBgColor' => $collection->button_bg_color,
                        'buttonTextColor' => $collection->button_text_color,
                        'baseTotalAmount' => (int) $items->sum(fn ($item) => ((int) $item['price']) * ((int) $item['quantity'])),
                        'itemCount' => $items->count(),
                        'availableItemCount' => $items->where('available', true)->count(),
                        'totalAmount' => $collection->custom_total_price !== null
                            ? (int) $collection->custom_total_price
                            : (int) $items->sum(fn ($item) => ((int) $item['price']) * ((int) $item['quantity'])),
                        'items' => $items->all(),
                        'bookSearchUrl' => route('boshqaruv.collections.book-search'),
                        'createUrl' => route('boshqaruv.collections.store'),
                        'updateUrl' => route('boshqaruv.collections.update', $collection),
                        'toggleUrl' => route('boshqaruv.collections.toggle', $collection),
                        'duplicateUrl' => route('boshqaruv.collections.duplicate', $collection),
                        'destroyUrl' => route('boshqaruv.collections.destroy', $collection),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function reelsPayload(): array
    {
        if (! Schema::hasTable('reels')) {
            return [];
        }

        return Reel::query()
            ->withCount('items')
            ->orderBy('order')
            ->latest()
            ->get()
            ->map(fn (Reel $reel) => [
                'id' => $reel->id,
                'title' => $reel->title,
                'description' => $reel->description,
                'order' => (int) ($reel->order ?? 0),
                'items' => (int) ($reel->items_count ?? 0),
                'status' => 'Active',
                'createUrl' => route('boshqaruv.reels.store'),
                'updateUrl' => route('boshqaruv.reels.update', $reel),
                'destroyUrl' => route('boshqaruv.reels.destroy', $reel),
            ])
            ->values()
            ->all();
    }

    private function policiesPayload(): array
    {
        if (! Schema::hasTable('policies')) {
            return [];
        }

        $hasTranslations = Schema::hasTable('policy_translations');

        return Policy::query()
            ->when($hasTranslations, fn ($query) => $query->with('translations'))
            ->orderBy('sort_order')
            ->latest()
            ->get()
            ->map(function (Policy $policy) use ($hasTranslations) {
                $translations = [];

                foreach (self::CONTENT_LOCALES as $locale) {
                    $translation = $hasTranslations
                        ? $policy->translations->firstWhere('locale', $locale)
                        : null;

                    $translations[$locale] = [
                        'title' => $translation?->title ?? '',
                        'content' => $translation?->content ?? '',
                    ];
                }

                return [
                    'id' => $policy->id,
                    'title' => $policy->title,
                    'slug' => $policy->slug,
                    'content' => $policy->content,
                    'status' => $policy->is_active ? 'Active' : 'Inactive',
                    'showInApp' => (bool) $policy->show_in_app,
                    'sortOrder' => (int) ($policy->sort_order ?? 0),
                    'translations' => $translations,
                    'createUrl' => route('boshqaruv.policies.store'),
                    'updateUrl' => route('boshqaruv.policies.update', $policy),
                    'toggleUrl' => route('boshqaruv.policies.toggle', $policy),
                    'destroyUrl' => route('boshqaruv.policies.destroy', $policy),
                ];
            })
            ->values()
            ->all();
    }

    private function pushNotificationsPayload(): array
    {
        if (! Schema::hasTable('fcm_notifications')) {
            return [];
        }

        return FcmNotifications::query()
            ->latest()
            ->get()
            ->map(function (FcmNotifications $notification) {
                $who = (string) $notification->who;
                $targetMode = ctype_digit($who) || str_contains($who, ':') ? 'individual' : 'audience';

                return [
                    'id' => $notification->id,
                    'title' => $notification->name,
                    'body' => $notification->description,
                    'who' => $who,
                    'targetMode' => $targetMode,
                    'targetLabel' => $this->pushTargetLabel($who),
                    'source' => $notification->source ?? 'legacy',
                    'status' => match ($notification->delivery_status ?? 'in_app') {
                        'sent' => 'Yuborildi',
                        'failed' => 'Xatolik',
                        'sending' => 'Yuborilmoqda',
                        default => 'Ilova ichida',
                    },
                    'sentCount' => (int) ($notification->sent_count ?? 0),
                    'failedCount' => (int) ($notification->failed_count ?? 0),
                    'date' => optional($notification->created_at)->format('Y-m-d H:i'),
                    'createUrl' => route('boshqaruv.push.store'),
                    'destroyUrl' => route('boshqaruv.push.destroy', $notification),
                ];
            })
            ->values()
            ->all();
    }

    private function pushTargetLabel(string $who): string
    {
        if (ctype_digit($who)) {
            $user = User::query()->find((int) $who);

            return $user
                ? (trim((string) ($user->name.' '.$user->lastname)) ?: "Foydalanuvchi #{$user->id}")
                : "Foydalanuvchi #{$who}";
        }

        if (preg_match('/^(business|courier):(\d+)$/', $who, $matches)) {
            $recipient = $matches[1] === 'business'
                ? Seller::query()->find((int) $matches[2])
                : Couriers::query()->find((int) $matches[2]);

            return $recipient
                ? $this->pushRecipientLabel($matches[1], $recipient)
                : ($matches[1] === 'business' ? 'Seller' : 'Kuryer')." #{$matches[2]}";
        }

        return match ($who) {
            'users' => 'Barcha foydalanuvchilar',
            'business' => 'Barcha sellerlar',
            'courier' => 'Barcha kuryerlar',
            default => $who,
        };
    }

    private function searchHistoryPagePayload(): array
    {
        if (! Schema::hasTable('search_histories')) {
            return ['searchHistory' => [], 'searchHistoryPagination' => $this->emptyPagination(), 'searchHistoryTypes' => [], 'searchHistoryInsights' => ['topQueries' => [], 'missingDemand' => [], 'summary' => []]];
        }

        $filter = (string) request('search_history_type', 'all');
        $search = trim((string) request('search_history_search', ''));
        $hasResultType = Schema::hasColumn('search_histories', 'result_type');
        $hasResultName = Schema::hasColumn('search_histories', 'result_name');
        $hasResultCount = Schema::hasColumn('search_histories', 'result_count');
        $hasSearchCount = Schema::hasColumn('search_histories', 'search_count');
        $hasDraft = Schema::hasColumn('search_histories', 'is_draft');
        $query = SearchHistory::query()->with('user:id,name,lastname,phone_number');
        $this->applySearchHistoryFilters($query, $filter, $search, $hasResultType, $hasResultName);
        $history = $query->latest()->paginate(40, ['*'], 'search_history_page')->withQueryString();

        $insights = $this->searchHistoryInsightsPayload($filter, $search, $hasResultType, $hasResultName, $hasResultCount, $hasSearchCount);

        return [
            'searchHistory' => $history->getCollection()->map(fn (SearchHistory $history) => [
                'id' => $history->id,
                'text' => $history->text,
                'user' => trim(($history->user?->name ?? '').' '.($history->user?->lastname ?? '')) ?: ($history->session_id ?: 'Mehmon'),
                'resultCount' => $hasResultCount ? (int) ($history->result_count ?? 0) : 0,
                'resultName' => $hasResultName ? $history->result_name : null,
                'resultType' => $hasResultType ? $history->result_type : null,
                'searchCount' => $hasSearchCount ? (int) ($history->search_count ?? 0) : 0,
                'draft' => $hasDraft ? (bool) $history->is_draft : false,
                'date' => optional($history->created_at)->format('Y-m-d H:i'),
            ])
                ->values()
                ->all(),
            'searchHistoryPagination' => $this->paginationMeta($history),
            'searchHistoryTypes' => $hasResultType ? SearchHistory::query()->whereNotNull('result_type')->distinct()->orderBy('result_type')->pluck('result_type')->values()->all() : [],
            'searchHistoryFilters' => ['type' => $filter, 'search' => $search],
            'searchHistoryInsights' => $insights,
        ];
    }

    private function searchHistoryInsightsPayload(
        string $filter,
        string $search,
        bool $hasResultType,
        bool $hasResultName,
        bool $hasResultCount,
        bool $hasSearchCount
    ): array {
        try {
            $insightsQuery = SearchHistory::query();
            $this->applySearchHistoryFilters($insightsQuery, $filter, $search, $hasResultType, $hasResultName);

            $weightSql = $hasSearchCount ? 'COALESCE(search_count, 1)' : '1';
            $foundConditionSql = $hasResultCount
                ? 'COALESCE(result_count, 0) > 0'
                : ($hasResultName ? "COALESCE(result_name, '') != ''" : '0 = 1');
            $missingConditionSql = $hasResultCount
                ? 'COALESCE(result_count, 0) = 0'
                : ($hasResultName ? "(result_name IS NULL OR result_name = '')" : '1 = 1');

            $topQueries = (clone $insightsQuery)
                ->selectRaw('text')
                ->selectRaw("SUM({$weightSql}) as total_searches")
                ->selectRaw("SUM(CASE WHEN {$foundConditionSql} THEN {$weightSql} ELSE 0 END) as found_searches")
                ->selectRaw("SUM(CASE WHEN {$missingConditionSql} THEN {$weightSql} ELSE 0 END) as missing_searches")
                ->selectRaw('MAX(created_at) as last_seen_at')
                ->whereNotNull('text')
                ->where('text', '!=', '')
                ->groupBy('text')
                ->orderByDesc('total_searches')
                ->limit(12)
                ->get()
                ->map(fn ($row) => [
                    'text' => $row->text,
                    'totalSearches' => (int) ($row->total_searches ?? 0),
                    'foundSearches' => (int) ($row->found_searches ?? 0),
                    'missingSearches' => (int) ($row->missing_searches ?? 0),
                    'successRate' => (int) round(((int) ($row->total_searches ?? 0)) > 0 ? ((int) ($row->found_searches ?? 0) / (int) $row->total_searches) * 100 : 0),
                    'lastSeenAt' => $this->dateTime($row->last_seen_at),
                ])
                ->values()
                ->all();

            $missingDemand = (clone $insightsQuery)
                ->selectRaw('text')
                ->selectRaw("SUM({$weightSql}) as total_searches")
                ->selectRaw('COUNT(*) as attempts')
                ->selectRaw('MAX(created_at) as last_seen_at')
                ->whereNotNull('text')
                ->where('text', '!=', '')
                ->groupBy('text')
                ->havingRaw("SUM(CASE WHEN {$foundConditionSql} THEN 1 ELSE 0 END) = 0")
                ->orderByDesc('total_searches')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'text' => $row->text,
                    'totalSearches' => (int) ($row->total_searches ?? 0),
                    'attempts' => (int) ($row->attempts ?? 0),
                    'lastSeenAt' => $this->dateTime($row->last_seen_at),
                    'recommendation' => 'Katalogga qo‘shib ko‘rish yoki synonym/alias ochish tavsiya etiladi.',
                ])
                ->values()
                ->all();

            $summaryBase = (clone $insightsQuery);
            $summary = [
                'totalRecords' => (int) $summaryBase->count(),
                'zeroResultRecords' => (int) (clone $insightsQuery)->whereRaw($missingConditionSql)->count(),
                'uniqueQueries' => (int) (clone $insightsQuery)->whereNotNull('text')->where('text', '!=', '')->distinct('text')->count('text'),
            ];

            return [
                'topQueries' => $topQueries,
                'missingDemand' => $missingDemand,
                'summary' => $summary,
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'topQueries' => [],
                'missingDemand' => [],
                'summary' => [
                    'totalRecords' => 0,
                    'zeroResultRecords' => 0,
                    'uniqueQueries' => 0,
                ],
            ];
        }
    }

    private function applySearchHistoryFilters($query, string $filter, string $search, bool $hasResultType, bool $hasResultName): void
    {
        $query
            ->when($hasResultType && $filter !== 'all', fn ($builder) => $builder->where('result_type', $filter))
            ->when($search !== '', fn ($builder) => $builder->where(function ($nested) use ($search, $hasResultName) {
                $nested->where('text', 'like', "%{$search}%")
                    ->when($hasResultName, fn ($result) => $result->orWhere('result_name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%"));
            }));
    }

    private function giftsPayload(): array
    {
        if (! Schema::hasTable('gifts')) {
            return [];
        }

        return Gifts::query()
            ->with('seller:id,shop_name')
            ->latest()
            ->get()
            ->map(fn (Gifts $gift) => [
                'id' => $gift->id,
                'artikul' => $gift->artikul,
                'name' => $gift->name,
                'seller' => $gift->seller?->shop_name,
                'stock' => (int) ($gift->stock ?? 0),
                'priceFrom' => (float) ($gift->priceFrom ?? 0),
                'priceTo' => (float) ($gift->priceTo ?? 0),
                'status' => $gift->status ? 'Active' : 'Inactive',
                'approved' => (bool) $gift->is_approved,
                'sold' => (int) ($gift->totalSales ?? 0),
                'revenue' => (float) ($gift->totalRevenue ?? 0),
                'image' => $this->assetFromStorage(collect($gift->images ?? [])->first()),
                'indexUrl' => route('admin.gifts.index'),
            ])
            ->values()
            ->all();
    }

    private function ticketsPagePayload(): array
    {
        if (! Schema::hasTable('bot_tickets') && ! Schema::hasTable('seller_support_tickets')) {
            return ['tickets' => [], 'ticketPagination' => $this->emptyPagination(), 'ticketCounts' => []];
        }

        $tab = (string) request('tickets_tab', 'all');
        $source = (string) request('tickets_source', 'all');
        $search = trim((string) request('tickets_search', ''));
        $rows = collect();

        if ($source !== 'seller' && Schema::hasTable('bot_tickets')) {
            $rows = $rows->concat(BotTicket::query()
                ->with(['operator', 'latestMessage'])
                ->withCount('messages')
                ->when($tab !== 'all', fn ($builder) => $builder->where('status', $tab))
                ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                    ->where('id', $search)
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('first_msg', 'like', "%{$search}%")))
                ->latest()
                ->get()
                ->map(fn (BotTicket $ticket) => [
                    'source' => 'bot',
                    'sourceLabel' => 'User/Telegram',
                    'id' => $ticket->id,
                    'user' => $ticket->name ?: ($ticket->username ?: 'Mijoz'),
                    'subject' => $ticket->first_msg ?: $ticket->latestMessage?->message ?: 'Support ticket',
                    'operator' => $ticket->operator?->name,
                    'messages' => (int) ($ticket->messages_count ?? 0),
                    'rating' => $ticket->rating,
                    'status' => $ticket->status,
                    'date' => optional($ticket->created_at)->format('Y-m-d H:i'),
                    'sortAt' => optional($ticket->updated_at ?: $ticket->created_at)->timestamp ?? 0,
                    'dataUrl' => route('boshqaruv.support.data', $ticket),
                    'closeUrl' => route('boshqaruv.support.close', $ticket),
                    'replyUrl' => route('boshqaruv.support.reply', $ticket),
                ]));
        }

        if ($source !== 'user' && Schema::hasTable('seller_support_tickets')) {
            $rows = $rows->concat(SellerSupportTicket::query()
                ->with(['seller:id,shop_name,firstname,lastname,phone_number', 'admin:id,name', 'latestMessage'])
                ->withCount('messages')
                ->when($tab !== 'all', fn ($builder) => $builder->where('status', $tab))
                ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                    ->where('id', $search)
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn ($seller) => $seller
                        ->where('shop_name', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"))))
                ->latest('last_message_at')
                ->latest('id')
                ->get()
                ->map(fn (SellerSupportTicket $ticket) => [
                    'source' => 'seller',
                    'sourceLabel' => 'Seller',
                    'id' => $ticket->id,
                    'user' => trim(($ticket->seller?->shop_name ?: 'Seller').' · '.($ticket->seller?->phone_number ?: '')),
                    'subject' => $ticket->subject ?: $ticket->latestMessage?->message ?: 'Kitobchi bilan suhbat',
                    'operator' => $ticket->admin?->name,
                    'messages' => (int) ($ticket->messages_count ?? 0),
                    'rating' => null,
                    'status' => $ticket->status,
                    'date' => optional($ticket->last_message_at ?: $ticket->created_at)->format('Y-m-d H:i'),
                    'sortAt' => optional($ticket->last_message_at ?: $ticket->updated_at ?: $ticket->created_at)->timestamp ?? 0,
                    'dataUrl' => route('boshqaruv.seller-support.data', $ticket),
                    'closeUrl' => $ticket->status !== 'closed' ? route('boshqaruv.seller-support.close', $ticket) : null,
                    'replyUrl' => $ticket->status !== 'closed' ? route('boshqaruv.seller-support.reply', $ticket) : null,
                ]));
        }

        $rows = $rows->sortByDesc('sortAt')->values();
        $page = max(1, (int) request('tickets_page', 1));
        $perPage = 25;
        $tickets = new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['pageName' => 'tickets_page']
        );

        return [
            'tickets' => $tickets->getCollection()->map(fn ($ticket) => collect($ticket)->except('sortAt')->all())->values()->all(),
            'ticketPagination' => $this->paginationMeta($tickets),
            'ticketCounts' => [
                'all' => (int) $rows->count(),
                'user' => (int) $rows->where('source', 'bot')->count(),
                'seller' => (int) $rows->where('source', 'seller')->count(),
                'queue' => (int) $rows->where('status', 'queue')->count(),
                'active' => (int) $rows->where('status', 'active')->count(),
                'open' => (int) $rows->where('status', 'open')->count(),
                'answered' => (int) $rows->where('status', 'answered')->count(),
                'waiting' => (int) $rows->where('status', 'waiting')->count(),
                'closed' => (int) $rows->where('status', 'closed')->count(),
                'rated' => (int) $rows->where('status', 'rated')->count(),
            ],
            'ticketFilters' => ['tab' => $tab, 'source' => $source, 'search' => $search],
        ];
    }

    private function ticketDetailPayload(BotTicket $ticket): array
    {
        $ticket->load(['operator', 'attachments', 'messages.admin', 'messages.operator']);

        return [
            'profile' => [
                'id' => $ticket->id,
                'userId' => $ticket->user_id,
                'name' => $ticket->name ?: 'Mijoz',
                'username' => $ticket->username,
                'sourceType' => $ticket->source_type,
                'sourceConversationId' => $ticket->source_conversation_id,
                'operator' => $ticket->operator?->name,
                'status' => $ticket->status,
                'rating' => $ticket->rating,
                'closeReason' => $ticket->close_reason,
                'closedAt' => $this->dateTime($ticket->closed_at),
                'createdAt' => $this->dateTime($ticket->created_at),
            ],
            'messages' => $ticket->messages->map(fn ($message) => [
                'id' => $message->id,
                'sentBy' => $message->sent_by,
                'actor' => $message->admin?->name ?: $message->operator?->name,
                'type' => $message->message_type,
                'message' => $message->message,
                'delivered' => (bool) $message->is_delivered,
                'error' => $message->delivery_error,
                'date' => $this->dateTime($message->created_at),
            ])->values()->all(),
            'attachments' => $ticket->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->file_name ?: $attachment->file_id,
                'type' => $attachment->file_type,
                'size' => $attachment->file_size ? round($attachment->file_size / 1024, 1) : null,
                'sentBy' => $attachment->sent_by,
            ])->values()->all(),
            'actions' => [
                'closeUrl' => route('boshqaruv.support.close', $ticket),
                'replyUrl' => route('boshqaruv.support.reply', $ticket),
            ],
        ];
    }

    private function sellerSupportTicketDetailPayload(SellerSupportTicket $ticket): array
    {
        $ticket->update(['admin_unread_count' => 0]);
        $ticket->load(['seller:id,shop_name,firstname,lastname,phone_number,region,status', 'admin:id,name', 'messages.admin']);

        return [
            'profile' => [
                'id' => $ticket->id,
                'name' => $ticket->seller?->shop_name ?: trim(($ticket->seller?->firstname ?? '').' '.($ticket->seller?->lastname ?? '')),
                'username' => $ticket->seller?->phone_number,
                'userId' => $ticket->seller_id,
                'sourceType' => 'Seller support',
                'sourceConversationId' => null,
                'operator' => $ticket->admin?->name,
                'status' => $ticket->status,
                'rating' => null,
                'closeReason' => $ticket->close_reason,
                'closedAt' => $this->dateTime($ticket->closed_at),
                'createdAt' => $this->dateTime($ticket->created_at),
            ],
            'messages' => $ticket->messages->map(fn (SellerSupportTicketMessage $message) => [
                'id' => $message->id,
                'sentBy' => $message->sender_type,
                'actor' => $message->sender_type === 'admin'
                    ? ($message->admin?->name ?: 'Admin')
                    : ($message->sender_type === 'seller' ? 'Seller' : 'Tizim'),
                'type' => 'text',
                'message' => $message->message,
                'delivered' => true,
                'error' => null,
                'date' => $this->dateTime($message->created_at),
            ])->values()->all(),
            'attachments' => [],
            'actions' => [
                'closeUrl' => route('boshqaruv.seller-support.close', $ticket),
                'replyUrl' => route('boshqaruv.seller-support.reply', $ticket),
            ],
        ];
    }

    private function sellerAiActionsPagePayload(): array
    {
        if (! Schema::hasTable('seller_ai_actions')) {
            return ['sellerAiActions' => [], 'sellerAiActionPagination' => $this->emptyPagination(), 'sellerAiActionCounts' => []];
        }

        $status = (string) request('ai_status', 'all');
        $search = trim((string) request('ai_search', ''));

        $query = SellerAiAction::query()
            ->with(['seller:id,shop_name,phone_number', 'requestedBy:id,firstname,lastname,phone_number'])
            ->when($status !== 'all', fn ($builder) => $builder->where('status', $status))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('token', 'like', "%{$search}%")
                ->orWhere('source_file_name', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhereHas('seller', fn ($seller) => $seller
                    ->where('shop_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%"))));

        $actions = $query->latest()->paginate(25, ['*'], 'ai_page')->withQueryString();

        return [
            'sellerAiActions' => $actions->getCollection()->map(fn (SellerAiAction $action) => [
                'token' => $action->token,
                'seller' => $action->seller?->shop_name ?: 'Seller',
                'sellerPhone' => $action->seller?->phone_number,
                'requestedBy' => trim(($action->requestedBy?->firstname ?? '').' '.($action->requestedBy?->lastname ?? '')) ?: $action->requestedBy?->phone_number,
                'actionType' => $action->action_type,
                'status' => $action->status,
                'file' => $action->source_file_name,
                'summary' => $action->summary,
                'itemsCount' => count($action->payload['items'] ?? []),
                'appliedCount' => count($action->result['applied'] ?? []),
                'payload' => $action->payload,
                'result' => $action->result,
                'createdAt' => $this->dateTime($action->created_at),
                'appliedAt' => $this->dateTime($action->applied_at),
                'rolledBackAt' => $this->dateTime($action->rolled_back_at),
            ])->values()->all(),
            'sellerAiActionPagination' => $this->paginationMeta($actions),
            'sellerAiActionCounts' => [
                'all' => SellerAiAction::query()->count(),
                'preview' => SellerAiAction::query()->where('status', 'preview')->count(),
                'applied' => SellerAiAction::query()->where('status', 'applied')->count(),
                'rolled_back' => SellerAiAction::query()->where('status', 'rolled_back')->count(),
                'failed' => SellerAiAction::query()->where('status', 'failed')->count(),
            ],
            'sellerAiActionFilters' => ['status' => $status, 'search' => $search],
        ];
    }

    private function conversationsPagePayload(): array
    {
        if (! Schema::hasTable('conversations')) {
            return ['conversations' => [], 'conversationCounts' => [], 'conversationPagination' => $this->emptyPagination()];
        }

        $tab = (string) request('chat_tab', 'all');
        $search = trim((string) request('chat_search', ''));
        $query = DB::table('conversations')
            ->select([
                'conversations.id',
                'conversations.shop_id',
                'conversations.type',
                'conversations.last_message_at',
                'u1.name as user_name',
                'u1.lastname as user_lastname',
                'u1.phone_number as user_phone',
                'u2.name as receiver_name',
                'u2.lastname as receiver_lastname',
                's.shop_name as seller_name',
            ])
            ->selectSub(DB::table('messages')->selectRaw('count(*)')->whereColumn('messages.conversation_id', 'conversations.id')->where('is_deleted', false), 'messages_count')
            ->selectSub(DB::table('messages')->select('message')->whereColumn('messages.conversation_id', 'conversations.id')->where('is_deleted', false)->latest('created_at')->limit(1), 'last_message')
            ->leftJoin('users as u1', 'u1.id', '=', 'conversations.user_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id')
            ->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->where('messages.is_deleted', false);
            })
            ->when($tab === 'user', fn ($builder) => $builder->whereNull('conversations.shop_id'))
            ->when($tab === 'seller', fn ($builder) => $builder->whereNotNull('conversations.shop_id'))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('u1.name', 'like', "%{$search}%")
                ->orWhere('u1.lastname', 'like', "%{$search}%")
                ->orWhere('u1.phone_number', 'like', "%{$search}%")
                ->orWhere('s.shop_name', 'like', "%{$search}%")));
        $conversations = $query->orderByDesc('conversations.last_message_at')->paginate(25, ['*'], 'chat_page')->withQueryString();

        return [
            'conversations' => $conversations->getCollection()->map(fn ($conversation) => [
                'id' => $conversation->id,
                'kind' => $conversation->shop_id ? 'seller' : 'user',
                'type' => $conversation->type,
                'user' => trim(($conversation->user_name ?? '').' '.($conversation->user_lastname ?? '')) ?: 'Foydalanuvchi',
                'phone' => $conversation->user_phone,
                'agent' => $conversation->seller_name ?: trim(($conversation->receiver_name ?? '').' '.($conversation->receiver_lastname ?? '')) ?: 'Foydalanuvchi',
                'messages' => (int) ($conversation->messages_count ?? 0),
                'lastMsg' => $conversation->last_message ?: 'Xabar yo‘q',
                'date' => $this->dateTime($conversation->last_message_at),
                'dataUrl' => route('boshqaruv.chat.data', $conversation->id),
            ])
                ->values()
                ->all(),
            'conversationCounts' => $this->conversationCounts(),
            'conversationPagination' => $this->paginationMeta($conversations),
            'conversationFilters' => ['tab' => $tab, 'search' => $search],
        ];
    }

    private function conversationCounts(): array
    {
        if (! Schema::hasTable('conversations')) {
            return ['all' => 0, 'user' => 0, 'seller' => 0];
        }

        $base = DB::table('conversations')
            ->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->where('messages.is_deleted', false);
            });

        return [
            'all' => (int) (clone $base)->count(),
            'user' => (int) (clone $base)->whereNull('shop_id')->count(),
            'seller' => (int) (clone $base)->whereNotNull('shop_id')->count(),
        ];
    }

    private function chatDetailPayload(int $conversationId): array
    {
        $conversation = DB::table('conversations')
            ->select(['conversations.*', 'u1.name as user_name', 'u1.lastname as user_lastname', 'u1.phone_number as user_phone', 'u2.name as receiver_name', 'u2.lastname as receiver_lastname', 's.shop_name as seller_name'])
            ->leftJoin('users as u1', 'u1.id', '=', 'conversations.user_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id')
            ->where('conversations.id', $conversationId)
            ->first();
        abort_if(! $conversation, 404);

        $reportedIds = Schema::hasTable('reports')
            ? DB::table('reports')->where('reportable_type', 'conversation_message')->pluck('reportable_id')->flip()
            : collect();
        $messages = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->where('is_deleted', false)
            ->orderBy('created_at')
            ->take(200)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'senderId' => $message->sender_id,
                'senderType' => $message->sender_type,
                'senderLabel' => $this->chatSenderLabel($message->sender_type, $conversation),
                'message' => $message->message,
                'read' => (bool) $message->is_read,
                'edited' => (bool) $message->is_edited,
                'reported' => $reportedIds->has($message->id),
                'date' => $this->dateTime($message->created_at),
            ]);

        $otherConversations = DB::table('conversations')
            ->select([
                'conversations.id',
                'conversations.shop_id',
                'conversations.type',
                'conversations.last_message_at',
                'u2.name as receiver_name',
                'u2.lastname as receiver_lastname',
                's.shop_name as seller_name',
            ])
            ->selectSub(DB::table('messages')->selectRaw('count(*)')->whereColumn('messages.conversation_id', 'conversations.id')->where('is_deleted', false), 'messages_count')
            ->selectSub(DB::table('messages')->select('message')->whereColumn('messages.conversation_id', 'conversations.id')->where('is_deleted', false)->latest('created_at')->limit(1), 'last_message')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id')
            ->where('conversations.user_id', $conversation->user_id)
            ->where('conversations.id', '!=', $conversationId)
            ->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->where('messages.is_deleted', false);
            })
            ->orderByDesc('conversations.last_message_at')
            ->limit(12)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'kind' => $item->shop_id ? 'seller' : 'user',
                'type' => $item->type,
                'agent' => $item->seller_name ?: trim(($item->receiver_name ?? '').' '.($item->receiver_lastname ?? '')) ?: 'Foydalanuvchi',
                'messages' => (int) ($item->messages_count ?? 0),
                'lastMsg' => $item->last_message ?: 'Xabar yo‘q',
                'date' => $this->dateTime($item->last_message_at),
                'dataUrl' => route('boshqaruv.chat.data', $item->id),
            ])
            ->values()
            ->all();

        return [
            'profile' => [
                'id' => $conversation->id,
                'kind' => $conversation->shop_id ? 'seller' : 'user',
                'kindLabel' => $conversation->shop_id ? 'Do‘kon bilan suhbat' : 'Foydalanuvchi suhbati',
                'type' => $conversation->type,
                'user' => trim(($conversation->user_name ?? '').' '.($conversation->user_lastname ?? '')) ?: 'Foydalanuvchi',
                'phone' => $conversation->user_phone,
                'agent' => $conversation->seller_name ?: trim(($conversation->receiver_name ?? '').' '.($conversation->receiver_lastname ?? '')) ?: 'Foydalanuvchi',
                'messagesCount' => $messages->count(),
                'orderId' => $conversation->order_id,
                'createdAt' => $this->dateTime($conversation->created_at),
                'lastMessageAt' => $this->dateTime($conversation->last_message_at),
            ],
            'messages' => $messages->values()->all(),
            'otherConversations' => $otherConversations,
        ];
    }

    private function chatSenderLabel(?string $senderType, object $conversation): string
    {
        return match ($senderType) {
            'user' => trim(($conversation->user_name ?? '').' '.($conversation->user_lastname ?? '')) ?: 'Mijoz',
            'seller' => $conversation->seller_name ?: 'Do‘kon',
            'courier' => 'Kuryer',
            'admin', 'operator' => trim(($conversation->receiver_name ?? '').' '.($conversation->receiver_lastname ?? '')) ?: 'Operator',
            default => $senderType ?: 'Noma’lum',
        };
    }

    private function complaintsPagePayload(): array
    {
        if (! Schema::hasTable('reports')) {
            return ['complaints' => [], 'complaintPagination' => $this->emptyPagination()];
        }

        $reports = Report::query()
            ->with('user:id,name,lastname,phone_number,avatar')
            ->latest()
            ->paginate(25, ['*'], 'complaints_page')
            ->withQueryString();

        $subjectPayloads = $this->complaintSubjectPayloads($reports->getCollection());

        return [
            'complaints' => $reports->getCollection()->map(function (Report $report) {
                $otherReports = Report::query()->where('user_id', $report->user_id)->whereKeyNot($report->id)->latest()->take(5)->get();

                return [
                    'id' => $report->id,
                    'user' => trim(($report->user?->name ?? '').' '.($report->user?->lastname ?? '')) ?: 'Mijoz',
                    'phone' => $report->user?->phone_number,
                    'avatar' => $report->user?->avatar,
                    'reason' => $report->reason,
                    'comment' => $report->comment,
                    'type' => $this->normalizeComplaintReportableType($report->reportable_type) ?: 'report',
                    'reportableId' => $report->reportable_id,
                    'status' => $report->status,
                    'date' => $this->dateTime($report->created_at),
                    'content' => $subjectPayloads[$report->id] ?? $this->complaintMissingSubjectPayload($report),
                    'otherReports' => $otherReports->map(fn (Report $other) => [
                        'id' => $other->id,
                        'reason' => $other->reason,
                        'status' => $other->status,
                        'date' => $this->dateTime($other->created_at),
                    ])->values()->all(),
                    'statusUrl' => route('boshqaruv.complaints.status', $report),
                    'destroyUrl' => route('boshqaruv.complaints.destroy', $report),
                ];
            })
                ->values()
                ->all(),
            'complaintPagination' => $this->paginationMeta($reports),
        ];
    }

    private function complaintSubjectPayloads($reports): array
    {
        $payloads = [];
        $reports = collect($reports)->map(function (Report $report) {
            $report->setAttribute('normalized_reportable_type', $this->normalizeComplaintReportableType($report->reportable_type));

            return $report;
        });

        $bookClubIds = $reports
            ->where('normalized_reportable_type', 'book_club')
            ->pluck('reportable_id')
            ->filter()
            ->unique()
            ->values();

        if ($bookClubIds->isNotEmpty()) {
            $posts = BookClub::query()
                ->whereIn('id', $bookClubIds)
                ->with([
                    'user:id,name,lastname,phone_number,avatar',
                    'images',
                    'activeWarning',
                ])
                ->withCount(['likes', 'comments'])
                ->get()
                ->keyBy('id');

            foreach ($reports->where('normalized_reportable_type', 'book_club') as $report) {
                $post = $posts->get($report->reportable_id);
                $payloads[$report->id] = $post
                    ? $this->complaintBookClubSubjectPayload($post)
                    : $this->complaintMissingSubjectPayload($report);
            }
        }

        $messageIds = $reports
            ->where('normalized_reportable_type', 'conversation_message')
            ->pluck('reportable_id')
            ->filter()
            ->unique()
            ->values();

        if ($messageIds->isNotEmpty()) {
            $messages = Message::query()
                ->whereIn('id', $messageIds)
                ->with([
                    'conversation.user:id,name,lastname,phone_number',
                    'conversation.receiver:id,name,lastname,phone_number',
                    'conversation.shop:id,shop_name',
                    'replyTo:id,message,sender_type,created_at',
                ])
                ->get()
                ->keyBy('id');

            foreach ($reports->where('normalized_reportable_type', 'conversation_message') as $report) {
                $message = $messages->get($report->reportable_id);
                $payloads[$report->id] = $message
                    ? $this->complaintConversationMessagePayload($message)
                    : $this->complaintMissingSubjectPayload($report);
            }
        }

        foreach ($reports as $report) {
            $payloads[$report->id] ??= $this->complaintMissingSubjectPayload($report);
        }

        return $payloads;
    }

    private function complaintBookClubSubjectPayload(BookClub $post): array
    {
        return [
            'kind' => 'book_club',
            'title' => 'Book Club posti',
            'summary' => $post->text ?: 'Post matni yo‘q',
            'author' => trim(($post->user?->name ?? '').' '.($post->user?->lastname ?? '')) ?: 'Kitobxon',
            'phone' => $post->user?->phone_number,
            'avatar' => $this->assetFromStorage($post->user?->avatar),
            'date' => $this->dateTime($post->created_at),
            'images' => $post->images
                ->take(3)
                ->map(fn ($image) => $this->assetFromStorage($image->image))
                ->filter()
                ->values()
                ->all(),
            'stats' => [
                'likes' => (int) ($post->likes_count ?? 0),
                'comments' => (int) ($post->comments_count ?? 0),
            ],
            'meta' => [
                'warning' => (bool) $post->activeWarning,
                'isDeleted' => (bool) $post->is_deleted,
                'repost' => (bool) $post->repost,
                'aiStatus' => $post->ai_post_status,
            ],
            'manageUrl' => route('boshqaruv.book-club', ['focus_post' => $post->id]),
            'manageLabel' => 'Post boshqaruvini ochish',
        ];
    }

    private function complaintConversationMessagePayload(Message $message): array
    {
        $conversation = $message->conversation;

        return [
            'kind' => 'conversation_message',
            'title' => 'Chatdagi xabar',
            'summary' => $message->message ?: 'Xabar matni yo‘q',
            'date' => $this->dateTime($message->created_at),
            'meta' => [
                'senderType' => $this->complaintMessageSenderLabel($message->sender_type),
                'isEdited' => (bool) $message->is_edited,
                'isDeleted' => (bool) $message->is_deleted,
            ],
            'conversation' => [
                'id' => $conversation?->id,
                'user' => trim(($conversation?->user?->name ?? '').' '.($conversation?->user?->lastname ?? '')) ?: 'Foydalanuvchi',
                'userPhone' => $conversation?->user?->phone_number,
                'agent' => $conversation?->shop?->shop_name ?: trim(($conversation?->receiver?->name ?? '').' '.($conversation?->receiver?->lastname ?? '')) ?: 'Operator',
                'type' => $conversation?->type,
                'orderId' => $conversation?->order_id,
            ],
            'replyTo' => $message->replyTo ? [
                'text' => $message->replyTo->message ?: 'Matnsiz xabar',
                'senderType' => $this->complaintMessageSenderLabel($message->replyTo->sender_type),
                'date' => $this->dateTime($message->replyTo->created_at),
            ] : null,
            'context' => $this->complaintConversationContextPayload($message),
            'manageUrl' => $conversation
                ? route('boshqaruv.chat', ['focus_chat' => $conversation->id, 'focus_message' => $message->id])
                : route('boshqaruv.chat'),
            'manageLabel' => 'Chat boshqaruvini ochish',
        ];
    }

    private function complaintConversationContextPayload(Message $message): array
    {
        $before = Message::query()
            ->where('conversation_id', $message->conversation_id)
            ->where('id', '<', $message->id)
            ->where('is_deleted', false)
            ->latest('id')
            ->take(2)
            ->get()
            ->reverse()
            ->values();

        $after = Message::query()
            ->where('conversation_id', $message->conversation_id)
            ->where('id', '>', $message->id)
            ->where('is_deleted', false)
            ->orderBy('id')
            ->take(2)
            ->get();

        return $before
            ->concat(collect([$message]))
            ->concat($after)
            ->map(fn (Message $row) => [
                'id' => $row->id,
                'text' => $row->message ?: 'Matnsiz xabar',
                'senderType' => $this->complaintMessageSenderLabel($row->sender_type),
                'date' => $this->dateTime($row->created_at),
                'isTarget' => (int) $row->id === (int) $message->id,
            ])
            ->values()
            ->all();
    }

    private function complaintMessageSenderLabel(?string $senderType): string
    {
        return match ($senderType) {
            'user' => 'Mijoz',
            'seller' => 'Seller',
            'courier' => 'Kuryer',
            'admin', 'operator' => 'Operator',
            default => $senderType ?: 'Noma’lum',
        };
    }

    private function complaintMissingSubjectPayload(Report $report): array
    {
        return [
            'kind' => 'missing',
            'title' => 'Kontent topilmadi',
            'summary' => 'Shikoyat qilingan obyekt hozir bazada topilmadi yoki o‘chirilgan.',
            'meta' => [
                'reportableType' => $this->normalizeComplaintReportableType($report->reportable_type) ?: $report->reportable_type,
                'rawReportableType' => $report->reportable_type,
                'reportableId' => $report->reportable_id,
            ],
            'manageUrl' => null,
            'manageLabel' => null,
        ];
    }

    private function normalizeComplaintReportableType(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        return match ($type) {
            'book_club', BookClub::class, 'App\\Models\\book_club', 'App\\Models\\Bookclub' => 'book_club',
            'conversation_message', Message::class, 'message', 'messages', 'App\\Models\\conversation_message' => 'conversation_message',
            default => Str::of($type)->afterLast('\\')->lower()->value(),
        };
    }

    private function vacanciesPayload(): array
    {
        if (! Schema::hasTable('vacancies')) {
            return [];
        }

        $hasTranslations = Schema::hasTable('vacancy_translations');
        $hasApplications = Schema::hasTable('career_applications');

        return Vacancy::query()
            ->when($hasTranslations, fn ($query) => $query->with('translations'))
            ->when($hasApplications, fn ($query) => $query->withCount('careerApplications'))
            ->ordered()
            ->get()
            ->map(function (Vacancy $vacancy) use ($hasTranslations) {
                $translations = [];

                foreach (self::CONTENT_LOCALES as $locale) {
                    $translation = $hasTranslations
                        ? $vacancy->translations->firstWhere('locale', $locale)
                        : null;

                    $translations[$locale] = [
                        'title' => $translation?->title ?? '',
                        'contract_type' => $translation?->contract_type ?? '',
                        'location' => $translation?->location ?? '',
                        'description' => $translation?->description ?? '',
                    ];
                }

                return [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'icon' => $vacancy->icon,
                    'contractType' => $vacancy->contract_type,
                    'location' => $vacancy->location,
                    'description' => $vacancy->description,
                    'sortOrder' => (int) ($vacancy->sort_order ?? 0),
                    'status' => $vacancy->is_active ? 'Active' : 'Inactive',
                    'applicants' => (int) ($vacancy->career_applications_count ?? 0),
                    'translations' => $translations,
                    'createUrl' => route('boshqaruv.vacancies.store'),
                    'updateUrl' => route('boshqaruv.vacancies.update', $vacancy),
                    'toggleUrl' => route('boshqaruv.vacancies.toggle', $vacancy),
                    'destroyUrl' => route('boshqaruv.vacancies.destroy', $vacancy),
                ];
            })
            ->values()
            ->all();
    }

    private function careerApplicationsPayload(): array
    {
        if (! Schema::hasTable('career_applications')) {
            return [];
        }

        return CareerApplication::query()
            ->with('vacancy:id,title')
            ->latest()
            ->get()
            ->map(fn (CareerApplication $application) => [
                'id' => $application->id,
                'name' => $application->full_name,
                'email' => $application->email,
                'telegram' => $application->telegram_username,
                'vacancy' => $application->vacancy?->title ?: $application->type,
                'status' => $application->status,
                'message' => $application->cover_message,
                'date' => optional($application->created_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.job-applications.show', $application),
                'cvUrl' => route('admin.job-applications.cv', $application),
                'replyUrl' => route('admin.job-applications.reply', $application),
                'statusUrl' => route('admin.job-applications.status', $application),
            ])
            ->values()
            ->all();
    }

    private function adminsPayload(): array
    {
        if (! Schema::hasTable('admins')) {
            return [];
        }

        return Admin::query()
            ->latest()
            ->get()
            ->map(fn (Admin $admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role_label,
                'active' => (bool) $admin->is_active,
                'lastLogin' => optional($admin->last_login_at)->format('Y-m-d H:i'),
                'roleKey' => $admin->role,
                'createUrl' => route('boshqaruv.admins.store'),
                'updateUrl' => route('boshqaruv.admins.update', $admin),
                'toggleUrl' => route('boshqaruv.admins.toggle', $admin),
                'destroyUrl' => route('boshqaruv.admins.destroy', $admin),
            ])
            ->values()
            ->all();
    }

    private function apiClientsPayload(): array
    {
        if (! Schema::hasTable('api_clients')) {
            return [];
        }

        return Cache::remember('boshqaruv:api-clients:payload:v2', now()->addMinutes(5), function () {
            $hasRequestLogs = Schema::hasTable('api_client_request_logs');
            $hasRateLimitPerSecond = Schema::hasColumn('api_clients', 'rate_limit_per_second');
            $hasRateLimitPerMinute = Schema::hasColumn('api_clients', 'rate_limit_per_minute');

            return ApiClient::query()
                ->when($hasRequestLogs, fn ($query) => $query->withCount('requestLogs'))
                ->orderByDesc('id')
                ->get()
                ->map(fn (ApiClient $client) => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'key' => $client->app_id ?: '—',
                    'abilities' => implode(', ', $this->apiClientAbilities($client->abilities)),
                    'active' => (bool) $client->is_active,
                    'requests' => (int) ($client->request_logs_count ?? 0),
                    'rateLimitSecond' => $hasRateLimitPerSecond ? (int) ($client->rate_limit_per_second ?? 0) : null,
                    'rateLimitMinute' => $hasRateLimitPerMinute ? (int) ($client->rate_limit_per_minute ?? 0) : null,
                    'createUrl' => route('boshqaruv.api-clients.store'),
                    'updateUrl' => route('boshqaruv.api-clients.update', $client),
                    'toggleUrl' => route('boshqaruv.api-clients.toggle', $client),
                    'regenerateUrl' => route('boshqaruv.api-clients.regenerate', $client),
                    'destroyUrl' => route('boshqaruv.api-clients.destroy', $client),
                ])
                ->values()
                ->all();
        });
    }

    private function apiLogsPayload(): array
    {
        if (! Schema::hasTable('api_client_request_logs')) {
            return [];
        }

        return Cache::remember('boshqaruv:api-clients:logs:v1', now()->addSeconds(30), fn () => ApiClientRequestLog::query()
            ->with('client:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn (ApiClientRequestLog $log) => [
                'id' => $log->id,
                'client' => $log->client?->name,
                'method' => $log->method,
                'path' => $log->path,
                'status' => (int) ($log->status_code ?? 0),
                'date' => optional($log->created_at)->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all());
    }

    private function apiClientsMeta(): array
    {
        $warnings = [];

        if (! Schema::hasTable('api_clients')) {
            $warnings[] = 'api_clients jadvali topilmadi. Sahifa faqat bo‘sh holatda ochiladi.';
        }

        if (Schema::hasTable('api_clients') && ! Schema::hasColumn('api_clients', 'rate_limit_per_second')) {
            $warnings[] = 'Sekundlik limit ustuni topilmadi. Limitlar vaqtincha ko‘rsatilmaydi.';
        }

        if (Schema::hasTable('api_clients') && ! Schema::hasColumn('api_clients', 'rate_limit_per_minute')) {
            $warnings[] = 'Minutlik limit ustuni topilmadi. Limitlar vaqtincha ko‘rsatilmaydi.';
        }

        if (! Schema::hasTable('api_client_request_logs')) {
            $warnings[] = 'API request loglari jadvali topilmadi. So‘rov statistikasi va loglar ko‘rsatilmaydi.';
        }

        return Cache::remember('boshqaruv:api-clients:meta:v1', now()->addMinutes(5), fn () => [
            'warnings' => $warnings,
        ]);
    }

    private function apiClientAbilities(mixed $abilities): array
    {
        if (is_string($abilities)) {
            $decoded = json_decode($abilities, true);
            $abilities = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $abilities);
        }

        return collect(is_array($abilities) ? $abilities : [])
            ->map(fn ($ability) => strtolower(trim((string) $ability)))
            ->filter()
            ->unique()
            ->values()
            ->all() ?: ['read'];
    }

    private function clearApiClientCache(): void
    {
        Cache::forget('boshqaruv:api-clients:payload:v2');
        Cache::forget('boshqaruv:api-clients:logs:v1');
        Cache::forget('boshqaruv:api-clients:meta:v1');
    }

    private function splitPagePayload(): array
    {
        $service = app(SplitProfileService::class);
        $status = trim((string) request('split_status', 'all'));
        $search = trim((string) request('split_search', ''));
        $hasProfiles = Schema::hasTable('split_user_profiles');

        $query = User::query()
            ->select('users.*')
            ->when($search !== '', fn ($builder) => $builder->where(function ($builder) use ($search) {
                $builder->where('users.id', $search)
                    ->orWhere('users.name', 'like', "%{$search}%")
                    ->orWhere('users.lastname', 'like', "%{$search}%")
                    ->orWhere('users.phone_number', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            }));

        if ($hasProfiles) {
            $query
                ->leftJoin('split_user_profiles as split_profiles', 'split_profiles.user_id', '=', 'users.id')
                ->when($status === 'eligible', fn ($builder) => $builder->where('split_profiles.eligible', true))
                ->when($status === 'ineligible', fn ($builder) => $builder->where(function ($builder) {
                    $builder->where('split_profiles.eligible', false)->orWhereNull('split_profiles.eligible');
                }))
                ->when($status === 'locked', fn ($builder) => $builder->where('split_profiles.active_exposure', '>', 0))
                ->orderByDesc(DB::raw('COALESCE(split_profiles.eligible, 0)'))
                ->orderByDesc(DB::raw('COALESCE(split_profiles.confidence_score, 0)'));
        }

        $query->orderByDesc('users.id');

        $users = $query->paginate(20, ['users.*'], 'split_page')->withQueryString();
        $service->warmProfilesForUsers($users->getCollection());

        $profiles = $hasProfiles
            ? SplitUserProfile::query()
                ->whereIn('user_id', $users->getCollection()->pluck('id'))
                ->get()
                ->keyBy('user_id')
            : collect();

        return [
            'splitSettings' => $this->splitSettingsPanelPayload($service),
            'splitSummary' => $this->splitSummaryPayload(),
            'splitBookRules' => $this->splitCategoryRulesPayload('book'),
            'splitStationeryRules' => $this->splitCategoryRulesPayload('stationery'),
            'splitUsers' => $users->getCollection()->map(function (User $user) use ($profiles) {
                /** @var SplitUserProfile|null $profile */
                $profile = $profiles->get($user->id);

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'phone' => $this->formatPhone($user->phone_number ?? $user->phone ?? ''),
                    'verified' => (bool) $user->isVerified,
                    'eligible' => (bool) ($profile?->eligible ?? false),
                    'confidenceScore' => (float) ($profile?->confidence_score ?? 0),
                    'computedLimit' => (int) ($profile?->computed_limit ?? 0),
                    'availableLimit' => (int) ($profile?->available_limit ?? 0),
                    'activeExposure' => (int) ($profile?->active_exposure ?? 0),
                    'reputationScore' => (float) ($profile?->reputation_score ?? $user->reputation_score ?? 0),
                    'completedOrders' => (int) ($profile?->completed_orders_all ?? 0),
                    'verifiedCardAgeDays' => (int) ($profile?->verified_card_age_days ?? 0),
                    'verifiedCardsCount' => (int) ($profile?->verified_cards_count ?? 0),
                    'successfulCardPayments180d' => (int) ($profile?->successful_card_payments_180d ?? 0),
                    'codReturnStrikes' => (int) ($profile?->cod_return_strikes ?? 0),
                    'reasons' => array_values($profile?->eligibility_reasons ?? []),
                    'lastRefreshedAt' => optional($profile?->last_refreshed_at)->format('Y-m-d H:i'),
                    'profileUrl' => route('boshqaruv.users', ['users_search' => $user->id]),
                    'refreshUrl' => route('boshqaruv.split.refresh'),
                ];
            })->values()->all(),
            'splitPagination' => $this->paginationMeta($users),
            'splitFilters' => ['status' => $status, 'search' => $search],
            'splitActions' => [
                'settingsUpdateUrl' => route('boshqaruv.split.settings.update'),
                'ruleStoreUrl' => route('boshqaruv.split.category-rules.store'),
                'refreshUrl' => route('boshqaruv.split.refresh'),
            ],
        ];
    }

    private function splitSettingsPanelPayload(SplitProfileService $service): array
    {
        $settings = $service->settings();

        return [
            'enabled' => (bool) $settings['enabled'],
            'publicEnabled' => (bool) $settings['public_enabled'],
            'upfrontPercent' => (int) $settings['upfront_percent'],
            'termDays' => (int) $settings['term_days'],
            'globalMinOrderSum' => (int) $settings['global_min_order_sum'],
            'globalMaxOrderSum' => (int) $settings['global_max_order_sum'],
            'globalMinLimit' => (int) $settings['global_min_limit'],
            'globalMaxLimit' => (int) $settings['global_max_limit'],
            'minCompletedOrders' => (int) $settings['min_completed_orders'],
            'minAccountAgeDays' => (int) $settings['min_account_age_days'],
            'minCardAgeDays' => (int) $settings['min_card_age_days'],
            'minReputationScore' => (float) $settings['min_reputation_score'],
            'maxActiveContracts' => (int) $settings['max_active_contracts'],
            'defaultFeePercent' => (float) $settings['default_fee_percent'],
            'cardDeleteLockEnabled' => (bool) $settings['card_delete_lock_enabled'],
            'refundSenderCardId' => (string) $settings['refund_sender_card_id'],
            'refundServiceId' => (string) $settings['refund_service_id'],
        ];
    }

    private function splitSummaryPayload(): array
    {
        if (! Schema::hasTable('split_user_profiles')) {
            return [
                'profiles' => 0,
                'eligible' => 0,
                'locked' => 0,
                'avgConfidence' => 0,
                'totalAvailableLimit' => 0,
            ];
        }

        return [
            'profiles' => (int) SplitUserProfile::query()->count(),
            'eligible' => (int) SplitUserProfile::query()->where('eligible', true)->count(),
            'locked' => (int) SplitUserProfile::query()->where('active_exposure', '>', 0)->count(),
            'avgConfidence' => round((float) SplitUserProfile::query()->avg('confidence_score'), 2),
            'totalAvailableLimit' => (int) round((float) SplitUserProfile::query()->sum('available_limit')),
        ];
    }

    private function splitCategoryRulesPayload(string $type): array
    {
        if (! Schema::hasTable('split_category_rules')) {
            return [];
        }

        $rules = SplitCategoryRule::query()
            ->where('category_type', $type)
            ->get()
            ->keyBy('category_id');

        $categories = $type === 'book'
            ? BookCategories::query()->orderBy('name_uz')->get()
            : StationeryCategory::query()->orderBy('name_uz')->get();

        return $categories->map(function ($category) use ($rules, $type) {
            /** @var SplitCategoryRule|null $rule */
            $rule = $rules->get($category->id);

            return [
                'id' => $category->id,
                'categoryType' => $type,
                'name' => $category->name_uz ?: $category->name_ru ?: $category->name_en ?: "Kategoriya #{$category->id}",
                'active' => (bool) ($category->is_active ?? true),
                'enabled' => (bool) ($rule?->enabled ?? false),
                'feePercent' => $rule?->fee_percent !== null ? (float) $rule->fee_percent : null,
                'minOrderSumOverride' => $rule?->min_order_sum_override,
                'maxOrderSumOverride' => $rule?->max_order_sum_override,
                'upfrontPercentOverride' => $rule?->upfront_percent_override,
                'destroyUrl' => $rule ? route('boshqaruv.split.category-rules.destroy', $rule) : null,
            ];
        })->values()->all();
    }

    private function settingsPayload(): array
    {
        if (! Schema::hasTable('project_settings')) {
            return [];
        }

        $settings = ProjectSetting::query()->firstOrCreate([]);

        return [
            'project' => [
                'business_version_ios' => $settings->business_version_ios,
                'business_version_android' => $settings->business_version_android,
                'courier_version_ios' => $settings->courier_version_ios,
                'courier_version_android' => $settings->courier_version_android,
                'market_version_ios' => $settings->market_version_ios,
                'market_version_android' => $settings->market_version_android,
                'kitobchi_phone' => $settings->kitobchi_phone,
                'kitobchi_email' => $settings->kitobchi_email,
                'business_phone' => $settings->business_phone,
                'business_email' => $settings->business_email,
                'courier_phone' => $settings->courier_phone,
                'courier_email' => $settings->courier_email,
                'on_premium' => (bool) $settings->on_premium,
                'on_reels' => (bool) $settings->on_reels,
                'ramadan' => (bool) $settings->ramadan,
                'stop_sales' => (bool) $settings->stop_sales,
                'show_home_special_sections' => $settings->show_home_special_sections === null ? true : (bool) $settings->show_home_special_sections,
                'packaging_price_small' => (int) ($settings->packaging_price_small ?? 25000),
                'packaging_price_large' => (int) ($settings->packaging_price_large ?? 40000),
                'packaging_threshold' => (int) ($settings->packaging_threshold ?? 4),
                'tax_mode' => $settings->tax_mode ?? 'fixed',
                'tax_fixed_uzs' => (int) ($settings->tax_fixed_uzs ?? 0),
                'tax_profit_percent' => (float) ($settings->tax_profit_percent ?? 0),
                'payment_provider_percent' => (float) ($settings->payment_provider_percent ?? 0),
                'courier_base_fee' => (int) ($settings->courier_base_fee ?? 3000),
                'courier_price_per_km' => (int) ($settings->courier_price_per_km ?? 1500),
                'courier_min_fee' => (int) ($settings->courier_min_fee ?? 5000),
                'courier_bonus_rules' => $settings->courier_bonus_rules ?? [],
                'telegram_login_enabled' => (bool) $settings->telegram_login_enabled,
                'telegram_client_id' => $settings->telegram_client_id,
                'telegram_redirect_uri_ios' => $settings->telegram_redirect_uri_ios ?: 'https://app3206985527-login.tg.dev',
                'telegram_redirect_uri_android' => $settings->telegram_redirect_uri_android ?: 'https://app2854400165-login.tg.dev/tglogin',
                'telegram_scopes' => $settings->telegram_scopes ?: 'openid profile phone',
            ],
            'commission' => $this->settingsCommissionPayload(),
            'cashback' => $this->settingsCashbackPayload(),
            'cashbackDelivery' => $this->settingsCashbackPayload(CashbackSetting::TYPE_DELIVERY),
            'cashbackPickup' => $this->settingsCashbackPayload(CashbackSetting::TYPE_PICKUP),
            'actions' => [
                'versions' => route('boshqaruv.settings.versions'),
                'contacts' => route('boshqaruv.settings.contacts'),
                'appFlags' => route('boshqaruv.settings.app-flags'),
                'courierBonus' => route('boshqaruv.settings.courier-bonus'),
                'finance' => route('boshqaruv.settings.finance'),
                'telegram' => route('boshqaruv.settings.telegram'),
                'commissionStore' => route('boshqaruv.settings.commission.store'),
                'cashbackStore' => route('boshqaruv.settings.cashback.store'),
            ],
            'indexUrl' => route('boshqaruv.settings'),
        ];
    }

    private function expensesPagePayload(): array
    {
        if (! Schema::hasTable('platform_expenses')) {
            return [
                'expenses' => [],
                'expensePagination' => $this->emptyPagination(),
                'expenseSummary' => ['total' => 0, 'month' => 0, 'categories' => []],
                'expenseCategories' => PlatformExpense::CATEGORIES,
                'actions' => ['storeUrl' => route('boshqaruv.expenses.store')],
            ];
        }

        $category = trim((string) request('expenses_category', ''));
        $search = trim((string) request('expenses_search', ''));
        $query = PlatformExpense::query()
            ->when($category !== '', fn ($builder) => $builder->where('category', $category))
            ->when($search !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
                ->where('title', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhere('order_id', $search)));
        $expenses = $query->latest('spent_at')->latest('id')->paginate(20, ['*'], 'expenses_page')->withQueryString();
        $categoryTotals = PlatformExpense::query()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'label' => PlatformExpense::CATEGORIES[$row->category] ?? $row->category,
                'total' => (float) $row->total,
            ])->values()->all();

        return [
            'expenses' => $expenses->getCollection()->map(fn (PlatformExpense $expense) => [
                'id' => $expense->id,
                'category' => $expense->category,
                'categoryLabel' => $expense->category_label,
                'amount' => (float) $expense->amount,
                'spentAt' => optional($expense->spent_at)->format('Y-m-d'),
                'title' => $expense->title,
                'note' => $expense->note,
                'orderId' => $expense->order_id,
                'reference' => $expense->reference,
                'updateUrl' => route('boshqaruv.expenses.update', $expense),
                'destroyUrl' => route('boshqaruv.expenses.destroy', $expense),
            ])->values()->all(),
            'expensePagination' => $this->paginationMeta($expenses),
            'expenseFilters' => ['category' => $category, 'search' => $search],
            'expenseSummary' => [
                'total' => (float) PlatformExpense::query()->sum('amount'),
                'month' => (float) PlatformExpense::query()->whereBetween('spent_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('amount'),
                'categories' => $categoryTotals,
            ],
            'expenseCategories' => PlatformExpense::CATEGORIES,
            'actions' => ['storeUrl' => route('boshqaruv.expenses.store')],
        ];
    }

    private function settingsCommissionPayload(): array
    {
        if (! Schema::hasTable('commission_settings')) {
            return [];
        }

        return CommissionSetting::query()
            ->orderBy('priceFrom')
            ->get()
            ->map(fn (CommissionSetting $setting) => [
                'id' => $setting->id,
                'priceFrom' => (int) ($setting->priceFrom ?? 0),
                'priceTo' => (int) ($setting->priceTo ?? 0),
                'percent' => (int) ($setting->percent ?? 0),
                'updateUrl' => route('boshqaruv.settings.commission.update', $setting),
                'destroyUrl' => route('boshqaruv.settings.commission.destroy', $setting),
            ])
            ->values()
            ->all();
    }

    private function settingsCashbackPayload(?string $type = null): array
    {
        if (! Schema::hasTable('cashback_settings')) {
            return [];
        }

        return CashbackSetting::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('fromUzs')
            ->get()
            ->map(fn (CashbackSetting $setting) => [
                'id' => $setting->id,
                'fromUzs' => (int) ($setting->fromUzs ?? 0),
                'toUzs' => (int) ($setting->toUzs ?? 0),
                'cashback' => (int) ($setting->cashback ?? 0),
                'type' => $setting->type ?: CashbackSetting::TYPE_DELIVERY,
                'updateUrl' => route('boshqaruv.settings.cashback.update', $setting),
                'destroyUrl' => route('boshqaruv.settings.cashback.destroy', $setting),
            ])
            ->values()
            ->all();
    }

    private function deliveryServicesPayload(): array
    {
        if (! Schema::hasTable('delivery_services')) {
            return [];
        }

        return DeliveryService::query()
            ->orderBy('name')
            ->get()
            ->map(fn (DeliveryService $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'price' => (int) ($service->priceKg ?? 0),
                'days' => (int) ($service->muddat ?? 0),
                'country' => $service->forCountry,
                'capital' => (bool) $service->capital,
                'freeFrom' => (int) ($service->freePriceFrom ?? 0),
                'active' => (bool) $service->status,
                'indexUrl' => route('boshqaruv.logistika'),
            ])
            ->values()
            ->all();
    }

    private function logisticsPagePayload(): array
    {
        if (! Schema::hasTable('delivery_services')) {
            return ['deliveryServices' => [], 'deliveryRules' => [], 'logisticsStats' => [], 'logisticsPreview' => null];
        }

        $codFilter = (string) request('cod_filter', '');
        $services = DeliveryService::query()->orderBy('name')->get();
        $rulesQuery = Schema::hasTable('delivery_zone_rules')
            ? DeliveryZoneRule::query()->with('deliveryService')
            : null;

        if ($rulesQuery && $codFilter === 'on') {
            $rulesQuery->where('cod_allowed', true);
        } elseif ($rulesQuery && $codFilter === 'off') {
            $rulesQuery->where('cod_allowed', false);
        }

        $rules = $rulesQuery
            ? $rulesQuery->orderByDesc('priority')->orderBy('zone_name')->get()
            : collect();
        $preview = null;
        if (request()->filled('preview_lat') && request()->filled('preview_lon')) {
            $location = (object) [
                'lat' => (float) request('preview_lat'),
                'lon' => (float) request('preview_lon'),
                'fullAddress' => request('preview_address'),
                'country_code' => strtoupper((string) request('preview_country_code', '')),
            ];
            $offers = app(DeliveryZoneResolverService::class)->resolveOffers(
                $location,
                max(1, min(20, (int) request('preview_seller_count', 1))),
                max(0, (int) request('preview_total_sum', 0)),
            );
            $preview = [
                'location' => [
                    'lat' => $location->lat,
                    'lon' => $location->lon,
                    'address' => $location->fullAddress,
                    'country' => $location->country_code,
                ],
                'sellerCount' => max(1, min(20, (int) request('preview_seller_count', 1))),
                'totalSum' => max(0, (int) request('preview_total_sum', 0)),
                'offers' => collect($offers)->map(fn ($offer) => [
                    'service' => data_get($offer, 'service.name') ?: data_get($offer, 'delivery_service.name') ?: data_get($offer, 'name'),
                    'price' => (int) (data_get($offer, 'calculated_price') ?? data_get($offer, 'price') ?? data_get($offer, 'amount') ?? 0),
                    'etaDays' => data_get($offer, 'muddat') ?? data_get($offer, 'eta_days') ?? data_get($offer, 'etaDays'),
                    'codAllowed' => (bool) data_get($offer, 'cod_allowed', data_get($offer, 'codAllowed', false)),
                    'rule' => data_get($offer, 'rule.zone_name') ?: data_get($offer, 'zone_name'),
                ])->values()->all(),
            ];
        }

        return [
            'deliveryServices' => $services->map(fn (DeliveryService $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'price' => (int) ($service->priceKg ?? 0),
                'days' => (int) ($service->muddat ?? 0),
                'country' => $service->forCountry,
                'capital' => (bool) $service->capital,
                'freeFrom' => (int) ($service->freePriceFrom ?? 0),
                'active' => (bool) $service->status,
                'updateUrl' => route('boshqaruv.logistika.services.update', $service),
                'destroyUrl' => route('boshqaruv.logistika.services.destroy', $service),
            ])->values()->all(),
            'deliveryRules' => $rules->map(fn (DeliveryZoneRule $rule) => [
                'id' => $rule->id,
                'zoneName' => $rule->zone_name,
                'country' => $rule->country_code,
                'scope' => $rule->scope,
                'region' => $rule->region_name,
                'district' => $rule->district_name,
                'city' => $rule->city_name,
                'centerLat' => $rule->center_lat,
                'centerLon' => $rule->center_lon,
                'radiusKm' => $rule->radius_km,
                'deliveryServiceId' => $rule->delivery_service_id,
                'service' => $rule->deliveryService?->name,
                'priority' => (int) $rule->priority,
                'basePrice' => (int) ($rule->base_price ?? 0),
                'additionalSellerPercent' => (float) ($rule->additional_seller_percent ?? 0),
                'freePriceFrom' => (int) ($rule->free_price_from ?? 0),
                'etaDays' => (int) ($rule->eta_days ?? 0),
                'codAllowed' => (bool) $rule->cod_allowed,
                'active' => (bool) $rule->is_active,
                'notes' => $rule->notes,
                'updateUrl' => route('boshqaruv.logistika.update', $rule),
                'destroyUrl' => route('boshqaruv.logistika.destroy', $rule),
            ])->values()->all(),
            'logisticsStats' => [
                'services' => $services->count(),
                'activeServices' => $services->where('status', true)->count(),
                'rules' => $rules->count(),
                'codRules' => Schema::hasTable('delivery_zone_rules') ? DeliveryZoneRule::query()->active()->where('cod_allowed', true)->count() : 0,
            ],
            'logisticsFilters' => [
                'codFilter' => $codFilter,
                'previewLat' => request('preview_lat'),
                'previewLon' => request('preview_lon'),
                'previewAddress' => request('preview_address'),
                'previewCountry' => request('preview_country_code'),
                'previewSellerCount' => request('preview_seller_count', 1),
                'previewTotalSum' => request('preview_total_sum', 0),
            ],
            'logisticsPreview' => $preview,
            'logisticsActions' => [
                'ruleStoreUrl' => route('boshqaruv.logistika.store'),
                'serviceStoreUrl' => route('boshqaruv.logistika.services.store'),
            ],
        ];
    }

    private function mysteryBoxPayload(): array
    {
        $plans = [];
        $subscriptions = [];

        if (Schema::hasTable('mystery_box_plans')) {
            $plans = MysteryBoxPlan::query()
                ->withCount('subscriptions')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (MysteryBoxPlan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name_uz,
                    'months' => (int) ($plan->months ?? 0),
                    'price' => (int) ($plan->price_uzs ?? 0),
                    'booksPerMonth' => (int) ($plan->books_per_month ?? 0),
                    'active' => (bool) $plan->is_active,
                    'subscribers' => (int) ($plan->subscriptions_count ?? 0),
                    'plansUrl' => route('admin.mystery-box.plans'),
                    'destroyUrl' => route('admin.mystery-box.plans.destroy', $plan),
                ])
                ->values()
                ->all();
        }

        if (Schema::hasTable('mystery_box_subscriptions')) {
            $subscriptions = MysteryBoxSubscription::query()
                ->with(['user:id,name,lastname,phone_number', 'plan:id,name_uz'])
                ->latest()
                ->get()
                ->map(fn (MysteryBoxSubscription $subscription) => [
                    'id' => $subscription->id,
                    'user' => trim(($subscription->user?->name ?? '').' '.($subscription->user?->lastname ?? '')) ?: 'Mijoz',
                    'phone' => $subscription->user?->phone_number,
                    'plan' => $subscription->plan?->name_uz ?: '—',
                    'status' => $subscription->status,
                    'statusLabel' => $subscription->status_label,
                    'nextDelivery' => optional($subscription->next_delivery_at)->format('Y-m-d'),
                    'progress' => $subscription->progress_pct,
                    'showUrl' => route('admin.mystery-box.show', $subscription),
                    'pauseUrl' => route('admin.mystery-box.pause', $subscription),
                    'resumeUrl' => route('admin.mystery-box.resume', $subscription),
                    'cancelUrl' => route('admin.mystery-box.cancel', $subscription),
                ])
                ->values()
                ->all();
        }

        return [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'indexUrl' => route('admin.mystery-box.index'),
            'plansUrl' => route('admin.mystery-box.plans'),
        ];
    }

    private function bookClubPayload(): array
    {
        if (! Schema::hasTable('book_club')) {
            return [];
        }

        return BookClub::query()
            ->with(['user:id,name,lastname,avatar', 'activeWarning'])
            ->withCount(['likes', 'comments'])
            ->where('is_deleted', false)
            ->latest()
            ->get()
            ->map(fn (BookClub $post) => [
                'id' => $post->id,
                'author' => trim(($post->user?->name ?? '').' '.($post->user?->lastname ?? '')) ?: 'Kitobxon',
                'avatar' => $this->assetFromStorage($post->user?->avatar),
                'text' => $post->text,
                'productType' => $post->product_type,
                'productId' => $post->product_id,
                'repost' => (bool) $post->repost,
                'likes' => (int) ($post->likes_count ?? 0),
                'comments' => (int) ($post->comments_count ?? 0),
                'aiStatus' => $post->ai_post_status,
                'aiScore' => $post->ai_post_score,
                'aiNote' => $post->ai_post_note,
                'warning' => (bool) $post->activeWarning,
                'date' => optional($post->created_at)->format('Y-m-d H:i'),
                'dataUrl' => route('boshqaruv.book-club.data', $post),
                'warnUrl' => route('boshqaruv.book-club.warn', $post),
                'destroyUrl' => route('boshqaruv.book-club.destroy', $post),
            ])
            ->values()
            ->all();
    }

    private function bookClubDetailPayload(BookClub $post): array
    {
        $post->load([
            'user:id,name,lastname,avatar,phone_number',
            'originalAuthor:id,name,lastname,avatar',
            'images',
            'votes',
            'activeWarning',
        ]);

        $comments = BookClubComment::query()
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,lastname,avatar,phone_number',
                'replies.user:id,name,lastname,avatar,phone_number',
            ])
            ->withCount(['likes', 'replies'])
            ->latest()
            ->take(20)
            ->get();

        $likers = $post->likes()
            ->with('user:id,name,lastname,avatar,phone_number')
            ->latest()
            ->take(30)
            ->get();
        $likesCount = $post->likes()->count();

        $reposters = BookClub::query()
            ->where('repost', true)
            ->where('reposted_user_id', $post->user_id)
            ->when($post->product_id, fn ($query) => $query->where('product_id', $post->product_id))
            ->where('text', $post->text)
            ->with('user:id,name,lastname,avatar')
            ->latest()
            ->take(20)
            ->get();

        $totalVotes = $post->votes->sum(fn ($vote) => DB::table('book_club_voted_users')->where('option_id', $vote->id)->count());

        return [
            'post' => [
                'id' => $post->id,
                'author' => trim(($post->user?->name ?? '').' '.($post->user?->lastname ?? '')) ?: 'Kitobxon',
                'phone' => $post->user?->phone_number,
                'avatar' => $this->assetFromStorage($post->user?->avatar),
                'text' => $post->text,
                'repost' => (bool) $post->repost,
                'originalAuthor' => $post->originalAuthor ? trim(($post->originalAuthor->name ?? '').' '.($post->originalAuthor->lastname ?? '')) : null,
                'productType' => $post->product_type,
                'productId' => $post->product_id,
                'date' => $this->dateTime($post->created_at),
                'aiStatus' => $post->ai_post_status,
                'aiScore' => $post->ai_post_score !== null ? (float) $post->ai_post_score : null,
                'aiNote' => $post->ai_post_note,
                'aiModel' => $post->ai_post_model,
                'aiCheckedAt' => $this->dateTime($post->ai_post_checked_at),
                'warning' => $post->activeWarning ? [
                    'note' => $post->activeWarning->note,
                    'date' => $this->dateTime($post->activeWarning->created_at),
                ] : null,
                'images' => $post->images->map(fn ($image) => $this->assetFromStorage($image->image))->filter()->values()->all(),
            ],
            'stats' => [
                'likes' => $likesCount,
                'comments' => $post->comments()->count(),
                'reposts' => $reposters->count(),
                'votes' => $totalVotes,
            ],
            'product' => $this->bookClubProductPayload($post),
            'comments' => $comments->map(fn (BookClubComment $comment) => $this->bookClubCommentPayload($comment))->values()->all(),
            'likers' => $likers->map(fn ($like) => [
                'id' => $like->id,
                'userId' => $like->user_id,
                'name' => trim(($like->user?->name ?? '').' '.($like->user?->lastname ?? '')) ?: 'Foydalanuvchi',
                'phone' => $like->user?->phone_number,
                'avatar' => $this->assetFromStorage($like->user?->avatar),
                'date' => $this->dateTime($like->created_at),
            ])->values()->all(),
            'reposters' => $reposters->map(fn (BookClub $repost) => [
                'id' => $repost->id,
                'name' => trim(($repost->user?->name ?? '').' '.($repost->user?->lastname ?? '')) ?: 'Foydalanuvchi',
                'avatar' => $this->assetFromStorage($repost->user?->avatar),
                'date' => $this->dateTime($repost->created_at),
            ])->values()->all(),
            'votes' => $post->votes->map(function ($vote) use ($totalVotes) {
                $count = DB::table('book_club_voted_users')->where('option_id', $vote->id)->count();

                return [
                    'id' => $vote->id,
                    'text' => $vote->option_text,
                    'count' => $count,
                    'percent' => $totalVotes > 0 ? round($count / $totalVotes * 100) : 0,
                ];
            })->values()->all(),
            'actions' => [
                'warnUrl' => route('boshqaruv.book-club.warn', $post),
                'destroyUrl' => route('boshqaruv.book-club.destroy', $post),
            ],
        ];
    }

    private function bookClubCommentPayload(BookClubComment $comment): array
    {
        return [
            'id' => $comment->id,
            'userId' => $comment->user_id,
            'name' => trim(($comment->user?->name ?? '').' '.($comment->user?->lastname ?? '')) ?: 'Foydalanuvchi',
            'phone' => $comment->user?->phone_number,
            'avatar' => $this->assetFromStorage($comment->user?->avatar),
            'content' => $comment->content,
            'likes' => (int) ($comment->likes_count ?? 0),
            'repliesCount' => (int) ($comment->replies_count ?? 0),
            'date' => $this->dateTime($comment->created_at),
            'aiStatus' => $comment->ai_status,
            'aiScore' => $comment->ai_score !== null ? (float) $comment->ai_score : null,
            'aiNote' => $comment->ai_note,
            'aiModel' => $comment->ai_model,
            'aiCheckedAt' => $this->dateTime($comment->ai_checked_at),
            'hiddenByAi' => (bool) $comment->is_hidden_by_ai,
            'moderationStatus' => $comment->ai_moderation_status,
            'moderationNote' => $comment->ai_moderation_note,
            'kangarooStatus' => $comment->kangaroo_ugc_status,
            'kangarooScore' => $comment->kangaroo_star_equivalent,
            'updateUrl' => route('boshqaruv.book-club.comment.update', $comment),
            'destroyUrl' => route('boshqaruv.book-club.comment.delete', $comment),
            'replies' => $comment->replies->take(5)->map(fn (BookClubComment $reply) => [
                'id' => $reply->id,
                'name' => trim(($reply->user?->name ?? '').' '.($reply->user?->lastname ?? '')) ?: 'Foydalanuvchi',
                'avatar' => $this->assetFromStorage($reply->user?->avatar),
                'content' => $reply->content,
                'date' => $this->dateTime($reply->created_at),
                'destroyUrl' => route('boshqaruv.book-club.comment.delete', $reply),
            ])->values()->all(),
        ];
    }

    private function bookClubProductPayload(BookClub $post): ?array
    {
        if (! $post->product_id || ! in_array($post->product_type, ['book', 'stationery'], true)) {
            return null;
        }

        $product = $post->product_type === 'book'
            ? Books::query()->with('seller:id,shop_name,phone_number')->find($post->product_id)
            : Stationery::query()->with('seller:id,shop_name,phone_number')->find($post->product_id);

        $productComments = BookClubComment::query()
            ->whereHas('post', fn ($query) => $query
                ->where('product_id', $post->product_id)
                ->where('product_type', $post->product_type)
                ->where('is_deleted', false)
                ->where(function ($builder) {
                    $builder->where('repost', false)->orWhereNull('repost');
                }))
            ->with('user:id,name,lastname,avatar,phone_number')
            ->latest()
            ->take(8)
            ->get();

        $viewsQuery = Schema::hasTable('product_view_logs')
            ? ProductViewLog::query()->where('product_id', $post->product_id)->where('product_type', $post->product_type)
            : null;

        return [
            'id' => $post->product_id,
            'type' => $post->product_type,
            'name' => $product?->name ?? data_get($post->product_snapshot ?? [], 'name') ?? 'Mahsulot',
            'seller' => $product?->seller?->shop_name,
            'views' => (int) ($product?->views ?? 0),
            'viewLogs' => $viewsQuery ? (int) (clone $viewsQuery)->count() : 0,
            'recentViews' => $viewsQuery
                ? (clone $viewsQuery)->with('user:id,name,lastname,phone_number')->latest()->take(8)->get()->map(fn (ProductViewLog $view) => [
                    'id' => $view->id,
                    'user' => trim(($view->user?->name ?? '').' '.($view->user?->lastname ?? '')) ?: ($view->user_id ? 'Foydalanuvchi' : 'Mehmon'),
                    'phone' => $view->user?->phone_number,
                    'device' => $view->device_id,
                    'recommended' => (bool) $view->recommendation_active,
                    'date' => $this->dateTime($view->created_at),
                ])->values()->all()
                : [],
            'aiScore' => $product && Schema::hasColumn($product->getTable(), 'ugc_aggregate_score') ? (float) ($product->ugc_aggregate_score ?? 0) : null,
            'reviewsCount' => $product && Schema::hasColumn($product->getTable(), 'ugc_reviews_count') ? (int) ($product->ugc_reviews_count ?? 0) : $productComments->count(),
            'scoredAt' => $product && Schema::hasColumn($product->getTable(), 'ugc_last_scored_at') ? $this->dateTime($product->ugc_last_scored_at) : null,
            'comments' => $productComments->map(fn (BookClubComment $comment) => [
                'id' => $comment->id,
                'postId' => $comment->post_id,
                'name' => trim(($comment->user?->name ?? '').' '.($comment->user?->lastname ?? '')) ?: 'Foydalanuvchi',
                'content' => $comment->content,
                'aiScore' => $comment->ai_score !== null ? (float) $comment->ai_score : null,
                'aiStatus' => $comment->ai_status,
                'date' => $this->dateTime($comment->created_at),
            ])->values()->all(),
        ];
    }

    private function liveSnapshot(): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $week = $now->copy()->startOfWeek();
        $month = $now->copy()->startOfMonth();
        $activeOrderStatuses = ['pending', 'packing', 'in_delivery', 'A', 'P', 'B'];

        $orders = Sold::query();
        $todayOrdersQuery = Sold::query()->where('created_at', '>=', $today);
        $monthOrdersQuery = Sold::query()->where('created_at', '>=', $month);
        $totalRevenue = (float) $this->paidOrdersQuery()->sum('amount');
        $paidOrders = (int) $this->paidOrdersQuery()->count();
        $todayRevenue = (float) $this->paidOrdersQuery()->where('created_at', '>=', $today)->sum('amount');
        $monthRevenue = (float) $this->paidOrdersQuery()->where('created_at', '>=', $month)->sum('amount');
        $totalOrders = (int) $orders->count();
        $todayOrders = (int) (clone $todayOrdersQuery)->count();
        $weekOrders = (int) Sold::query()->where('created_at', '>=', $week)->count();
        $avgOrderValue = $paidOrders > 0 ? round($totalRevenue / $paidOrders) : 0;
        $activeOrders = $this->countStatuses(Sold::query(), $activeOrderStatuses);
        $cancelledOrders = $this->countStatuses(Sold::query(), ['cancelled', 'returned', 'F', 'R']);
        $completedOrders = $this->countStatuses(Sold::query(), ['customer_received', 'D']);
        $finance = $this->marketplaceFinancialSnapshot();
        $deliveryIncome = $finance['deliveryIncome'];
        $promoDiscount = $finance['promoDiscount'];
        $cashback = $finance['cashback'];
        $commission = $finance['commission'];
        $courierPayout = $finance['courierPayout'];
        $platformProfit = $finance['platformProfit'];
        $onlineUsers = Schema::hasColumn('users', 'last_seen_at')
            ? User::query()->where('last_seen_at', '>=', $now->copy()->subMinutes(5))->count()
            : 0;

        $mainCounts = [
            'all' => $totalOrders,
            'new' => $this->countStatuses(Sold::query(), ['pending', 'A']),
            'packing' => $this->countStatuses(Sold::query(), ['packing', 'P']),
            'onway' => $this->countStatuses(Sold::query(), ['in_delivery', 'B']),
            'arrived' => $this->countStatuses(Sold::query(), ['delivered', 'C']),
            'done' => $this->countStatuses(Sold::query(), ['customer_received', 'D']),
            'cancelled' => $cancelledOrders,
        ];

        $sellerCounts = [
            'all' => $this->tableCount('seller_orders'),
            'payment_pending' => $this->countStatuses(SellerOrder::query(), ['payment_pending']),
            'new' => $this->countStatuses(SellerOrder::query(), ['new']),
            'accepted' => $this->countStatuses(SellerOrder::query(), ['accepted']),
            'handover' => $this->countStatuses(SellerOrder::query(), ['handed_to_courier']),
            'cancelled' => $this->countStatuses(SellerOrder::query(), ['cancelled']),
        ];

        $courierCounts = [
            'all' => $this->tableCount('courier_orders'),
            'pay_process' => $this->countStatuses(CourierOrder::query(), ['payment_pending']),
            'pending' => $this->countStatuses(CourierOrder::query(), ['pending']),
            'in_delivery' => $this->countStatuses(CourierOrder::query(), ['in_delivery']),
            'delivered' => $this->countStatuses(CourierOrder::query(), ['delivered']),
            'customer_received' => $this->countStatuses(CourierOrder::query(), ['customer_received']),
            'rejected' => $this->countStatuses(CourierOrder::query(), ['cancelled', 'returned']),
        ];

        return [
            'generated_at' => $now->format('H:i:s'),
            'endpoint' => route('boshqaruv.live.data'),
            'kpis' => [
                'total_revenue' => $totalRevenue,
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'platform_profit' => $platformProfit,
                'total_orders' => $totalOrders,
                'paid_orders' => $paidOrders,
                'today_orders' => $todayOrders,
                'week_orders' => $weekOrders,
                'active_orders' => $activeOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'avg_order_value' => $avgOrderValue,
                'online_users' => (int) $onlineUsers,
                'delivery_income' => $deliveryIncome,
                'promo_discount' => $promoDiscount,
                'cashback' => $cashback,
                'commission' => $commission,
                'courier_payout' => $courierPayout,
                'manual_expenses' => $finance['manualExpenses'],
                'provider_fee' => $finance['providerFee'],
                'tax' => $finance['tax'],
                'completion_rate' => $totalOrders > 0 ? round($completedOrders / $totalOrders * 100, 1) : 0,
                'cancellation_rate' => $totalOrders > 0 ? round($cancelledOrders / $totalOrders * 100, 1) : 0,
                'paid_rate' => $totalOrders > 0 ? round($paidOrders / $totalOrders * 100, 1) : 0,
                'profit_margin' => $totalRevenue > 0 ? round($platformProfit / $totalRevenue * 100, 1) : 0,
            ],
            'main_counts' => $mainCounts,
            'seller_counts' => $sellerCounts,
            'courier_counts' => $courierCounts,
            'chart' => $this->liveHourlyChart($today),
            'regions' => $this->liveRegionStats(),
            'recent_orders' => $this->liveRecentOrders(),
            'recent_seller_orders' => $this->liveRecentSellerOrders(),
            'recent_courier_orders' => $this->liveRecentCourierOrders(),
            'online_users' => $this->liveOnlineUsers(),
            'top_products' => $this->liveTopProducts(),
            'alerts' => $this->liveAlerts($mainCounts, $sellerCounts, $courierCounts),
            'payment_split' => $this->paymentSplit([]),
            'delivery_split' => $this->deliverySplit(),
        ];
    }

    private function paidOrdersQuery()
    {
        return Sold::query()
            ->where(function ($query) {
                $query->where('status_code', 'customer_received')
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->whereIn('status', ['D', 'customer_received']);
                    });
            })
            ->where(function ($query) {
                $query->whereIn('payment_status_code', ['paid', 'success', 'completed'])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->whereIn('paymentStatus', ['2', 2, 'paid', 'success', 'completed', 'C', 'c']);
                    });
            });
    }

    private function customerReceivedOrdersQuery()
    {
        return $this->paidOrdersQuery();
    }

    private function customerReceivedCourierQuery()
    {
        return CourierOrder::query()->where(function ($query) {
            $query->where('status_code', 'customer_received')
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('status_code')
                        ->whereIn('status', ['D', 'customer_received']);
                });
        });
    }

    private function dashboardMonthlySales(): array
    {
        return collect(range(11, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $revenue = (float) $this->paidOrdersQuery()->whereBetween('created_at', [$start, $end])->sum('amount');
            $orders = (int) $this->paidOrdersQuery()->whereBetween('created_at', [$start, $end])->count();
            $finance = $this->marketplaceFinancialSnapshot($start, $end);

            return [
                'month' => $date->format('M'),
                'revenue' => $revenue,
                'profit' => $finance['platformProfit'],
                'orders' => $orders,
            ];
        })->values()->all();
    }

    private function dashboardCategoryShare(): array
    {
        $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#7c3aed', '#ef4444', '#14b8a6'];
        $totals = $this->paidOrderItemAggregates()['categories'];

        $sum = array_sum($totals);
        if ($sum <= 0) {
            return [];
        }

        $index = 0;

        return collect($totals)
            ->filter(fn ($value) => $value > 0)
            ->sortDesc()
            ->take(8)
            ->map(function ($value, $name) use (&$index, $colors, $sum) {
                $color = $colors[$index % count($colors)];
                $index++;

                return [
                    'name' => (string) $name,
                    'value' => round($value / $sum * 100, 1),
                    'revenue' => $value,
                    'color' => $color,
                ];
            })
            ->values()
            ->all();
    }

    private function countStatuses($query, array $statuses): int
    {
        return (int) $query
            ->where(function ($inner) use ($statuses) {
                $inner->whereIn('status_code', $statuses)
                    ->orWhere(function ($fallback) use ($statuses) {
                        $fallback->whereNull('status_code')->whereIn('status', $statuses);
                    });
            })
            ->count();
    }

    private function liveHourlyChart($today): array
    {
        return $this->customerReceivedOrdersQuery()
            ->where('created_at', '>=', $today)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders, COALESCE(SUM(amount), 0) as revenue')
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->pipe(fn ($rows) => collect(range(0, 23))->map(fn ($hour) => [
                'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'orders' => (int) ($rows[$hour]->orders ?? 0),
                'revenue' => (float) ($rows[$hour]->revenue ?? 0),
            ]))
            ->values()
            ->all();
    }

    private function liveRegionStats(): array
    {
        $colors = ['#a855f7', '#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#f43f5e'];
        $coords = [
            ['x' => 72, 'y' => 35], ['x' => 50, 'y' => 55], ['x' => 35, 'y' => 60], ['x' => 85, 'y' => 45],
            ['x' => 92, 'y' => 40], ['x' => 82, 'y' => 32], ['x' => 20, 'y' => 45], ['x' => 45, 'y' => 70],
        ];

        $rows = Cache::remember('boshqaruv.live.regions.v5.address_snapshot', now()->addMinute(), function () {
            $regions = [];
            $hasCollectionDiscountAmount = Schema::hasColumn('solds', 'collectionDiscountAmount');
            $selectColumns = ['id', 'address', 'recipient_region', 'amount', 'deliveryPrice', 'discountAmount', 'cashbackAmount'];
            if ($hasCollectionDiscountAmount) {
                $selectColumns[] = 'collectionDiscountAmount';
            }
            $this->customerReceivedOrdersQuery()
                ->whereNotNull('address')
                ->select($selectColumns)
                ->chunkById(500, function ($orders) use (&$regions) {
                    foreach ($orders as $order) {
                        $address = $this->primaryOrderAddressSnapshot($order->address ?? []);
                        $name = $this->regionNameFromOrderAddress($address, $order->recipient_region ?? null);
                        $regions[$name] ??= ['name' => $name, 'value' => 0, 'revenue' => 0.0, 'profit' => 0.0];
                        $regions[$name]['value']++;
                        $regions[$name]['revenue'] += (float) ($order->amount ?? 0);
                        $regions[$name]['profit'] += (float) ($order->deliveryPrice ?? 0)
                            - (float) ($order->discountAmount ?? 0)
                            - (float) ($order->collectionDiscountAmount ?? 0)
                            - (float) ($order->cashbackAmount ?? 0);
                    }
                });

            return collect($regions)->sortByDesc('value')->take(8)->values();
        });

        return $rows->map(fn ($row, $index) => [
            'name' => $row['name'],
            'value' => $row['value'],
            'revenue' => $row['revenue'],
            'profit' => $row['profit'],
            'color' => $colors[$index % count($colors)],
            'coords' => $coords[$index % count($coords)],
        ])->all();
    }

    private function primaryOrderAddressSnapshot(mixed $address): array
    {
        if (! is_array($address)) {
            return [];
        }

        $isList = array_is_list($address);
        if ($isList) {
            $first = collect($address)->first(fn ($row) => is_array($row));

            return is_array($first) ? $first : [];
        }

        return $address;
    }

    private function regionNameFromOrderAddress(array $address, mixed $fallbackRegion = null): string
    {
        $directFields = [
            'region_name',
            'recipient_region',
            'region',
            'province',
            'state',
            'regionSlug',
            'region_slug',
        ];

        foreach ($directFields as $field) {
            $direct = data_get($address, $field);
            if (! is_scalar($direct) || trim((string) $direct) === '') {
                continue;
            }

            $region = $this->canonicalAddressRegionFromText((string) $direct);
            if ($region !== null) {
                return $region;
            }
        }

        $fullAddress = data_get($address, 'fullAddress')
            ?: data_get($address, 'full_address')
            ?: data_get($address, 'address');

        if (is_scalar($fullAddress)) {
            $region = $this->canonicalAddressRegionFromText((string) $fullAddress);
            if ($region !== null) {
                return $region;
            }
        }

        if (is_scalar($fallbackRegion) && trim((string) $fallbackRegion) !== '') {
            $region = $this->canonicalAddressRegionFromText((string) $fallbackRegion);
            if ($region !== null) {
                return $region;
            }
        }

        foreach (['city_name', 'city', 'district_name', 'district'] as $field) {
            $value = data_get($address, $field);
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $region = $this->canonicalAddressRegionFromText((string) $value);
            if ($region !== null) {
                return $region;
            }
        }

        return 'Noma\'lum';
    }

    private function cleanAddressRegionName(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(['`', '‘', '’'], ["'", "'", "'"], $value)) ?: '');
    }

    private function canonicalAddressRegionFromText(string $value): ?string
    {
        $key = $this->normalizedAddressPartKey($value);
        if ($key === '' || $this->isCountryAddressKey($key)) {
            return null;
        }

        $aliases = $this->addressRegionAliases();
        $baseKey = $this->normalizedAddressBaseKey($value);
        if (isset($aliases[$key])) {
            return $aliases[$key];
        }
        if (isset($aliases[$baseKey])) {
            return $aliases[$baseKey];
        }

        foreach ($aliases as $alias => $label) {
            if ($alias !== '' && str_contains($key, $alias)) {
                return $label;
            }
        }

        return null;
    }

    private function addressRegionAliases(): array
    {
        return [
            'toshkentshahri' => 'Toshkent shahri',
            'tashkentcity' => 'Toshkent shahri',
            'городташкент' => 'Toshkent shahri',
            'гташкент' => 'Toshkent shahri',
            'toshkentviloyati' => 'Toshkent viloyati',
            'tashkentregion' => 'Toshkent viloyati',
            'ташкентская' => 'Toshkent viloyati',
            'ташкентскаяобласть' => 'Toshkent viloyati',
            'ташкентобласть' => 'Toshkent viloyati',
            'toshkent' => 'Toshkent shahri',
            'tashkent' => 'Toshkent shahri',
            'ташкент' => 'Toshkent shahri',
            'andijon' => 'Andijon viloyati',
            'andijan' => 'Andijon viloyati',
            'андижан' => 'Andijon viloyati',
            'андижанская' => 'Andijon viloyati',
            'buxoro' => 'Buxoro viloyati',
            'bukhara' => 'Buxoro viloyati',
            'бухара' => 'Buxoro viloyati',
            'бухарская' => 'Buxoro viloyati',
            'fargona' => "Farg'ona viloyati",
            'fergana' => "Farg'ona viloyati",
            'ferghana' => "Farg'ona viloyati",
            'фергана' => "Farg'ona viloyati",
            'ферганская' => "Farg'ona viloyati",
            'jizzax' => 'Jizzax viloyati',
            'jizzakh' => 'Jizzax viloyati',
            'джизак' => 'Jizzax viloyati',
            'джизакская' => 'Jizzax viloyati',
            'xorazm' => 'Xorazm viloyati',
            'khorezm' => 'Xorazm viloyati',
            'хорезм' => 'Xorazm viloyati',
            'хорезмская' => 'Xorazm viloyati',
            'namangan' => 'Namangan viloyati',
            'наманган' => 'Namangan viloyati',
            'наманганская' => 'Namangan viloyati',
            'navoiy' => 'Navoiy viloyati',
            'navoi' => 'Navoiy viloyati',
            'навоий' => 'Navoiy viloyati',
            'навоийская' => 'Navoiy viloyati',
            'qashqadaryo' => 'Qashqadaryo viloyati',
            'qashqadaryooblast' => 'Qashqadaryo viloyati',
            'kashkadarya' => 'Qashqadaryo viloyati',
            'кашкадарья' => 'Qashqadaryo viloyati',
            'кашкадарьинская' => 'Qashqadaryo viloyati',
            'qoraqalpogiston' => "Qoraqalpog'iston Respublikasi",
            'qoraqalpogistonrespublikasi' => "Qoraqalpog'iston Respublikasi",
            'karakalpakstan' => "Qoraqalpog'iston Respublikasi",
            'karakalpakstanrepublic' => "Qoraqalpog'iston Respublikasi",
            'republicofkarakalpakstan' => "Qoraqalpog'iston Respublikasi",
            'каракалпакстан' => "Qoraqalpog'iston Respublikasi",
            'республикакаракалпакстан' => "Qoraqalpog'iston Respublikasi",
            'samarqand' => 'Samarqand viloyati',
            'samarkand' => 'Samarqand viloyati',
            'самарканд' => 'Samarqand viloyati',
            'самаркандская' => 'Samarqand viloyati',
            'sirdaryo' => 'Sirdaryo viloyati',
            'syrdarya' => 'Sirdaryo viloyati',
            'сырдарья' => 'Sirdaryo viloyati',
            'сырдарьинская' => 'Sirdaryo viloyati',
            'surxondaryo' => 'Surxondaryo viloyati',
            'surkhandarya' => 'Surxondaryo viloyati',
            'сурхандарья' => 'Surxondaryo viloyati',
            'сурхандарьинская' => 'Surxondaryo viloyati',
        ];
    }

    private function isCountryAddressKey(string $key): bool
    {
        return in_array($key, [
            'ozbekiston', 'uzbekiston', 'uzbekistan', 'uzb',
            'ozbekistonrespublikasi', 'uzbekistonrespublikasi', 'republicofuzbekistan',
            'узбекистан', 'республикаузбекистан',
        ], true);
    }

    private function normalizedAddressPartKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(["'", 'ʻ', 'ʼ', '`', '‘', '’', '.', ','], '')
            ->replace([' ', '-', '_'], '')
            ->value();
    }

    private function normalizedAddressBaseKey(string $value): string
    {
        return Str::of($this->normalizedAddressPartKey($value))
            ->replace([
                'viloyati', 'viloyat', 'region', 'oblast', 'область', 'обл',
                'shahri', 'shahar', 'city', 'город',
            ], '')
            ->value();
    }

    private function liveRecentOrders(): array
    {
        return Sold::query()
            ->with('user:id,name,lastname,avatar,phone_number')
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (Sold $order) => [
                'id' => $order->id,
                'title' => '#'.$order->id.' buyurtma',
                'customer' => trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) ?: 'Mehmon',
                'phone' => $order->user?->phone_number,
                'avatar' => $this->assetFromStorage($order->user?->avatar),
                'amount' => (float) ($order->amount ?? 0),
                'status' => $this->orderStatusLabel((string) ($order->status_code ?? $order->status ?? '')),
                'status_code' => (string) ($order->status_code ?? $order->status ?? ''),
                'updated_at' => optional($order->updated_at)->diffForHumans(),
                'url' => route('boshqaruv.orders', ['orders_search' => $order->id, 'orders_tab' => 'all']),
            ])
            ->values()
            ->all();
    }

    private function liveRecentSellerOrders(): array
    {
        if (! Schema::hasTable('seller_orders')) {
            return [];
        }

        return SellerOrder::query()
            ->with(['seller:id,shop_name,firstname,lastname,photo', 'client:id,name,lastname,avatar'])
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (SellerOrder $order) => [
                'id' => $order->id,
                'seller' => $order->seller?->shop_name ?: trim(($order->seller?->firstname ?? '').' '.($order->seller?->lastname ?? '')) ?: 'Sotuvchi',
                'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: 'Mijoz',
                'avatar' => $this->assetFromStorage($order->seller?->photo),
                'amount' => (float) ($order->amount ?? 0),
                'status' => $this->sellerStatusLabel((string) ($order->status_code ?? $order->status ?? '')),
                'status_code' => (string) ($order->status_code ?? $order->status ?? ''),
                'updated_at' => optional($order->updated_at)->diffForHumans(),
                'url' => route('boshqaruv.seller-orders'),
            ])
            ->values()
            ->all();
    }

    private function liveRecentCourierOrders(): array
    {
        if (! Schema::hasTable('courier_orders')) {
            return [];
        }

        return CourierOrder::query()
            ->with(['courier:id,first_name,last_name,photo', 'user:id,name,lastname,avatar'])
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (CourierOrder $order) => [
                'id' => $order->id,
                'courier' => trim(($order->courier?->first_name ?? '').' '.($order->courier?->last_name ?? '')) ?: 'Tayinlanmagan',
                'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
                'avatar' => $this->assetFromStorage($order->courier?->photo),
                'amount' => (float) ($order->amount ?? 0),
                'status' => $this->orderStatusLabel((string) ($order->status_code ?? $order->status ?? '')),
                'status_code' => (string) ($order->status_code ?? $order->status ?? ''),
                'updated_at' => optional($order->updated_at)->diffForHumans(),
                'url' => route('boshqaruv.courier-orders'),
            ])
            ->values()
            ->all();
    }

    private function liveOnlineUsers(): array
    {
        if (! Schema::hasColumn('users', 'last_seen_at')) {
            return [];
        }

        return User::query()
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->select('id', 'name', 'lastname', 'avatar', 'last_seen_at')
            ->latest('last_seen_at')
            ->take(12)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: 'Foydalanuvchi',
                'avatar' => $this->assetFromStorage($user->avatar),
                'last_seen' => optional($user->last_seen_at)->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    private function liveTopProducts(): array
    {
        $products = collect($this->paidOrderItemAggregates()['products'])
            ->sortByDesc('quantity')
            ->take(5)
            ->values();

        return $products->all();
    }

    private function paidOrderItemAggregates(): array
    {
        return Cache::remember('boshqaruv.live.item-aggregates.v3.category-sales', now()->addMinute(), function () {
            $categories = [];
            $products = [];
            $bookCategoryNames = $this->bookCategoryNameMap();

            $this->paidOrdersQuery()
                ->whereNotNull('items')
                ->select(['id', 'items'])
                ->chunkById(500, function ($orders) use (&$categories, &$products, $bookCategoryNames) {
                    foreach ($orders as $order) {
                        foreach (collect($order->items ?? []) as $item) {
                            $type = Str::lower((string) ($item['type'] ?? 'book'));
                            if ($type === 'gift') {
                                continue;
                            }

                            $id = (int) ($item['item_id'] ?? $item['product_id'] ?? 0);
                            $name = (string) ($item['name'] ?? 'Mahsulot');
                            $quantity = (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? 1);
                            $revenue = $quantity * (float) ($item['item_price'] ?? $item['price'] ?? 0);
                            $key = $type.':'.$id.':'.$name;
                            $category = match ($type) {
                                'book' => $bookCategoryNames[$id] ?? 'Kitob: Kategoriyasiz',
                                'stationery' => 'Kanselyariya',
                                default => 'Boshqa',
                            };

                            $categories[$category] ??= 0.0;
                            $categories[$category] += $revenue;
                            $products[$key] ??= ['name' => $name, 'quantity' => 0, 'revenue' => 0.0];
                            $products[$key]['quantity'] += $quantity;
                            $products[$key]['revenue'] += $revenue;
                        }
                    }
                });

            return ['categories' => $categories, 'products' => array_values($products)];
        });
    }

    private function bookCategoryNameMap(): array
    {
        if (! Schema::hasTable('book_categories')) {
            return [];
        }

        return Books::query()
            ->leftJoin('book_categories', 'books.category_id', '=', 'book_categories.id')
            ->select([
                'books.id as book_id',
                'book_categories.name_uz',
                'book_categories.name_ru',
                'book_categories.name_en',
                'book_categories.slug',
            ])
            ->get()
            ->mapWithKeys(function ($row) {
                $name = $row->name_uz ?: $row->name_ru ?: $row->name_en ?: $row->slug ?: 'Kitob: Kategoriyasiz';

                return [(int) $row->book_id => (string) $name];
            })
            ->all();
    }

    private function liveAlerts(array $mainCounts, array $sellerCounts, array $courierCounts): array
    {
        $alerts = [];
        if (($mainCounts['new'] ?? 0) > 0) {
            $alerts[] = ['level' => 'warning', 'icon' => 'bi-bag-check', 'title' => 'Yangi buyurtmalar', 'text' => $mainCounts['new'].' ta buyurtma ishlov kutmoqda', 'url' => route('boshqaruv.orders')];
        }
        if (($sellerCounts['new'] ?? 0) > 0) {
            $alerts[] = ['level' => 'info', 'icon' => 'bi-shop-window', 'title' => 'Seller navbati', 'text' => $sellerCounts['new'].' ta seller order qabul kutmoqda', 'url' => route('boshqaruv.seller-orders')];
        }
        if (($courierCounts['pending'] ?? 0) > 0) {
            $alerts[] = ['level' => 'warning', 'icon' => 'bi-bicycle', 'title' => 'Kuryer navbati', 'text' => $courierCounts['pending'].' ta kuryer order kutilmoqda', 'url' => route('boshqaruv.courier-orders')];
        }
        if ($this->tableCount('bot_tickets') > 0) {
            $queued = Schema::hasColumn('bot_tickets', 'status') ? DB::table('bot_tickets')->where('status', 'queue')->count() : 0;
            if ($queued > 0) {
                $alerts[] = ['level' => 'danger', 'icon' => 'bi-headset', 'title' => 'Support navbati', 'text' => $queued.' ta murojaat javob kutmoqda', 'url' => route('boshqaruv.tickets')];
            }
        }

        return array_slice($alerts, 0, 6);
    }

    private function paymentSplit(array $paidStatuses): array
    {
        $total = max(1, Sold::query()->count());
        $paid = $paidStatuses
            ? Sold::query()->where(fn ($query) => $query->whereIn('payment_status_code', $paidStatuses)->orWhereIn('paymentStatus', $paidStatuses))->count()
            : $this->paidOrdersQuery()->count();

        $cancelled = $this->countStatuses(Sold::query(), ['cancelled', 'returned', 'F', 'R']);
        $pending = max(0, $total - $paid - $cancelled);

        return [
            ['name' => 'To\'langan', 'count' => (int) $paid, 'share' => round($paid / $total * 100, 1), 'color' => '#10b981'],
            ['name' => 'Kutilmoqda', 'count' => $pending, 'share' => round($pending / $total * 100, 1), 'color' => '#f59e0b'],
            ['name' => 'Bekor / qaytgan', 'count' => $cancelled, 'share' => round($cancelled / $total * 100, 1), 'color' => '#ef4444'],
        ];
    }

    private function deliverySplit(): array
    {
        return $this->paidOrdersQuery()
            ->selectRaw('deliveryType, COUNT(*) as count, COALESCE(SUM(amount), 0) as revenue')
            ->groupBy('deliveryType')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->deliveryType ?: 'delivery',
                'count' => (int) $row->count,
                'revenue' => (float) $row->revenue,
            ])
            ->values()
            ->all();
    }

    private function recentBookOrders(Books $book): array
    {
        return $this->recentProductOrders('book', (int) $book->id);
    }

    private function recentProductOrders(string $type, int $productId): array
    {
        return Sold::query()
            ->with('user:id,name,lastname,phone_number')
            ->latest()
            ->take(500)
            ->get()
            ->filter(fn (Sold $order) => $this->orderContainsProduct($order->items ?? [], $type, $productId))
            ->take(10)
            ->map(fn (Sold $order) => [
                'id' => $order->id,
                'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
                'phone' => $order->user?->phone_number,
                'amount' => (float) ($order->amount ?? 0),
                'status' => (string) ($order->status_code ?? $order->status ?? ''),
                'payment' => (string) ($order->payment_status_code ?? $order->paymentStatus ?? ''),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'url' => route('boshqaruv.orders', ['orders_search' => $order->id, 'orders_tab' => 'all']),
            ])
            ->values()
            ->all();
    }

    private function recentSellerOrdersForBook(Books $book): array
    {
        return $this->recentSellerOrdersForProduct('book', (int) $book->id);
    }

    private function recentSellerOrdersForProduct(string $type, int $productId): array
    {
        if (! Schema::hasTable('seller_order_items') || ! Schema::hasTable('seller_orders')) {
            return [];
        }

        return SellerOrder::query()
            ->with(['client:id,name,lastname,phone_number', 'seller:id,shop_name'])
            ->whereIn('id', SellerOrderItem::query()
                ->select('order_id')
                ->where('type', $type)
                ->where('product_id', $productId))
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (SellerOrder $order) => [
                'id' => $order->id,
                'seller' => $order->seller?->shop_name,
                'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: 'Mijoz',
                'phone' => $order->client?->phone_number,
                'amount' => (float) ($order->amount ?? 0),
                'status' => (string) ($order->status_code ?? $order->status ?? ''),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'url' => route('boshqaruv.seller-orders', ['seller_orders_search' => $order->id, 'seller_orders_tab' => 'all']),
            ])
            ->values()
            ->all();
    }

    private function orderContainsProduct(array $items, string $type, int $productId): bool
    {
        return collect($items)->contains(function ($item) use ($type, $productId) {
            $item = (array) $item;
            $itemType = (string) ($item['type'] ?? 'book');
            $itemId = (int) ($item['item_id'] ?? $item['product_id'] ?? $item['id'] ?? 0);

            return $itemType === $type && $itemId === $productId;
        });
    }

    private function orderPayload(Sold $order): array
    {
        $panelAdmin = Auth::guard('panel')->user();
        $canModerateRefunds = (bool) ($panelAdmin?->isAdmin() ?? false);
        $sellerOrderModels = Schema::hasTable('seller_orders')
            ? SellerOrder::query()
                ->with(['seller:id,shop_name,phone_number,commission_percent', 'courier:id,first_name,last_name,phone_number,region'])
                ->where('order_id', $order->id)
                ->latest('id')
                ->get()
            : collect();
        $sellerOrderItemModels = Schema::hasTable('seller_order_items') && $sellerOrderModels->isNotEmpty()
            ? SellerOrderItem::query()
                ->with([
                    'book:id,name,author,images',
                    'stationery:id,name,images',
                    'gift:id,name,images',
                    'variant:id,color_name,image_path,stock',
                ])
                ->whereIn('order_id', $sellerOrderModels->pluck('id')->all())
                ->orderBy('id')
                ->get()
            : collect();
        $address = collect($order->address ?? [])->values()->map(fn ($item) => $this->orderAddressPayload((array) $item));
        $primaryAddress = (array) ($address->first() ?? []);
        $fulfillment = $order->fulfillment;
        $paymentTransaction = Schema::hasTable('transactions') ? Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->latest('id')
            ->first() : null;
        $canProcessRefunds = $this->canProcessOperationalRefunds($order, $paymentTransaction);
        $items = $sellerOrderItemModels->isNotEmpty()
            ? $sellerOrderItemModels->map(fn (SellerOrderItem $item) => $this->sellerOrderItemPayload($item, $canModerateRefunds && $canProcessRefunds))->values()
            : collect($order->items ?? [])->map(fn ($item) => $this->orderItemPayload((array) $item))->values();
        $sellerTransactions = Schema::hasTable('seller_transactions') ? SellerTransaction::query()
            ->where('order_id', $order->id)
            ->get() : collect();
        $courierOrder = Schema::hasTable('courier_orders') ? CourierOrder::query()
            ->with('courier:id,first_name,last_name,phone_number,region')
            ->where('order_id', $order->id)
            ->latest('id')
            ->first() : null;
        $courierTask = Schema::hasTable('courier_tasks') ? CourierTask::query()
            ->where('order_id', $order->id)
            ->when($courierOrder?->courier_id, fn ($query) => $query->where('courier_id', $courierOrder->courier_id))
            ->latest('id')
            ->first() : null;
        $refunds = Schema::hasTable('order_refunds')
            ? OrderRefund::query()
                ->where('order_id', $order->id)
                ->latest('id')
                ->get()
            : collect();

        $paymentCard = null;
        if ($paymentTransaction && filled($paymentTransaction->provider_card_id) && Schema::hasTable('user_cards')) {
            $paymentCard = UserCard::query()
                ->where('provider_card_id', $paymentTransaction->provider_card_id)
                ->first();
        }
        $paymentCardSnapshot = data_get($paymentTransaction?->provider_response, 'card_snapshot', []);
        $paymentCardView = [
            'provider' => $paymentTransaction?->provider,
            'providerCardId' => $paymentTransaction?->provider_card_id,
            'maskedNumber' => $paymentCard?->card_number ?: ($paymentCardSnapshot['masked_number'] ?? null),
            'vendor' => $paymentCard?->vendor ?: ($paymentCardSnapshot['vendor'] ?? null),
            'cardName' => $paymentCard?->card_name ?: ($paymentCardSnapshot['card_name'] ?? null),
            'phone' => $paymentCard?->phone_number ?: ($paymentCardSnapshot['phone_number'] ?? null),
        ];

        $sellerSettlements = [];
        $settlementOverview = [
            'gross' => 0,
            'commission' => 0,
            'net' => 0,
            'reversedNet' => 0,
            'currentNet' => 0,
            'saleCount' => 0,
            'reversalCount' => 0,
            'transactions' => (int) $sellerTransactions->count(),
            'status' => 'pending',
            'label' => 'Hisob-kitob kutilmoqda',
        ];
        foreach ($sellerOrderModels as $sellerOrder) {
            $transactions = $sellerTransactions->where('seller_order_id', $sellerOrder->id)->values();
            $saleTransactions = $transactions
                ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_SALE)
                ->where('status', SellerTransaction::STATUS_APPROVED)
                ->values();
            $reversalTransactions = $transactions
                ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_REVERSAL)
                ->where('status', SellerTransaction::STATUS_APPROVED)
                ->values();

            $gross = (int) $saleTransactions->sum('amount');
            $commission = (int) $saleTransactions->sum('commissionPrice');
            $net = (int) $saleTransactions->sum('netAmount');
            $reversedNet = (int) $reversalTransactions->sum('netAmount');
            $currentNet = $net - $reversedNet;
            $saleCount = $saleTransactions->count();
            $reversalCount = $reversalTransactions->count();
            $status = 'pending';
            $label = 'Hisob-kitob kutilmoqda';
            if ($saleCount > 0 && $currentNet > 0) {
                $status = 'settled';
                $label = 'Sellerga tushgan';
            } elseif ($saleCount > 0 && $currentNet <= 0) {
                $status = 'reversed';
                $label = 'Hisob-kitob qaytarilgan';
            }

            $sellerSettlements[$sellerOrder->id] = [
                'gross' => $gross,
                'commission' => $commission,
                'net' => $net,
                'reversedNet' => $reversedNet,
                'currentNet' => $currentNet,
                'saleCount' => $saleCount,
                'reversalCount' => $reversalCount,
                'status' => $status,
                'label' => $label,
                'latestSaleAt' => $this->dateTime($saleTransactions->last()?->created_at),
                'latestReversalAt' => $this->dateTime($reversalTransactions->last()?->created_at),
            ];

            $settlementOverview['gross'] += $gross;
            $settlementOverview['commission'] += $commission;
            $settlementOverview['net'] += $net;
            $settlementOverview['reversedNet'] += $reversedNet;
            $settlementOverview['saleCount'] += $saleCount;
            $settlementOverview['reversalCount'] += $reversalCount;
        }
        $settlementOverview['currentNet'] = $settlementOverview['net'] - $settlementOverview['reversedNet'];
        if ($settlementOverview['saleCount'] > 0 && $settlementOverview['currentNet'] > 0) {
            $settlementOverview['status'] = 'settled';
            $settlementOverview['label'] = 'Sellerga tushgan';
        } elseif ($settlementOverview['saleCount'] > 0 && $settlementOverview['currentNet'] <= 0) {
            $settlementOverview['status'] = 'reversed';
            $settlementOverview['label'] = 'Hisob-kitob qaytarilgan';
        }

        $sellerOrders = $sellerOrderModels
            ->map(fn (SellerOrder $sellerOrder) => [
                'id' => $sellerOrder->id,
                'sellerId' => $sellerOrder->seller_id,
                'seller' => $sellerOrder->seller?->shop_name,
                'sellerPhone' => $sellerOrder->seller?->phone_number,
                'sellerCommissionPercent' => (int) ($sellerOrder->seller?->commission_percent ?? 0),
                'courier' => trim(($sellerOrder->courier?->first_name ?? '').' '.($sellerOrder->courier?->last_name ?? '')) ?: ($sellerOrder->courierName ?? null),
                'courierPhone' => $sellerOrder->courier?->phone_number,
                'courierRegion' => $sellerOrder->courier?->region,
                'amount' => (float) ($sellerOrder->amount ?? 0),
                'deliveryType' => $sellerOrder->delivery_type,
                'status' => (string) ($sellerOrder->status_code ?? $sellerOrder->status ?? ''),
                'acceptedAt' => $this->dateTime($sellerOrder->accepted_at),
                'createdAt' => $this->dateTime($sellerOrder->created_at),
                'address' => $this->orderAddressPayload((array) ($sellerOrder->address ?? [])),
                'settlement' => $sellerSettlements[$sellerOrder->id] ?? null,
                'url' => route('boshqaruv.seller-orders'),
                'isCancelled' => $sellerOrder->cancelled_at !== null,
                'cancelledAt' => $this->dateTime($sellerOrder->cancelled_at),
                'cancelReasonCode' => $sellerOrder->cancel_reason_code,
                'cancelNotes' => [
                    'uz' => $sellerOrder->cancel_note_uz,
                    'ru' => $sellerOrder->cancel_note_ru,
                    'en' => $sellerOrder->cancel_note_en,
                    'ja' => $sellerOrder->cancel_note_ja,
                ],
                'refundStatus' => $sellerOrder->refund_status,
                'canRefund' => $canModerateRefunds && $canProcessRefunds && $sellerOrder->cancelled_at === null,
                'refundUrl' => route('boshqaruv.seller-orders.refund', $sellerOrder),
            ])
            ->values()
            ->all();
        $canRefundPayment = $panelAdmin?->isSuperAdmin()
            && ($paymentTransaction?->provider === 'paylov')
            && in_array(PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus), [PaymentStatusCode::HELD, PaymentStatusCode::PAID], true)
            && ! in_array((string) ($order->status_code ?? $order->status), [OrderStatusCode::CANCELLED->value, OrderStatusCode::CANCELLED->legacy()], true);
        $refundConfirmationPhrase = null;
        if ($canRefundPayment) {
            $refundConfirmationPhrase = 'QAYTAR-'.Str::upper(Str::random(3)).'-'.random_int(10, 99);
            session()->put("admin.order_refund_phrase.{$order->id}", $refundConfirmationPhrase);
        }

        $activeItems = $items
            ->reject(fn ($item) => (bool) ($item['isCancelled'] ?? false))
            ->values();

        return [
            'id' => '#'.$order->id,
            'rawId' => $order->id,
            'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
            'user' => $order->user ? [
                'id' => $order->user->id,
                'name' => trim(($order->user->name ?? '').' '.($order->user->lastname ?? '')),
                'phone' => $order->user->phone_number,
                'email' => $order->user->email,
                'url' => route('boshqaruv.users'),
            ] : null,
            'userReputation' => $order->user ? [
                'score' => round((float) ($order->user->reputation_score ?? \App\Services\UserReputationService::BASELINE_SCORE), 2),
                'cashOnDeliveryAllowed' => (bool) ($order->user->cash_on_delivery_allowed ?? true),
                'codReturnStrikes' => (int) ($order->user->cod_return_strikes ?? 0),
                'cashOnDeliveryBlockReason' => ($order->user->cash_on_delivery_allowed ?? true)
                    ? null
                    : "Avvalgi qaytgan naqd buyurtma sabab mijoz uchun naqd to'lov vaqtincha yopilgan.",
            ] : null,
            'items' => (int) $activeItems->sum(fn ($item) => (int) ($item['quantity'] ?? 1)),
            'itemsList' => $items->all(),
            'total' => (float) ($order->amount ?? 0),
            'subtotal' => (float) $activeItems->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * (int) ($item['quantity'] ?? 1)),
            'deliveryPrice' => (float) ($order->deliveryPrice ?? 0),
            'discountAmount' => (float) (($order->discountAmount ?? 0) + ($order->collectionDiscountAmount ?? 0)),
            'cashbackAmount' => (float) ($order->cashbackAmount ?? 0),
            'withCashback' => (bool) ($order->withCashback ?? false),
            'awardedCashbackAmount' => (float) ($order->awarded_cashback_amount ?? 0),
            'cashbackReadyAt' => $this->dateTime($order->cashback_ready_at),
            'cashbackAwardedAt' => $this->dateTime($order->cashback_awarded_at),
            'cashbackNotifiedAt' => $this->dateTime($order->cashback_notified_at),
            'giftCertAmount' => (float) ($order->giftCertAmount ?? 0),
            'giftCertificateId' => $order->gift_certificate_id,
            'packagingPrice' => (float) ($order->packaging_price ?? 0),
            'status' => OrderStatusCode::fromLegacy($order->status_code ?? $order->status)->value,
            'legacyStatus' => (string) ($order->status ?? ''),
            'date' => optional($order->created_at)->format('Y-m-d H:i'),
            'completedAt' => optional($order->completed_at)->format('Y-m-d H:i'),
            'payment' => (string) ($order->paymentStatus ?? $order->payment_status_code ?? '—'),
            'paymentStatus' => (string) ($order->payment_status_code ?? $order->paymentStatus ?? '—'),
            'deliveryType' => $order->deliveryType,
            'orderKind' => $order->order_kind,
            'postalReturnStatus' => $order->postal_return_status,
            'postalReturnFee' => (float) ($order->postal_return_fee ?? 0),
            'postalReturnNote' => $order->postal_return_note,
            'resendSourceOrderId' => $order->resend_source_order_id,
            'resendReplacementOrderId' => $order->resend_replacement_order_id,
            'resendAvailableAt' => optional($order->resend_available_at)->format('Y-m-d H:i'),
            'returnFlow' => [
                'isPostal' => $order->deliveryType === 'postal',
                'isReturned' => OrderStatusCode::fromLegacy($order->status_code ?? $order->status) === OrderStatusCode::RETURNED,
                'penaltyAmount' => (float) ($order->postal_return_fee ?? 0),
                'canCreateReplacement' => $order->isPostalResendSource(),
                'replacementOrderId' => $order->resend_replacement_order_id,
                'availableAt' => optional($order->resend_available_at)->format('Y-m-d H:i'),
                'note' => $order->postal_return_note,
            ],
            'address' => $primaryAddress,
            'addresses' => $address->all(),
            'isInstore' => (bool) ($order->is_instore ?? false),
            'withPackaging' => (bool) ($order->with_packaging ?? false),
            'isGiftToOther' => (bool) ($order->is_gift_to_other ?? false),
            'recipient' => [
                'name' => $order->recipient_name,
                'phone' => $order->recipient_phone,
                'region' => $order->recipient_region,
                'address' => $order->recipient_address,
            ],
            'buyerWish' => $order->buyerWish,
            'promocode' => $order->promocode,
            'courierName' => $order->courierName,
            'fulfillment' => $fulfillment ? [
                'mode' => $fulfillment->fulfillment_mode,
                'status' => $fulfillment->status_code,
                'hub' => $fulfillment->hub?->name ?? $fulfillment->hub?->code,
                'firstMile' => $fulfillment->first_mile_mode,
                'lastMile' => $fulfillment->last_mile_mode,
                'isCod' => (bool) $fulfillment->is_cod,
                'cashCollectAmount' => (float) ($fulfillment->cash_collect_amount ?? 0),
                'tracking' => $fulfillment->postal_tracking_number,
                'labelCode' => $fulfillment->label_code,
                'notes' => $fulfillment->notes,
                'routingVersion' => $fulfillment->routing_version,
                'routingSnapshot' => $fulfillment->routing_snapshot,
                'lastModeSwitch' => collect(data_get($fulfillment->meta ?? [], 'mode_switch_log', []))->last(),
                'lastHubReroute' => collect(data_get($fulfillment->meta ?? [], 'hub_reroute_log', []))->last(),
                'timeline' => $this->orderFulfillmentTimeline($fulfillment),
            ] : null,
            'paymentTransaction' => $paymentTransaction ? [
                'id' => $paymentTransaction->id,
                'provider' => $paymentTransaction->provider,
                'providerCardId' => $paymentTransaction->provider_card_id,
                'amount' => (float) ($paymentTransaction->amount ?? 0),
                'status' => $paymentTransaction->status,
                'date' => $this->dateTime($paymentTransaction->created_at),
            ] : null,
            'paymentCard' => $paymentCardView,
            'settlementOverview' => $settlementOverview,
            'canRefundPayment' => (bool) $canRefundPayment,
            'refundConfirmationPhrase' => $refundConfirmationPhrase,
            'refundReasonCatalog' => [
                'item' => SellerCancellationReasonCatalog::itemOptions(),
                'order' => SellerCancellationReasonCatalog::orderOptions(),
            ],
            'refundLedger' => $refunds->map(fn (OrderRefund $refund) => [
                'id' => $refund->id,
                'type' => $refund->type,
                'status' => $refund->status,
                'cardRefundAmount' => (float) ($refund->card_refund_amount ?? 0),
                'cashbackRestoreAmount' => (float) ($refund->cashback_restore_amount ?? 0),
                'giftCertRestoreAmount' => (float) ($refund->gift_cert_restore_amount ?? 0),
                'deliveryRefundAmount' => (float) ($refund->delivery_refund_amount ?? 0),
                'packagingRefundAmount' => (float) ($refund->packaging_refund_amount ?? 0),
                'reasonCode' => $refund->reason_code,
                'reasonNoteUz' => $refund->reason_note_uz,
                'processedAt' => $this->dateTime($refund->processed_at),
            ])->values()->all(),
            'courierOrder' => $courierOrder ? [
                'id' => $courierOrder->id,
                'courier' => trim(($courierOrder->courier?->first_name ?? '').' '.($courierOrder->courier?->last_name ?? '')) ?: '—',
                'phone' => $courierOrder->courier?->phone_number,
                'region' => $courierOrder->courier?->region,
                'status' => $courierOrder->status_code ?? $courierOrder->status,
                'amount' => (float) ($courierOrder->amount ?? 0),
                'courierPrice' => (float) ($courierOrder->courierPrice ?? 0),
                'courierBonus' => (float) ($courierOrder->courierBonus ?? 0),
                'taskDistanceKm' => (float) ($courierTask?->distance_km ?? 0),
                'taskFeeAmount' => (float) ($courierTask?->fee_amount ?? 0),
                'taskBaseFeeAmount' => (float) ($courierTask?->base_fee_amount ?? 0),
                'taskDistanceFeeAmount' => (float) ($courierTask?->distance_fee_amount ?? 0),
                'taskBonusAmount' => (float) ($courierTask?->bonus_amount ?? 0),
                'taskLeg' => $courierTask?->leg,
                'settledAmount' => (float) ($courierOrder->settled_amount ?? 0),
                'settledAt' => $this->dateTime($courierOrder->settled_at),
                'pickedUpAt' => $this->dateTime($courierOrder->picked_up_at),
            ] : null,
            'activeHubs' => Schema::hasTable('hubs') ? Hub::query()
                ->where('is_active', true)
                ->orderBy('priority')
                ->get(['id', 'name', 'code', 'city_name'])
                ->map(fn (Hub $hub) => ['id' => $hub->id, 'label' => trim($hub->name.' '.($hub->code ? "({$hub->code})" : '').' '.($hub->city_name ?: ''))])
                ->values()
                ->all() : [],
            'fulfillmentModes' => collect(FulfillmentMode::cases())->map(fn (FulfillmentMode $mode) => [
                'value' => $mode->value,
                'label' => match ($mode) {
                    FulfillmentMode::HUB_BASED => 'Hub orqali kuryer yetkazuvi',
                    FulfillmentMode::DIRECT_COURIER => 'Direct courier',
                    FulfillmentMode::POSTAL_ONLY_VIA_HUB => 'Hub orqali pochta',
                    FulfillmentMode::PICKUP_ONLY => 'Pickup only',
                },
            ])->all(),
            'sellerOrders' => $sellerOrders,
            'dataUrl' => route('boshqaruv.orders.data', $order),
            'labelUrl' => route('boshqaruv.orders.print.label', $order),
            'receiptUrl' => route('boshqaruv.orders.print.receipt', $order),
            'statusUrl' => route('boshqaruv.orders.status', $order),
            'cancelUrl' => route('boshqaruv.orders.cancel', $order),
            'switchModeUrl' => route('boshqaruv.orders.switch-mode', $order),
            'rerouteHubUrl' => route('boshqaruv.orders.reroute-hub', $order),
            'postalReturnUrl' => route('boshqaruv.orders.postal-return', $order),
            'refundCancelUrl' => route('boshqaruv.orders.refund-cancel', $order),
        ];
    }

    private function orderFulfillmentTimeline(mixed $fulfillment): array
    {
        $rows = collect([
            ['code' => 'seller_prepared', 'title' => 'Seller buyurtmani tayyorladi', 'at' => $fulfillment->seller_prepared_at],
            ['code' => 'ready_for_pickup', 'title' => 'Pickup uchun tayyor', 'at' => $fulfillment->ready_for_pickup_at],
            ['code' => 'picked_from_seller', 'title' => 'Sellerdan olib ketildi', 'at' => $fulfillment->picked_from_seller_at],
            ['code' => 'arrived_at_hub', 'title' => 'Hubga qabul qilindi', 'at' => $fulfillment->arrived_at_hub_at],
            ['code' => 'qc_checked', 'title' => 'Sifat nazorati yakunlandi', 'at' => $fulfillment->qc_checked_at],
            ['code' => 'packed', 'title' => 'Qadoqlandi', 'at' => $fulfillment->packed_at],
            ['code' => 'labeled', 'title' => 'Etiketka tayyorlandi', 'at' => $fulfillment->labeled_at],
            ['code' => 'dispatched_to_post', 'title' => 'Pochtaga topshirildi', 'at' => $fulfillment->dispatched_to_post_at],
            ['code' => 'assigned_last_mile', 'title' => 'Last-mile yetkazuviga uzatildi', 'at' => $fulfillment->assigned_last_mile_at],
            ['code' => 'out_for_delivery', 'title' => "Mijozga yo'l oldi", 'at' => $fulfillment->out_for_delivery_at],
            ['code' => 'delivered', 'title' => 'Buyurtma topshirildi', 'at' => $fulfillment->delivered_at],
            ['code' => 'returned', 'title' => 'Buyurtma qaytarildi', 'at' => $fulfillment->returned_at],
        ])->filter(fn (array $row) => $row['at'])
            ->map(fn (array $row) => [...$row, 'at' => $this->dateTime($row['at'])]);

        $customRows = collect(data_get($fulfillment->meta ?? [], 'timeline', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['at']))
            ->map(fn (array $row) => [
                'code' => (string) ($row['code'] ?? 'event'),
                'title' => (string) ($row['title'] ?? $row['code'] ?? 'Operatsion hodisa'),
                'at' => $this->dateTime($row['at']) ?? (string) $row['at'],
            ]);

        return $rows->concat($customRows)
            ->unique(fn (array $row) => $row['code'].'-'.$row['at'])
            ->sortBy('at')
            ->values()
            ->all();
    }

    private function orderAddressPayload(array $address): array
    {
        $lat = $address['lat'] ?? $address['latitude'] ?? $address['location_lat'] ?? null;
        $lon = $address['lon'] ?? $address['lng'] ?? $address['longitude'] ?? $address['location_lon'] ?? null;
        $text = $address['fullAddress']
            ?? $address['full_address']
            ?? $address['address']
            ?? collect([
                $address['region'] ?? $address['province'] ?? null,
                $address['district'] ?? null,
                $address['street'] ?? null,
                $address['home'] ?? null,
            ])->filter()->implode(', ');

        return [
            'fullName' => $address['fullName'] ?? $address['full_name'] ?? null,
            'phone' => $address['phoneNumber'] ?? $address['phone'] ?? null,
            'region' => $address['region'] ?? $address['province'] ?? null,
            'district' => $address['district'] ?? null,
            'street' => $address['street'] ?? $address['address'] ?? null,
            'home' => $address['home'] ?? null,
            'fullAddress' => $text ?: null,
            'lat' => $lat,
            'lon' => $lon,
            'mapLinks' => $this->mapLinks($lat, $lon, $text),
        ];
    }

    private function mapLinks(mixed $lat = null, mixed $lon = null, ?string $address = null): array
    {
        $lat = is_numeric($lat) ? (float) $lat : null;
        $lon = is_numeric($lon) ? (float) $lon : null;
        $address = trim((string) $address);

        if ($lat !== null && $lon !== null) {
            return [
                'google' => 'https://www.google.com/maps/search/?api=1&query='.$lat.','.$lon,
                'yandex' => 'https://yandex.com/maps/?ll='.$lon.','.$lat.'&z=16&pt='.$lon.','.$lat.',pm2rdm',
            ];
        }

        if ($address !== '') {
            return [
                'google' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address),
                'yandex' => 'https://yandex.com/maps/?text='.rawurlencode($address),
            ];
        }

        return [];
    }

    private function qrImageUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=520x520&margin=22&format=png&ecc=Q&data='.rawurlencode($value);
    }

    private function orderItemPayload(array $item): array
    {
        $type = $item['type'] ?? 'book';
        $productId = (int) (
            $item['item_id']
            ?? $item['product_id']
            ?? $item['productId']
            ?? $item['itemId']
            ?? $item['id']
            ?? 0
        );
        $product = match ($type) {
            'stationery' => $productId ? Stationery::find($productId) : null,
            'gift' => $productId ? Gifts::find($productId) : null,
            default => $productId ? Books::with('seller:id,shop_name')->find($productId) : null,
        };
        $sellerId = (int) ($item['seller_id'] ?? $item['sellerId'] ?? 0);
        $seller = $sellerId > 0 ? Seller::select('id', 'shop_name')->find($sellerId) : null;
        $quantity = (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? $item['qty'] ?? 1);
        $price = (float) ($item['item_price'] ?? $item['price'] ?? $item['amount'] ?? 0);

        return [
            'id' => $productId,
            'type' => $type,
            'productUrl' => match ($type) {
                'stationery' => route('boshqaruv.stationeries', ['stationeries_search' => $productId, 'stationeries_tab' => 'all']),
                'gift' => route('boshqaruv.sovgalar'),
                default => route('boshqaruv.books', ['books_search' => $productId]),
            },
            'typeLabel' => match ($type) {
                'stationery' => 'Kanselyariya',
                'gift' => "Sovg'a",
                default => 'Kitob',
            },
            'name' => $product?->name
                ?? $product?->title
                ?? $item['name']
                ?? $item['title']
                ?? $item['product_name']
                ?? $item['productName']
                ?? 'Mahsulot',
            'quantity' => $quantity,
            'price' => $price,
            'total' => $price * $quantity,
            'seller' => $seller?->shop_name ?? $product?->seller?->shop_name ?? ($item['seller'] ?? $item['seller_name'] ?? null),
            'image' => $this->productImageUrl($product),
        ];
    }

    private function sellerOrderItemPayload(SellerOrderItem $item, bool $canModerateRefunds): array
    {
        $product = match ($item->type) {
            'stationery' => $item->stationery,
            'gift' => $item->gift,
            default => $item->book,
        };

        $seller = $item->seller_id ? Seller::select('id', 'shop_name')->find($item->seller_id) : null;

        return [
            'id' => (int) $item->product_id,
            'sellerOrderItemId' => (int) $item->id,
            'sellerOrderId' => (int) $item->order_id,
            'type' => (string) $item->type,
            'variantId' => $item->variant_id ? (int) $item->variant_id : null,
            'productUrl' => match ($item->type) {
                'stationery' => route('boshqaruv.stationeries', ['stationeries_search' => $item->product_id, 'stationeries_tab' => 'all']),
                'gift' => route('boshqaruv.sovgalar'),
                default => route('boshqaruv.books', ['books_search' => $item->product_id]),
            },
            'typeLabel' => match ($item->type) {
                'stationery' => 'Kanselyariya',
                'gift' => "Sovg'a",
                default => 'Kitob',
            },
            'name' => $product?->name ?? 'Mahsulot',
            'quantity' => (int) ($item->quantity ?? 1),
            'price' => (float) ($item->price ?? 0),
            'total' => (float) (($item->price ?? 0) * ($item->quantity ?? 1)),
            'seller' => $seller?->shop_name,
            'image' => $item->type === 'stationery' && $item->variant?->image_path
                ? ProductImageUrls::originalUrl((string) $item->variant->image_path)
                : $this->productImageUrl($product),
            'isCancelled' => $item->cancelled_at !== null,
            'cancelledAt' => $this->dateTime($item->cancelled_at),
            'cancelReasonCode' => $item->cancel_reason_code,
            'cancelNotes' => [
                'uz' => $item->cancel_note_uz,
                'ru' => $item->cancel_note_ru,
                'en' => $item->cancel_note_en,
                'ja' => $item->cancel_note_ja,
            ],
            'refundStatus' => $item->refund_status,
            'canRefund' => $canModerateRefunds && $item->cancelled_at === null,
            'refundUrl' => route('boshqaruv.seller-order-items.refund', $item),
        ];
    }

    private function canProcessOperationalRefunds(Sold $order, ?Transaction $paymentTransaction = null): bool
    {
        $orderStatus = OrderStatusCode::fromLegacy($order->status_code ?? $order->status);
        if (in_array($orderStatus, [
            OrderStatusCode::IN_DELIVERY,
            OrderStatusCode::DELIVERED,
            OrderStatusCode::CUSTOMER_RECEIVED,
            OrderStatusCode::RETURNED,
            OrderStatusCode::CANCELLED,
        ], true)) {
            return false;
        }

        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        if (in_array($paymentStatus, [
            PaymentStatusCode::CASH_PENDING,
            PaymentStatusCode::CARD_PENDING,
            PaymentStatusCode::HELD,
        ], true)) {
            return true;
        }

        if ($paymentStatus !== PaymentStatusCode::PAID) {
            return false;
        }

        if ((int) ($order->amount ?? 0) <= 0) {
            return (int) ($order->cashbackAmount ?? 0) > 0
                || (int) ($order->giftCertAmount ?? 0) > 0;
        }

        $transaction = $paymentTransaction;
        if (! $transaction) {
            $transaction = Transaction::query()
                ->where('order_id', $order->id)
                ->where('payment_type', 'order')
                ->where('provider', 'paylov')
                ->where(function ($query) {
                    $query->where('state', 2)
                        ->orWhereNotNull('perform_time');
                })
                ->latest('id')
                ->first();
        }

        return $transaction?->provider === 'paylov';
    }

    private function productImageUrl($product): ?string
    {
        if (! $product) {
            return null;
        }

        $image = $product->first_image ?? collect($product->images ?? [])->first();

        return is_string($image) && $image !== ''
            ? ProductImageUrls::originalUrl($image)
            : null;
    }

    private function assetFromStorage(?string $path): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }

    private function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'pending', 'A' => 'Yangi',
            'payment_pending' => "To'lov jarayonida",
            'packing', 'P' => 'Qadoqlanmoqda',
            'in_delivery', 'B' => "Yo'lda",
            'delivered', 'C' => 'Yetib bordi',
            'customer_received', 'D' => 'Mijoz qabul qildi',
            'cancelled', 'F' => 'Bekor qilindi',
            'returned', 'R' => 'Qaytgan',
            default => $status !== '' ? $status : 'Noma\'lum',
        };
    }

    private function sellerStatusLabel(string $status): string
    {
        return match ($status) {
            'payment_pending' => "To'lov jarayonida",
            'new' => 'Yangi',
            'accepted' => "Do'kon qabul qildi",
            'handed_to_courier' => 'Kuryerga berildi',
            'cancelled' => 'Bekor qilindi',
            default => $status !== '' ? $status : 'Noma\'lum',
        };
    }

    private function dateTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return is_scalar($value) ? (string) $value : null;
        }
    }

    private function formatPhone(mixed $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $raw = (string) $phone;
        $digits = preg_replace('/\D+/', '', $raw) ?: '';

        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            return sprintf(
                '+%s %s %s %s %s',
                substr($digits, 0, 3),
                substr($digits, 3, 2),
                substr($digits, 5, 3),
                substr($digits, 8, 2),
                substr($digits, 10, 2),
            );
        }

        return $raw;
    }

    private function tableCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function paginationMeta($paginator): array
    {
        return [
            'page' => (int) $paginator->currentPage(),
            'totalPages' => (int) $paginator->lastPage(),
            'from' => (int) ($paginator->firstItem() ?? 0),
            'to' => (int) ($paginator->lastItem() ?? 0),
            'total' => (int) $paginator->total(),
        ];
    }

    private function emptyPagination(): array
    {
        return ['page' => 1, 'totalPages' => 1, 'from' => 0, 'to' => 0, 'total' => 0];
    }

    private function legacyUrl(string $component): ?string
    {
        return [
            'Books' => route('boshqaruv.books'),
            'BookCategories' => route('boshqaruv.book-categories'),
            'Stationeries' => route('boshqaruv.stationeries'),
            'stationery-categories' => route('boshqaruv.stationery-categories'),
            'Authors' => route('boshqaruv.authors'),
            'Publishers' => route('boshqaruv.publishers'),
            'Users' => route('boshqaruv.users'),
            'Orders' => route('boshqaruv.orders'),
            'SellerOrders' => request()->is('boshqaruv/sellers*')
                ? route('boshqaruv.sellers')
                : route('boshqaruv.seller-orders'),
            'CourierOrders' => request()->is('boshqaruv/couriers*')
                ? route('boshqaruv.couriers')
                : route('boshqaruv.courier-orders'),
            'Hubs' => route('boshqaruv.hubs'),
            'Transaksiyalar' => route('boshqaruv.transactions'),
            'SellerAiActions' => route('boshqaruv.seller-ai-actions'),
            'LogistikaPage' => route('boshqaruv.logistika'),
            'Reklamalar' => route('boshqaruv.reklamalar'),
            'Promokodlar' => route('boshqaruv.promokodlar'),
            'Blogerlar' => route('boshqaruv.blogerlar'),
            'GiftSertifikatlar' => route('admin.gift-certificates.index'),
            'MarketNewsPage' => route('boshqaruv.market-news'),
            'CollectionsPage' => route('boshqaruv.collections'),
            'ReelsPage' => route('boshqaruv.reels'),
            'BookClub' => route('boshqaruv.book-club'),
            'Tickets' => route('boshqaruv.tickets'),
            'Shikoyatlar' => route('boshqaruv.shikoyatlar'),
            'ChatKuzatuv' => route('boshqaruv.chat'),
            'PushNotifications' => route('boshqaruv.push'),
            'Vakansiyalar' => route('boshqaruv.vakansiyalar'),
            'KaryeraArizalari' => route('admin.job-applications.index'),
            'Adminlar' => route('boshqaruv.adminlar'),
            'MysteryBoxPage' => route('admin.mystery-box.index'),
            'Sovgalar' => route('admin.gifts.index'),
            'Siyosatlar' => route('boshqaruv.siyosatlar'),
            'ApiClients' => route('boshqaruv.api-clients'),
            'SearchHistory' => route('boshqaruv.search-history'),
            'Settings' => route('boshqaruv.settings'),
        ][$component] ?? null;
    }
}
