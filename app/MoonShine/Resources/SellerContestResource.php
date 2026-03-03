<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\SellerContest;

use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Json;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\Laravel\Resources\ModelResource;

class SellerContestResource extends ModelResource
{
    protected string $model = SellerContest::class;
    protected string $title = 'Do\'konlar tadbirlari';


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
            QueryTag::make(
                'Yakunlangan',
                fn(Builder $query) => $query->where('status', 'ended')
            ),
        ];
    }
    
    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Sotuvchi', 'seller', fn($item) => $item->shop_name ?? 'N/A'),
            Text::make('Sarlavha', 'title'),
            Date::make('Tugash sanasi', 'end_date')->format('d.m.Y'),
            Select::make('Status', 'status')->options([
                'pending' => 'Kutilmoqda',
                'approved' => 'Tasdiqlangan',
                'rejected' => 'Rad etilgan',
                'ended' => 'Yakunlangan',
            ])->updateOnPreview(),
            Number::make('G\'oliblar soni', 'winner_count'),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Sotuvchi', 'seller', fn($item) => $item->shop_name ?? 'N/A'),
            Text::make('Sarlavha', 'title'),
            Textarea::make('Tavsif', 'description'),
            Date::make('Tugash sanasi', 'end_date')->format('d.m.Y H:i'),
            Select::make('Status', 'status')->options([
                'pending' => 'Kutilmoqda',
                'approved' => 'Tasdiqlangan',
                'rejected' => 'Rad etilgan',
                'ended' => 'Yakunlangan',
            ])->updateOnPreview(),
            Number::make('G\'oliblar soni', 'winner_count'),
            Json::make('G\'oliblar', 'winners')->keyValue(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),
                BelongsTo::make('Sotuvchi', 'seller', fn($item) => $item->shop_name ?? 'N/A')
                    ->searchable()
                    ->nullable(),
                Text::make('Sarlavha', 'title')->required(),
                Textarea::make('Tavsif', 'description'),
                Date::make('Tugash sanasi', 'end_date')->required(),
                Select::make('Status', 'status')->options([
                'pending' => 'Kutilmoqda',
                'approved' => 'Tasdiqlangan',
                'rejected' => 'Rad etilgan',
                'ended' => 'Yakunlangan',
                ])->default('inactive'),
                Number::make('G\'oliblar soni', 'winner_count')->default(0),
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
    public function search(): array
    {
        return ['id', 'title', 'seller.name'];
    }

    public function filters(): array
    {
        return [
            Select::make('Status', 'status')->options([
                '' => 'Barchasi',
                'active' => 'Faol',
                'inactive' => 'Nofaol',
                'completed' => 'Yakunlangan',
            ])->nullable(),
            Date::make('Tugash sanasi', 'end_date'),
        ];
    }
}