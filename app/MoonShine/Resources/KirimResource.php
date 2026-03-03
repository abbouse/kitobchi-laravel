<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Enums\Action;
use App\Models\Kirim;
use App\Models\Sold;
use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\QueryTags\QueryTag;

use MoonShine\Laravel\Resources\ModelResource;


class KirimResource extends ModelResource
{
    protected string $model = Kirim::class;

    protected string $title = 'Kirim';
    
    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::UPDATE, Action::DELETE, Action::CREATE, Action::MASS_DELETE);
    }
    
    public function queryTags(): array
    {
        return [
            QueryTag::make(
                'Tekshiruvdagilar',
                fn(Builder $query) => $query->where('paymentStatus', ['0', '1'])
            )->default(),
            QueryTag::make(
                'Tasdiqlanganlar',
                fn(Builder $query) => $query->where('paymentStatus', '2')
            ),
            QueryTag::make(
                'Bekor qilinganlar',
                fn(Builder $query) => $query->where('paymentStatus', '3') 
            ),
            QueryTag::make(
                'Qabul qilinganida to\'lanadiganlar',
                fn(Builder $query) => $query->where('paymentStatus', '0')
            ),
        ];
    }

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Number::make('Buyurtma qiymati', 'amount'),
                Number::make('Buyurtma ID', 'order_id'),
                Select::make('To\'lov holati', 'paymentStatus')->options([
                'yes' => '✅To\'lov qilindi',
                'bek' => '❌Bekor qilindi',
                'no' => '🟥Tekshirilmadi',
                'yet' => '🟨Qabul qilinganida to\'lanadi'
            ])->onAfterApply(function(Model $item, $value, Select $field) {
                        $sold = Sold::where('id', $item->order_id)->first();
                        $sold->paymentStatus = $value;
                        if ($value == "bek") $sold->status = "F"; else $sold->status = "A";
                        $sold->save();
            })->updateOnPreview(),
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
                Number::make('Buyurtma qiymati', 'amount'),
                Number::make('Buyurtma ID', 'order_id'),
                Select::make('To\'lov holati', 'paymentStatus')->options([
                'yes' => '✅To\'lov qilindi',
                'bek' => '❌Bekor qilindi',
                'no' => '🟥Tekshirilmadi',
                'yet' => '🟨Qabul qilinganida to\'lanadi'
            ])->onAfterApply(function(Model $item, $value, Select $field) {
                        $sold = Sold::where('id', $item->order_id)->first();
                        $sold->paymentStatus = $value;
                        if ($value == "bek") $sold->status = "F"; else $sold->status = "A";
                        $sold->save();
            }),
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
}
