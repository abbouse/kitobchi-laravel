<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookCategories;

use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;

class BookCategoriesResource extends ModelResource
{
    protected string $model = BookCategories::class;

    protected string $title = 'Bo\'limlar ro\'yxati';
    public string $column = 'title';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Nomi', 'title'),
                Text::make('Belgi', 'icon')->hint("Tushunmasangiz tegmaganingiz maqul!")->disabled(),
                Textarea::make('Bo\'lim haqida', 'description'),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    protected function formFields(): iterable
    {
        return [
                ID::make()->sortable(),
                Text::make('Nomi', 'title'),
                Text::make('Belgi', 'icon')->hint("Tushunmasangiz tegmaganingiz maqul!")->disabled(),
                Textarea::make('Bo\'lim haqida', 'description'),
        ];
    }
}
