<?php

namespace Tests\Feature\Catalog;

use App\Models\Books;
use App\Models\CatalogSlotPurchase;
use App\Models\CatalogSlotSetting;
use App\Models\Seller;
use App\Models\SellerBalanceEntry;
use App\Services\BranchStockService;
use App\Services\Catalog\BuyBoxService;
use App\Services\Catalog\CatalogSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * KATALOG JOYI (pullik buy box).
 *
 * Bitta kartada bitta joy; pul balansdan yechiladi; admin tasdiqlaydi;
 * qoldiq tugasa joy vaqtincha boshqa do'konga o'tadi, muddat esa davom etadi.
 */
class CatalogSlotTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private const API = '/api/v1/seller/';

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');

        CatalogSlotSetting::query()->delete();
        CatalogSlotSetting::query()->create([
            'price_per_month' => 300000,
            'min_days' => 7,
            'max_days' => 90,
            'is_active' => true,
        ]);
    }

    /** Ikki do'kon bitta kartada; ikkinchisi qimmatroq — oddiy holatda birinchisi g'olib. */
    private function twoOffers(): array
    {
        $cat = $this->makeCategory();
        $cheap = $this->makeBook($this->makeSeller(), $cat, ['price' => 40000]);
        $pricey = $this->makeBook($this->makeSeller(), $cat, ['price' => 90000]);

        return [$cheap->fresh(), $pricey->fresh()];
    }

    public function test_price_is_prorated_by_days(): void
    {
        $slots = app(CatalogSlotService::class);

        $this->assertSame(300000, $slots->priceFor(30));
        $this->assertSame(70000, $slots->priceFor(7));
    }

    public function test_purchase_charges_balance_and_writes_ledger(): void
    {
        [, $pricey] = $this->twoOffers();
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();

        $result = app(CatalogSlotService::class)->purchase($seller, $pricey, 30);

        $this->assertTrue($result['ok']);
        $this->assertSame(200000, (int) $seller->fresh()->balance);

        $entry = SellerBalanceEntry::query()->where('seller_id', $seller->id)->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame(-300000, (int) $entry->amount);
        $this->assertSame(SellerBalanceEntry::TYPE_CATALOG_SLOT, $entry->type);
    }

    public function test_second_shop_cannot_buy_a_taken_slot(): void
    {
        [$cheap, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);

        $first = Seller::find($pricey->seller_id);
        $first->forceFill(['balance' => 500000])->save();
        $this->assertTrue($slots->purchase($first, $pricey, 30)['ok']);

        $second = Seller::find($cheap->seller_id);
        $second->forceFill(['balance' => 500000])->save();
        $result = $slots->purchase($second, $cheap, 30);

        $this->assertFalse($result['ok']);
        $this->assertSame('slot_taken', $result['code']);
        // Pul yechilmasligi kerak
        $this->assertSame(500000, (int) $second->fresh()->balance);
    }

    public function test_insufficient_balance_is_rejected_without_charge(): void
    {
        [, $pricey] = $this->twoOffers();
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 1000])->save();

        $result = app(CatalogSlotService::class)->purchase($seller, $pricey, 30);

        $this->assertFalse($result['ok']);
        $this->assertSame('insufficient_balance', $result['code']);
        $this->assertSame(0, CatalogSlotPurchase::query()->count());
    }

    public function test_approved_slot_wins_buy_box_over_cheaper_offer(): void
    {
        [$cheap, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);

        // Avval: arzoni g'olib
        app(BuyBoxService::class)->recompute((int) $cheap->edition_id);
        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);

        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();
        $purchase = $slots->purchase($seller, $pricey, 30)['purchase'];

        // Tasdiqlanmaguncha hech narsa o'zgarmaydi
        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);

        $slots->approve($purchase, null);

        $this->assertTrue((bool) $pricey->fresh()->catalog_featured);
        $this->assertFalse((bool) $cheap->fresh()->catalog_featured);
    }

    public function test_paid_slot_passes_on_when_out_of_stock_but_keeps_running(): void
    {
        [$cheap, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);

        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();
        $purchase = $slots->purchase($seller, $pricey, 30)['purchase'];
        $slots->approve($purchase, null);
        $this->assertTrue((bool) $pricey->fresh()->catalog_featured);

        // Qoldiq tugadi — joy vaqtincha arzoniga o'tadi
        app(BranchStockService::class)->setTotalFromLegacy('book', (int) $pricey->id, 0, (int) $pricey->seller_id, 0);
        app(BuyBoxService::class)->recompute((int) $pricey->edition_id);

        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);
        // Lekin muddat davom etadi (pul qaytmaydi, joy band turaveradi)
        $this->assertSame(CatalogSlotPurchase::STATUS_ACTIVE, $purchase->fresh()->status);

        // Qoldiq qaytgach joy ham qaytadi
        app(BranchStockService::class)->setTotalFromLegacy('book', (int) $pricey->id, 0, (int) $pricey->seller_id, 5);
        app(BuyBoxService::class)->recompute((int) $pricey->edition_id);
        $this->assertTrue((bool) $pricey->fresh()->catalog_featured);
    }

    public function test_reject_refunds_the_shop_once(): void
    {
        [, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();

        $purchase = $slots->purchase($seller, $pricey, 30)['purchase'];
        $this->assertSame(200000, (int) $seller->fresh()->balance);

        $slots->reject($purchase, null, 'Sinov');
        $this->assertSame(500000, (int) $seller->fresh()->balance);

        // Ikkinchi marta qaytmasligi kerak
        $slots->reject($purchase->fresh(), null, 'Sinov');
        $this->assertSame(500000, (int) $seller->fresh()->balance);
    }

    public function test_expired_slot_releases_the_buy_box(): void
    {
        [$cheap, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();

        $purchase = $slots->purchase($seller, $pricey, 7)['purchase'];
        $slots->approve($purchase, null);
        $this->assertTrue((bool) $pricey->fresh()->catalog_featured);

        $purchase->forceFill(['ends_at' => now()->subMinute()])->save();
        $this->assertSame(1, $slots->expireDue());

        $this->assertSame(CatalogSlotPurchase::STATUS_EXPIRED, $purchase->fresh()->status);
        $this->assertTrue((bool) $cheap->fresh()->catalog_featured);
    }

    public function test_slot_frees_up_after_expiry_for_another_shop(): void
    {
        [$cheap, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);

        $first = Seller::find($pricey->seller_id);
        $first->forceFill(['balance' => 500000])->save();
        $purchase = $slots->purchase($first, $pricey, 7)['purchase'];
        $slots->approve($purchase, null);
        $purchase->forceFill(['ends_at' => now()->subMinute()])->save();
        $slots->expireDue();

        $second = Seller::find($cheap->seller_id);
        $second->forceFill(['balance' => 500000])->save();
        $this->assertTrue($slots->purchase($second, $cheap, 7)['ok']);
    }

    public function test_seller_api_reports_availability_and_buys(): void
    {
        [, $pricey] = $this->twoOffers();
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();
        Sanctum::actingAs($seller, ['*'], 'seller');

        $this->getJson(self::API . 'catalog-slots/info')
            ->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.price_per_month', 300000);

        $this->getJson(self::API . "catalog-slots/quote?book_id={$pricey->id}&days=30")
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.price', 300000);

        $this->postJson(self::API . 'catalog-slots', ['book_id' => $pricey->id, 'days' => 30])
            ->assertStatus(201);

        $this->getJson(self::API . "catalog-slots/quote?book_id={$pricey->id}&days=30")
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.occupied_by_me', true);
    }

    public function test_seller_cannot_buy_a_slot_for_another_shops_book(): void
    {
        [$cheap, $pricey] = $this->twoOffers();
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();
        Sanctum::actingAs($seller, ['*'], 'seller');

        $this->postJson(self::API . 'catalog-slots', ['book_id' => $cheap->id, 'days' => 30])
            ->assertStatus(404);
    }

    public function test_cancel_returns_money_only_while_pending(): void
    {
        [, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();

        $purchase = $slots->purchase($seller, $pricey, 30)['purchase'];
        $this->assertTrue($slots->cancel($purchase)['ok']);
        $this->assertSame(500000, (int) $seller->fresh()->balance);

        $second = $slots->purchase($seller->fresh(), $pricey, 30)['purchase'];
        $slots->approve($second, null);
        $result = $slots->cancel($second->fresh());
        $this->assertFalse($result['ok']);
        $this->assertSame('not_pending', $result['code']);
    }

    public function test_offers_payload_marks_the_sponsored_shop(): void
    {
        [, $pricey] = $this->twoOffers();
        $slots = app(CatalogSlotService::class);
        $seller = Seller::find($pricey->seller_id);
        $seller->forceFill(['balance' => 500000])->save();
        $slots->approve($slots->purchase($seller, $pricey, 30)['purchase'], null);

        $offers = \App\Support\CatalogOffers::forEdition((int) $pricey->edition_id, (int) $pricey->id);

        $sponsored = collect($offers)->firstWhere('is_sponsored', true);
        $this->assertNotNull($sponsored);
        $this->assertSame((int) $pricey->id, $sponsored['id']);
        // Pullik taklif ro'yxatda birinchi turadi
        $this->assertSame((int) $pricey->id, $offers[0]['id']);
    }

    public function test_offer_opened_from_a_shop_is_first_and_current(): void
    {
        [$cheap, $pricey] = $this->twoOffers();

        // Mijoz qimmat do'kon profilidan kirdi — o'sha birinchi va tanlangan
        $offers = \App\Support\CatalogOffers::forEdition((int) $pricey->edition_id, (int) $pricey->id);

        $this->assertSame((int) $pricey->id, $offers[0]['id']);
        $this->assertTrue($offers[0]['is_current']);
        $this->assertSame((int) $cheap->id, $offers[1]['id']);
    }
}
