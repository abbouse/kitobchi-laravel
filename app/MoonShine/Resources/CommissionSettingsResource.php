<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\CommissionSetting;

use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;


class CommissionSettingsResource extends ModelResource
{
    protected string $model = CommissionSetting::class;

    protected string $title = 'Tovarlar uchun komissiya miqdorlari';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Number::make('X UZS\'dan', 'priceFrom'),
                Number::make('Y UZS\'gacha', 'priceTo'),
                Number::make('Komissiya (%)', 'percent')
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
                Number::make('X UZS\'dan', 'priceFrom'),
                Number::make('Y UZS\'gacha', 'priceTo'),
                Number::make('Komissiya (%)', 'percent')
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
}
