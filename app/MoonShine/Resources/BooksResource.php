<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Books;
use App\Models\Seller;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Number;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Date;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\Enums\Color;
use MoonShine\Laravel\QueryTags\QueryTag;
use App\MoonShine\Resources\BookCategoriesResource;

class BooksResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern;

    protected string $model = Books::class;

    public function getTitle(): string
    {
        return __('Kitoblar ro\'yxati');
    }

    public string $column = 'name';
    
    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::MASS_DELETE, Action::CREATE);
    }
    
    public function queryTags(): array
    {
        return [
            QueryTag::make(
                'Kutilmoqda',
                fn(Builder $query) => $query->where('is_approved', '0')
            )->default(),
            QueryTag::make(
                'Tasdiqlangan',
                fn(Builder $query) => $query->where('is_approved', '1')
            ),
            QueryTag::make(
                'Rad etilgan',
                fn(Builder $query) => $query->where('is_approved', '2')
            ),
        ];
    }
    
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable()->badge(Color::PURPLE),
            Text::make('Nomi', 'name'),
            Text::make('Muallif', 'author'),
            Image::make('Rasm', 'images')->multiple(),
            Number::make('Narx (UZS)', 'price', fn($item) => number_format($item->price))->badge(Color::INFO),
            Number::make('Chegirma (UZS)', 'discountPrice', fn($item) => number_format($item->discountPrice))->badge(Color::YELLOW),
            Select::make('Moderatsiya', 'is_approved')
                ->options([
                    '0' => '🔃',
                    '1' => '✅',
                    '2' => '❌',
                ])->updateOnPreview(),
            Select::make('Marketpleyda', 'status')
                ->options([
                    '0' => 'Ko\'rsatilmayapti',
                    '1' => 'Ko\'rinadi'
                ])->default('1')->required(),
            Select::make('Holat', 'is_hidden')
                ->options([
                    '0' => '🟩 Faol',
                    '1' => '🟥 Faol emas'
                ])->default('0'),
        ];
    }

    protected function detailFields(): iterable
    {
        return [
            ID::make()->sortable()->badge(Color::PURPLE),
            Text::make('Nomi', 'name'),
            Text::make('Muallif', 'author'),
            BelongsTo::make('Bo\'lim', 'category', 'name', resource: BookCategoriesResource::class)->searchable(),
            BelongsTo::make('Sotuvchi', 'seller', 'shop_name')->searchable(),
            Image::make('Rasm', 'images')->dir('books')->multiple(),
            Textarea::make('Kitob haqida', 'description'),
            Number::make('Narx (UZS)', 'price', fn($item) => number_format($item->price))->badge(Color::INFO),
            Number::make('Chegirma narxi (UZS)', 'discountPrice', fn($item) => number_format($item->discountPrice))->badge(Color::YELLOW),
            Number::make('Mavjud', 'count', fn($item) => $item->count . ' dona')->badge('gray'),
            Select::make('Tili', 'lang')
                ->options([
                    'O‘zbek' => 'O\'zbekcha',
                    'Rus' => 'Ruscha',
                    'Ingliz' => 'Inglizcha',
                ]),
            Select::make('Yozuv turi', 'langType')
                ->options([
                    'Lotin' => 'Lotin',
                    'Kirill' => 'Kirill',
                ]),
            Select::make('Muqovasi', 'coverType')
                ->options([
                    'Yumshoq' => 'Yumshoq',
                    'Qattiq' => 'Qattiq',
                ]),
            Number::make('Yili', 'year'),
            Number::make('Sahifalar', 'pages'),
            BelongsToMany::make('Teglar', 'tags', 'tag_name_uz', resource: BookTagResource::class)
                ->badge(Color::BLUE),
            Select::make('Moderatsiya', 'is_approved')
                ->options([
                    '0' => '🔃 Kutilmoqda',
                    '1' => '✅ Tasdiqlangan',
                    '2' => '❌ Rad etilgan',
                ])->default('0')->updateOnPreview(),
            Select::make('Marketpleyda', 'status')
                ->options([
                    '0' => 'Ko\'rsatilmayapti',
                    '1' => 'Ko\'rinadi'
                ])->default('1')->required(),
            Select::make('Holat', 'is_hidden')
                ->options([
                    '0' => '🟩 Faol',
                    '1' => '🟥 Faol emas'
                ])->default('0')->hint('Marketpleysda ko\'rinsa ham, shuningdek Moderatsiya muvaffaqiyatli bo\'lsa ham maxsulot holati qizil (o\'chirilgan) bo\'lsa ushbu tovar hech qayerda ko\'rsatilmaydi.'),
            Date::make('Yaratilgan sana', 'created_at'),
            Date::make('Yangilangan sana', 'updated_at'),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Grid::make([
                Column::make([
                    Box::make([
                        BelongsTo::make(
                            'Bo\'lim',
                            'category',
                            'name',
                            resource: BookCategoriesResource::class
                        )->searchable()->disabled(),
                        BelongsTo::make('Sotuvchi', 'seller', 'shop_name')->disabled(),
                        Text::make('Nomi', 'name')->disabled(),
                        Text::make('Muallif', 'author')->disabled(),
                        Image::make('Rasmlar', 'images')->dir('books')->multiple()->disabled(),
                        Textarea::make('Kitob haqida', 'description')->disabled(),
                    ]),
                ])->columnSpan(6),
                Column::make([
                    Box::make([
                        Select::make('Tili', 'lang')
                            ->options([
                                'O‘zbek' => 'O\'zbekcha',
                                'Rus' => 'Ruscha',
                                'Ingliz' => 'Inglizcha',
                            ])->default('O‘zbek')->disabled(),
                        Select::make('Yozuv turi', 'langType')
                            ->options([
                                'Lotin' => 'Lotin',
                                'Kirill' => 'Kirill',
                            ])->default('Lotin')->disabled(),
                        Select::make('Muqovasi', 'coverType')
                            ->options([
                                'Yumshoq' => 'Yumshoq',
                                'Qattiq' => 'Qattiq',
                            ])->default('Yumshoq')->disabled(),
                        Number::make('Yili', 'year')
                            ->disabled(),
                        Number::make('Sahifalar', 'pages')->disabled(),
                        Number::make('Narxi', 'price')->disabled(),
                        Number::make('Chegirma narxi', 'discountPrice')->disabled(),
                        Number::make('Mavjud', 'count')->hint('Ushbu kitobdan zahirada nechta borligini kiriting')->disabled(),
                        Select::make('Moderatsiya', 'is_approved')
                            ->options([
                                '0' => 'Kutilmoqda',
                                '1' => 'Tasdiqlangan',
                                '2' => 'Rad etilgan',
                            ])->default('0')->required(),
                        Select::make('Marketpleyda', 'status')
                            ->options([
                                '0' => 'Ko\'rsatilmayapti',
                                '1' => 'Ko\'rinadi'
                            ])->default('1')->required(),
                        Switcher::make('Holati', 'is_hidden')->onValue(1)->offValue(0),
                    ]),
                ])->columnSpan(6),
            ]),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'category_id' => ['required', 'exists:book_categories,id'],
            'seller_id' => ['required', 'exists:sellers,id'],
            'name' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discountPrice' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'count' => ['required', 'integer', 'min:0'],
            'lang' => ['required', 'in:O‘zbek,Rus,Ingliz'],
            'langType' => ['required', 'in:Lotin,Kirill'],
            'coverType' => ['required', 'in:Yumshoq,Qattiq'],
            'year' => ['required', 'integer', 'min:2000', 'max:2026'],
            'pages' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:0,1'],
            'is_hidden' => ['boolean'],
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'author', 'count'];
    }

    protected function filters(): array
    {
        return [
            Select::make('Holati', 'status')
                ->options([
                    '0' => 'Faol emas',
                    '1' => 'Faol',
                ]),
            Switcher::make("O'chirilgan", 'is_hidden')->onValue(1)->offValue(0),
        ];
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make()->sortable()->badge(Color::PURPLE),
            Text::make('Nomi', 'name'),
            Text::make('Muallif', 'author'),
            BelongsTo::make('Sotuvchi', 'seller', 'shop_name'),
            Number::make('Narx (UZS)', 'price'),
            Number::make('Chegirma narxi (UZS)', 'discountPrice'),
            Number::make('Mavjud', 'count'),
            Select::make('Holati', 'status')
                ->options([
                    '0' => 'Faol',
                    '1' => 'Faol emas',
                ]),
        ];
    }

    protected function importFields(): iterable
    {
        return [
            ID::make()->sortable()->badge(Color::PURPLE),
            Text::make('Nomi', 'name'),
            Text::make('Muallif', 'author'),
            BelongsTo::make('Sotuvchi', 'seller', 'shop_name'),
            Number::make('Narx (UZS)', 'price'),
            Number::make('Chegirma narxi (UZS)', 'discountPrice'),
            Number::make('Mavjud', 'count'),
            Select::make('Holati', 'status')
                ->options([
                    '0' => 'Faol',
                    '1' => 'Faol emas',
                ]),
        ];
    }
}