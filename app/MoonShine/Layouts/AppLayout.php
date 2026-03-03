<?php
namespace App\MoonShine\Layouts;

use Illuminate\Support\Facades\Cache; // Cache ni qo'shamiz

use App\MoonShine\Resources\BookCategoriesResource;
use App\MoonShine\Resources\BooksResource;
use App\MoonShine\Resources\ProjectSettingResource;
use App\MoonShine\Resources\ApiClientResource;
use App\MoonShine\Resources\SellerContestResource;
use App\MoonShine\Resources\BookTagResource;
use App\MoonShine\Resources\CommissionSettingsResource;
use App\MoonShine\Resources\CashbackSettingsResource;
use App\MoonShine\Resources\CouriersResource;
use App\MoonShine\Resources\DeliveryServiceResource;
use App\MoonShine\Resources\FcmNotificationCouriersResource;
use App\MoonShine\Resources\FcmNotificationMainResource;
use App\MoonShine\Resources\FcmNotificationBusinessResource;
use App\MoonShine\Resources\GiftsResource;
use App\MoonShine\Resources\KirimResource;
use App\MoonShine\Resources\MarketNewsResource;
use App\MoonShine\Resources\PromocodeResource;
use App\MoonShine\Resources\SellerResource;
use App\MoonShine\Resources\SellerTransactionResource;
use App\MoonShine\Resources\SoldResource;
use App\MoonShine\Resources\UsersResource;
use App\MoonShine\Resources\ReelResource;
use App\MoonShine\Resources\ReelItemResource;
use MoonShine\Laravel\Resources\MoonShineUserResource;
use MoonShine\Laravel\Resources\MoonShineUserRoleResource;

use App\Models\BookCategories;
use App\Models\Books;
use App\Models\BookTag;
use App\Models\Couriers;
use App\Models\DeliveryService;
use App\Models\FcmNotifications;
use App\Models\Gifts;
use App\Models\MarketNews;
use App\Models\Promocode;
use App\Models\Seller;
use App\Models\SellerContest;
use App\Models\Sold;
use App\Models\User;

use MoonShine\Laravel\Layouts\CompactLayout;
use MoonShine\Laravel\Components\Layout\{Locales, Notifications, Profile, Search};
use MoonShine\UI\Components\{Breadcrumbs, Components, Layout\Flash, Layout\Div, Layout\Body, Layout\Burger, Layout\Content, Layout\Footer, Layout\Head, Layout\Favicon, Layout\Assets, Layout\Meta, Layout\Header, Layout\Html, Layout\Layout, Layout\Logo, Layout\Menu, Layout\Sidebar, Layout\ThemeSwitcher, Layout\TopBar, Layout\Wrapper};
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;

final class AppLayout extends CompactLayout
{
  // Kesh davomiyligini belgilaymiz (sekundlarda, masalan, 5 daqiqa)
  private const CACHE_DURATION = 300; 
protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

