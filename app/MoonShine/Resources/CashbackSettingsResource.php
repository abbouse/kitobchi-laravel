<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\CashbackSetting;

use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;


class CashbackSettingsResource extends ModelResource
{
    protected string $model = CashbackSetting::class;

    protected string $title = 'Sotib olish uchun keshbek';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Number::make('X UZS\'dan', 'fromUzs'),
                Number::make('Y UZS\'gacha', 'toUzs'),
                Number::make('Keshbek (%)', 'cashback')
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
                Number::make('X UZS\'dan', 'fromUzs'),
                Number::make('Y UZS\'gacha', 'toUzs'),
                Number::make('Keshbek (%)', 'cashback')
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
}
