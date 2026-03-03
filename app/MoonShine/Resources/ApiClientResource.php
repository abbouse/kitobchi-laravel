<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\ApiClient;
use Illuminate\Support\Str;

use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\Laravel\Resources\ModelResource;

class ApiClientResource extends ModelResource
{
    protected string $model = ApiClient::class;

    public string $titleField = 'name';

    protected string $title = 'API Mijozlar';

    public string $column = 'id';

    // ── Ro'yxat ko'rinishi ────────────────────────────────────────────────────
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make('Nomi', 'name'),

            Text::make('App ID', 'app_id')
                ->readonly(),

            Text::make('App Secret', 'app_secret')
                ->readonly(),

            Text::make('Huquqlar', 'abilities')
                ->readonly()
                ->hint('JSON formatida saqlangan'),

            Switcher::make('Holati', 'is_active')
                ->onValue('1')
                ->offValue('0')
                ->updateOnPreview(),
        ];
    }

    // ── Detail ko'rinishi ─────────────────────────────────────────────────────
    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    // ── Form (yaratish / tahrirlash) ──────────────────────────────────────────
    protected function formFields(): iterable
    {
        return [
            Grid::make([

                // Chap ustun — asosiy ma'lumotlar
                Column::make([
                    Box::make('Mijoz ma\'lumotlari', [

                        ID::make(),

                        Text::make('Mijoz nomi', 'name')
                            ->required()
                            ->hint('Masalan: "iOS App", "Partner Service"'),

                        Text::make('App ID', 'app_id')
                            ->readonly()
                            ->hint('Avtomatik yaratiladi'),

                        Text::make('App Secret', 'app_secret')
                            ->readonly()
                            ->hint('Avtomatik yaratiladi — faqat bir marta ko\'rsatiladi'),

                        Switcher::make('Faol', 'is_active')
                            ->onValue('1')
                            ->offValue('0')
                            ->default('1'),
                    ]),
                ])->columnSpan(6),

                // O'ng ustun — huquqlar
                Column::make([
                    Box::make('Huquqlar (Abilities)', [

                        // Har bir ability alohida checkbox sifatida
                        // abilities JSON arrayda saqlanadi: ['read','write','delete']
                        Text::make('Huquqlar (JSON)', 'abilities')
                            ->hint('Misol: ["read","write","delete"]')
                            ->placeholder('["read"]'),

                    ]),
                ])->columnSpan(6),

            ])->canSee(fn() => auth()->user()->moonshine_user_role_id <= 2),
        ];
    }
}