  protected function menu(): array
  {
    return [
      // #1 Tezlik optimizatsiyasi: Sold (Sotilganlar) soni
      MenuItem::make(
        static fn() => __('Yuborish kerak'),
        SoldResource::class
      )
      ->icon('rocket-launch')
      ->badge(fn() => Cache::remember('sold_status_a_count', self::CACHE_DURATION, fn() => Sold::where('status', 'A')->count()), 'green')
      ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
      MenuGroup::make('Tizim')->setItems([
        MenuItem::make('Boshqaruvchilar', MoonShineUserResource::class),
        MenuItem::make('Lavozimlar', MoonShineUserRoleResource::class),
        MenuItem::make('Komissiya to\'lovlari', CommissionSettingsResource::class)->icon('banknotes'),
        MenuItem::make('Keshbek sozlanmasi', CashbackSettingsResource::class)->icon('receipt-percent'),
        // #2 Tezlik optimizatsiyasi: Users soni
        MenuItem::make('Ilova versiyalari', ProjectSettingResource::class)
        ->icon('users')
        ->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        MenuItem::make('API kalitlari', ApiClientResource::class)
        ->icon('users')
        ->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
      ])->icon('adjustments-horizontal')->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
     
      MenuGroup::make('Boshqaruv', [
          MenuItem::make('Foydalanuvchilar', UsersResource::class)
        ->icon('users')
        ->badge(fn() => Cache::remember('users_count', self::CACHE_DURATION, fn() => User::count()))
        ->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        
        // #3 Tezlik optimizatsiyasi: Couriers soni
        MenuItem::make('Kuryerlar ro\'yxati', CouriersResource::class)
        ->icon('users')
        ->badge(fn() => Cache::remember('couriers_count', self::CACHE_DURATION, fn() => Couriers::count()))
        ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
        
        // #4 Tezlik optimizatsiyasi: Seller soni
        MenuItem::make('Sotuvchilar ro\'yxati', SellerResource::class)
        ->icon('users')
        ->badge(fn() => Cache::remember('seller_count', self::CACHE_DURATION, fn() => Seller::count()))
        ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
        
        // #5 Tezlik optimizatsiyasi: DeliveryService soni
        MenuItem::make('Yetkazib berish usullari', DeliveryServiceResource::class)
        ->icon('inbox-stack')
        ->badge(fn() => Cache::remember('delivery_service_count', self::CACHE_DURATION, fn() => DeliveryService::count()))
        ->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        
        // #6 Tezlik optimizatsiyasi: MarketNews soni
        MenuItem::make(
          static fn() => __('Yangiliklar & Bloglar'),
          MarketNewsResource::class
        )->icon('rss')
        ->badge(fn() => Cache::remember('market_news_count', self::CACHE_DURATION, fn() => MarketNews::count()))
        ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 3),
        MenuGroup::make('Video Boshqaruvi', [
            MenuItem::make('Reel Kolleksiyalar', ReelResource::class)
                ->icon('play'),
            MenuItem::make('Barcha Videolar', ReelItemResource::class)
                ->icon('play'),
        ])->icon('folder-open'),
        
        // #7 Tezlik optimizatsiyasi: Promocodes soni
        MenuItem::make("Promokodlar", PromocodeResource::class)
        ->icon('receipt-percent')
        ->badge(fn() => Cache::remember('promocodes_count', self::CACHE_DURATION, fn() => Promocode::count()))
        ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
        
        MenuItem::make("Do'konlar tadbirlari", SellerContestResource::class)
        ->icon('receipt-percent')
        ->badge(fn() => Cache::remember('seller_contest_count', self::CACHE_DURATION, fn() => SellerContest::count()))
        ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
        
        MenuGroup::make('Bildirishnoma (mobil))', [
          // #8 Tezlik optimizatsiyasi: FcmNotifications soni (Foydalanuvchilarga)
          MenuItem::make(
            static fn() => __('Foydalanuvchilarga'),
            FcmNotificationMainResource::class
          )->icon('ellipsis-horizontal')
          ->badge(fn() => Cache::remember('fcm_main_count', self::CACHE_DURATION, fn() => FcmNotifications::where('who', 'users')->count())),
          MenuItem::make(
            static fn() => __('Biznes egalariga'),
            FcmNotificationBusinessResource::class
          )->icon('ellipsis-horizontal')
          ->badge(fn() => Cache::remember('fcm_main_count', self::CACHE_DURATION, fn() => FcmNotifications::where('who', 'business')->count())),
          // #9 Tezlik optimizatsiyasi: FcmNotifications soni (Kuryerlarga)
          MenuItem::make(
            static fn() => __('Kuryerlarga'),
            FcmNotificationCouriersResource::class
          )->icon('ellipsis-horizontal')
          ->badge(fn() => Cache::remember('fcm_couriers_count', self::CACHE_DURATION, fn() => FcmNotifications::where('who', 'courier')->count())), // Aslida bu so'rov ham bir xil, lekin 2 marta keshlanmasligi uchun
        ])->icon('bell')->canSee(fn() => auth()->user()->moonshine_user_role_id <= 3),
      ])->icon('shield-check')->canSee(fn() => auth()->user()->moonshine_user_role_id <= 3),
     
      MenuGroup::make('Hisobot', [
        MenuItem::make('Kirim', KirimResource::class)->icon('arrow-long-down'),
        MenuItem::make('Tranzaksiyalar', SellerTransactionResource::class)->icon('arrow-long-up'),
      ])->icon('scale')->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
     
      MenuGroup::make('Maxsulotlar', [
        // #10 Tezlik optimizatsiyasi: BookCategories soni
        MenuItem::make("Bo'limlar", BookCategoriesResource::class)
          ->icon('folder')
          ->badge(fn() => Cache::remember('book_categories_count', self::CACHE_DURATION, fn() => BookCategories::count()))
          ->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        
        // #11 Tezlik optimizatsiyasi: Books soni
        MenuItem::make("Kitoblar", BooksResource::class)
          ->icon('book-open')
          ->badge(fn() => Cache::remember('books_count', self::CACHE_DURATION, fn() => Books::count()))
          ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 4),
          
        // #12 Tezlik optimizatsiyasi: BookTag soni
        MenuItem::make("Kitoblar uchun teglar", BookTagResource::class)
          ->icon('book-open')
          ->badge(fn() => Cache::remember('book_tag_count', self::CACHE_DURATION, fn() => BookTag::count()))
          ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 4),
        
        // #14 Tezlik optimizatsiyasi: Gifts soni
        MenuItem::make("Sovg'alar", GiftsResource::class)
          ->icon('gift')
          ->badge(fn() => Cache::remember('gifts_count', self::CACHE_DURATION, fn() => Gifts::count()))
          ->canSee(fn() => auth()->user()->moonshine_user_role_id <= 3),
      ])->icon('view-columns')->canSee(fn() => auth()->user()->moonshine_user_role_id <= 4),
    ];
  }
  
  protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }

    public function build(): Layout
    {
        return parent::build();
        /*return Layout::make([
            Html::make([
                $this->getHeadComponent(),
                Body::make([
                    Wrapper::make([
                        $this->getSidebarComponent(),

                        Div::make([
                            Flash::make(),

                            $this->getHeaderComponent(),

                            Content::make([
                                Components::make(
                                    $this->getPage()->getComponents()
                                ),
                            ]),

                            $this->getFooterComponent(),
                        ])->class('layout-page'),
                    ]),
                ]),
            ])
                ->customAttributes([
                    'lang' => $this->getHeadLang(),
                ])
                ->withAlpineJs()
                ->withThemes(),
        ]);*/
    }
}