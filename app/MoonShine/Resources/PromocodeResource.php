<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Promocode;

use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;

class PromocodeResource extends ModelResource
{
    protected string $model = Promocode::class;
    protected string $title = 'PROMO KODLAR';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Kod', 'code'),
                Number::make('Chegirma narxi', 'amount'),
                Select::make('Turi', 'type')
                ->options([
                    'percent' => 'Foiz',
                    'uzs' => 'UZS',
                ]),
                Number::make('Min. summa', 'min_order_amount'),
                Number::make('Limit', 'usesLimit'),
                Number::make('Ishlatildi', 'usedCount'),
                Select::make('Holat', 'status')
                ->options([
                    '0' => 'Faol emas',
                    '1' => 'Faol',
                ])->updateOnPreview(),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    protected function formFields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),
                Text::make('Kod', 'code'),
                Number::make('Chegirma narxi', 'amount'),
                Select::make('Turi', 'type')
                ->options([
                    'percent' => 'Foiz',
                    'uzs' => 'UZS',
                ]),
                Number::make('Minimal buyurtma summasi (UZS)', 'min_order_amount'),
                Number::make('Limit', 'usesLimit'),
                Select::make('Holat', 'status')
                ->options([
                    '0' => 'Faol emas',
                    '1' => 'Faol',
                ]),
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
}
