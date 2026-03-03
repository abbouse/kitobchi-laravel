<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\DeliveryService;

use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;

class DeliveryServiceResource extends ModelResource
{
    protected string $model = DeliveryService::class;

    protected string $title = 'Yetkazib beruvchi pochtalar';

    protected function indexFields(): iterable
{
        return [
            ID::make()->sortable(),
            Text::make('Pochta', 'name'),
            Number::make('Narx (1kg)', 'priceKg'),
            Switcher::make('Holati', 'type')->onValue('on')->offValue('off')->updateOnPreview(),
            Text::make('Region', 'deliveryRegion'),
        ];
    }

    public function formFields(): array
    {
        return [
            Grid::make([
            Column::make([
                Box::make([
                ID::make()->sortable(),
                Text::make('Pochtaning rasmiy nomi', 'name')->required(),
                Number::make('1kg uchun narx', 'priceKg')->required(),
                Number::make('Yetkazish bepulligi uchun maksimal kitob narxi', 'freePriceFrom')->hint('narx so\'mda kiritilishi zarur, bunga ko\'ra kitoblar narxi ma\'lum darajaga yetganda yoki oshganda ushbu pochta orqali bepul yetkazilishi haqida foydalanuvchiga e\'lon ko\'rsatiladi')->required(),
            ]),
            ])->columnSpan(6),
            Column::make([
                Box::make([
                    Number::make('Yetkazish muddati (kun)', 'muddat')->required(),
                    Select::make('Region', 'deliveryRegion')->options([
                'all' => 'Barcha shaharlarga',
                'tash' => 'Faqat Toshkent shahri'
            ])->required(),
                    Switcher::make('Holati', 'type')->onValue('on')->offValue('off')->hint('o\'chirilgan bo\'lsa foydalanuvchilarga ushbu pochta varianti ko\'rsatilmaydi')->updateOnPreview(),
                    ]),
                    ])->columnSpan(6),
                    ])->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
        ];
    }

    public function detailFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Pochtaning rasmiy nomi', 'name'),
                Number::make('1kg uchun narx', 'priceKg'),
                Number::make('Yetkazish bepulligi uchun maksimal kitob narxi', 'freePriceFrom'),
                Number::make('Yetkazish muddati (kun)', 'muddat'),
                    Select::make('Region', 'deliveryRegion')->options([
                'all' => 'Barcha shaharlarga',
                'tash' => 'Faqat Toshkent shahri'
            ])->required(),
                    Switcher::make('Holati', 'type')->onValue('on')->offValue('off'),
        ];
    }
}
