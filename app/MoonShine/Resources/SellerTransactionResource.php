<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\SellerTransaction;

use MoonShine\Laravel\Enums\Action;
use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;

use MoonShine\Laravel\Resources\ModelResource;

#[Icon('heroicons.outline.arrow-long-up')]
class SellerTransactionResource extends ModelResource
{
    protected string $model = SellerTransaction::class;

    protected string $title = 'Tranzaksiyalar';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::MASS_DELETE, Action::DELETE, Action::CREATE);
    }
    public function queryTags(): array
    {
        return [
            QueryTag::make(
                'Kutilmoqda',
                fn(Builder $query) => $query->where('status', 'pending')
            )->default(),
            QueryTag::make(
                'Tasdiqlangan',
                fn(Builder $query) => $query->where('status', 'approved')
            ),
            QueryTag::make(
                'Rad etilgan',
                fn(Builder $query) => $query->where('status', 'rejected')
            ),
        ];
    }
    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Sotuvchi', 'seller_id'),
                Number::make('Summa', 'amount'),
                Text::make('Karta', 'card'),
            Number::make('Komissiya %', 'commissionPercent'),
            Number::make('Komissiya (UZS)', 'commissionPrice'),
            Select::make('Holati', 'status')
                ->options([
                    'pending' => '🔃',
                    'approved' => '✅',
                    'rejected' => '❌',
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
                Text::make('Karta', 'card')->disabled(),
                Number::make('Qiymati', 'amount')->disabled(),
                Text::make('Karta', 'card')->disabled(),
                Number::make('Komissiya %', 'commissionPercent')->disabled(),
                Number::make('Komissiya (UZS)', 'commissionPrice')->disabled(),
                Select::make('Holati', 'status')
                    ->options([
                        'pending' => 'Kutilmoqda',
                        'approved' => 'Tasdiqlangan',
                        'rejected' => 'Rad etilgan',
                    ]),
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
}
