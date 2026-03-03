<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\BookTag;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Color;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use App\MoonShine\Resources\BookCategoriesResource;

class BookTagResource extends ModelResource
{
    protected string $model = BookTag::class;

    public function getTitle(): string
    {
        return __('Teglar ro\'yxati');
    }

    public string $column = 'tag_name_uz';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::MASS_DELETE, Action::CREATE);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable()->badge(Color::PURPLE),
            Text::make('Nomi RU', 'tag_name_uz')->required(),
            Text::make('Nomi UZ', 'tag_name_ru')->required(),
            Text::make('Nomi EN', 'tag_name_en')->required(),
            BelongsToMany::make('Kategoriyalar', 'categories', 'name', resource: BookCategoriesResource::class)
                ->badge(Color::BLUE),
        ];
    }
    
    public function formFields(): iterable
    {
        return [
            ID::make()->sortable()->badge(Color::PURPLE),
            Text::make('Nomi RU', 'tag_name_uz')->required(),
            Text::make('Nomi UZ', 'tag_name_ru')->required(),
            Text::make('Nomi EN', 'tag_name_en')->required(),
            BelongsToMany::make('Kategoriyalar', 'categories', 'name', resource: BookCategoriesResource::class)
                ->searchable()
                ->required(),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'tag_name_ru' => ['required', 'string', 'max:255'],
            'tag_name_uz' => ['required', 'string', 'max:255'],
            'tag_name_en' => ['required', 'string', 'max:255'],
        ];
    }

    protected function search(): array
    {
        return ['id', 'tag_name_ru', 'tag_name_uz', 'tag_name_en'];
    }
}