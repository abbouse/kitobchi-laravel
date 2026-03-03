<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gifts;

use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Range; 
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

use MoonShine\Laravel\Resources\ModelResource;


class GiftsResource extends ModelResource
{
    protected string $model = Gifts::class;
public string $titleField = 'name';
    protected string $title = 'Kitoblar uchun beriluvchi sovg\'alar';
    public string $column = 'id';
    
    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Nomi', 'name'),
                Image::make('Rasm', 'image')->dir('gifts'),
                    Number::make('Narxi (UZS)', 'price'),
                    Number::make('Mavjud', 'count'),
                    Switcher::make('Holati', 'type')->onValue('on')->offValue('off')->updateOnPreview(),
        ];
    }
    
    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    protected function formFields(): iterable
    {
        return [
            Grid::make([
            Column::make([
                Box::make([
                ID::make()->sortable(),
                Text::make('Nomi', 'name')->required(),
                Image::make('Rasm', 'image')->dir('gifts')->required(),
                Textarea::make('Sovg\'a haqida', 'description')->required(),
            ]),
            ])->columnSpan(6),
            Column::make([
                Box::make([
                    Range::make('Kitoblar toifasi')
            ->fromTo('priceFrom', 'priceTo'),
                    Number::make('Sovg\'a narxi', 'price')->required(),
                    Number::make('Mavjud', 'count')->hint('Ushbu maydon omborda qancha qolganini ko\'rsatadi')->required(),
                    Switcher::make('Holati', 'type')->onValue('on')->offValue('off'),
                    ]),
                    ])->columnSpan(6),
                    ])->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
        ];
    }
}
