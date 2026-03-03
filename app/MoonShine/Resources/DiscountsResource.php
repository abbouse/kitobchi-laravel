<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Discounts;
use App\Models\Books;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Number;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\ID;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\SortDirection;
use App\MoonShine\Resources\BooksResource;

class DiscountsResource extends ModelResource
{
    protected string $model = Discounts::class;

    public function getTitle(): string
    {
        return __('Chegirmalar ro\'yxati');
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Nomi', 'name')
                ->required(),
            Number::make('Chegirma foizi', 'discount')
        ];
    }

    protected function detailFields(): iterable
    {
        return [
            ID::make()->sortable(),

            // Chegirma nomi
            Text::make('Nomi', 'name')
                ->required(),
            Number::make('Chegirma foizi', 'discount')
                ->suffix('%'),
                BelongsToMany::make(
                    'Kitoblar', 'books',
                    resource: BooksResource::class
                )
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Box::make([
                Text::make('Nomi', 'name')
                    ->required(),

                Number::make('Chegirma foizi', 'discount')
                    ->required()
                    ->min(1)
                    ->max(100)
                    ->suffix('%')
                    ->placeholder('Foizlarda kiritish zarur, misol: 50'),
                BelongsToMany::make(
                    'Kitoblar', 'books',
                    fn($item) => $item->id.". ".$item->name." (".number_format($item->price)." UZS)",
                    BooksResource::class,
                    )->columnLabel('Salom')
                    ->searchable()
                    ->selectMode()
                    ->required(),
            ]),
        ];
    }
}
