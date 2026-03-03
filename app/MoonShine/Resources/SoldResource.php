<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\StatusEnum;
use App\Models\Sold;
use App\Models\Seller;
use App\MoonShine\Resources\UsersResource;
use App\MoonShine\Resources\GiftsResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\Enums\Color;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Components;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Icon;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\Support\ListOf;

// yangi namespace (v3)
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Position;

// BelongsTo ehtimol Laravel integration ichida joylashgan:
use MoonShine\Laravel\Fields\Relationships\BelongsTo; // agar mavjud bo'lmasa: use MoonShine\UI\Fields\Relationships\BelongsTo;

#[Icon('heroicons.outline.rocket-launch')]
class SoldResource extends ModelResource
{
    protected string $model = Sold::class;
    protected string $title = "Yuborilishi kerak bo'lgan tovarlar";
    public string $column = 'id';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::DELETE, Action::CREATE, Action::MASS_DELETE);
    }

    public function queryTags(): array
    {
        return [
            QueryTag::make('Tez yuborish zarur', fn(Builder $q) => $q->where('status', 'A'))->default(),
            QueryTag::make("Qadoqlanmoqda/Kuryer olgan", fn(Builder $q) => $q->where('status', 'P')),
            QueryTag::make("Yo'lda", fn(Builder $q) => $q->where('status', 'B')),
            QueryTag::make('Yetib borgan', fn(Builder $q) => $q->where('status', 'C')),
            QueryTag::make('Bekor qilingan', fn(Builder $q) => $q->where('status', 'F')),
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make(
            'Buyurtmachi',
            'user',
            formatted: fn($user) => $user ? "{$user->name} {$user->lastname}" : '—',
            resource: UsersResource::class
        )->badge(Color::GREEN),

            Number::make('Narx (UZS)', 'amount')->disabled(),

            Select::make("To'lov holati", 'paymentStatus')->options([
            '0' => '🟨 Qabul qilinganida',
            '1' => '💳 Karta orqali',
            '2' => '✅ To\'langan',
            '3' => '❌ Rad etildi',
        ]),

            Enum::make('Buyurtma holati', 'status')->attach(StatusEnum::class),
        ];
    }

    protected function detailFields(): iterable
{
    return [
        ID::make('Buyurtma raqami', 'id')->sortable(),
        BelongsTo::make(
            'Buyurtmachi',
            'user',
            formatted: fn($user) => $user ? "{$user->name} {$user->lastname}" : '—',
            resource: UsersResource::class
        )->badge(Color::GREEN),
        Text::make('Yetkazish usuli', 'deliveryType'),
        Number::make('Yetkazish narxi', 'deliveryPrice'),
        Text::make('Promokod', 'promocode'),
        Number::make('Promokod summasi', 'discountAmount')->badge(Color::RED),
        Number::make('Umumiy summa', 'amount')->badge(Color::PURPLE),
        Select::make("To'lov holati", 'paymentStatus')->options([
            '0' => '🟨 Qabul qilinganida',
            '1' => '💳 Karta orqali',
            '2' => '✅ To\'langan',
            '3' => '❌ Rad etildi',
        ]),
        BelongsTo::make(
            'Sovg\'a',
            'getGift',
            formatted: fn($gift) => $gift ? "{$gift->name} — {$gift->id}" : '—',
            resource: GiftsResource::class
        )->badge(Color::PURPLE),
        Json::make('Buyurtma tarkibi', 'items')->fields([
            Position::make(),
            Text::make('Maxsulot', 'name'),
            Text::make('Muallif', 'author'),
            Number::make("Narxi (so'm)", 'item_price'),
            Number::make('ID', 'item_id'),
            Number::make('Soni', 'count_item'),
            Text::make(
                "Do'kon",
                'seller_id',
                fn($item) => (string) ( ($sellerId = data_get($item, 'seller_id')) && ($shop = Seller::find($sellerId))
                    ? "<a href='/admin/resource/seller-resource/detail-page/{$shop->id}' target='_blank'>🏪 {$shop->shop_name}</a>"
                    : "Shop #{$sellerId}" )
            )->unescape(),
        ])->creatable(false),
        Json::make('Yetkazish', 'address')->fields([
            Text::make('Manzil', 'fullAddress')->disabled(),
            Number::make('Telefon', 'phoneNumber')->disabled(),
            Text::make('Oluvchi', 'fullName')->disabled(),
            Text::make(
                'Joylashuv',
                'location',
                fn($address) => (string) ( (data_get($address, 'lat') && data_get($address, 'lon'))
                    ? "<a href='https://maps.yandex.uz/?text=" . e(data_get($address, 'lat')) . "+" . e(data_get($address, 'lon')) . "&z=16' target='_blank'>📍 Yandex xaritada ochish</a>"
                    : 'Joylashuv yo‘q' )
            )->unescape(),
            Text::make('LAT.', 'lat')->disabled(),
            Text::make('LON.', 'lon')->disabled(),
        ])->creatable(false),
        Date::make('Buyurtma vaqti', 'created_at'),
        Enum::make('Buyurtma holati', 'status')->attach(StatusEnum::class),
        Text::make('Keyingi xaridorga tilak', 'buyerWish'),
    ];
}

    protected function formFields(): iterable
{
    return [
        Box::make('Buyurtma ma\'lumotlari', [
            ID::make()->sortable(),
            Number::make('Umumiy summa', 'amount')->disabled(),
            Number::make('User ID', 'user_id')->disabled(),
            Text::make('Yetkazish turi', 'deliveryType')->disabled(),
            Select::make("To'lov holati", 'paymentStatus')->options([
            '0' => '🟨 Qabul qilinganida',
            '1' => '💳 Karta orqali (to\'lanmagan)',
            '2' => '✅ To\'langan',
            '3' => '❌ Rad etildi',
        ])->disabled(),
        ]),
        Enum::make('Buyurtma holati', 'status')->attach(StatusEnum::class),
        Date::make('Sotib olindi', 'created_at')->disabled()
            ->hint("Ushbu maydonlarni o'zgartirish mumkin emas!"),
    ];
}

    protected function search(): array
    {
        return ['id'];
    }
}
