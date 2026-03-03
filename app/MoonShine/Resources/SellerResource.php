<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Enums\StatusEnum;
use App\Models\Seller;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Enums\Action;
use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Select;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\Support\ListOf;

#[Icon('heroicons.outline.user')]
class SellerResource extends ModelResource
{
    protected string $model = Seller::class;

    protected string $title = 'Sotuvchilar';

    public string $column = 'id';

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
            Text::make('Do\'kon nomi', 'shop_name')->required(),
            Text::make('Telefon raqami', 'phone_number')->required(),
            Text::make('Viloyat', 'region')->required(),
            Select::make('Faoliyat turlari', 'activity_types')
                ->options([
                    'Kitob' => 'Kitob',
                    'Kanstovar' => 'Kanstovar',
                ])
                ->multiple(),
            Select::make('Holati', 'status')
                ->options([
                    'pending' => 'Kutilmoqda',
                    'approved' => 'Tasdiqlangan',
                    'rejected' => 'Rad etilgan',
                ]),
            Switcher::make('Yopilgan', 'is_hidden')->onValue(1)->offValue(0),
        ];
    }

    protected function detailFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Do\'kon nomi', 'shop_name')->required(),
            Text::make('Ism', 'firstname')->nullable(),
            Text::make('Familiya', 'lastname')->nullable(),
            Text::make('Telefon raqami', 'phone_number')->required(),
            Text::make('Viloyat', 'region')->required(),
            Select::make('Faoliyat turlari', 'activity_types')
                ->options([
                    'Kitob' => 'Kitob',
                    'Kanstovar' => 'Kanstovar',
                ])
                ->multiple(),
            Image::make('Rasm', 'photo')->dir('seller_photos')->nullable(),
            Text::make('Parol', 'password'),
            Text::make('Parolni tiklash chegarasi', 'password_reset_limit'),
            Text::make('Eslatma tokeni', 'remember_token'),
            Select::make('Holati', 'status')
                ->options([
                    'pending' => 'Kutilmoqda',
                    'approved' => 'Tasdiqlangan',
                    'rejected' => 'Rad etilgan',
                ]),
            Switcher::make('Yopilgan', 'is_hidden')->onValue(1)->offValue(0),
            Date::make('Yaratilgan sana', 'created_at')->format('d.m.Y'),
            Date::make('Yangilangan sana', 'updated_at')->format('d.m.Y'),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),
                Text::make('Do\'kon nomi', 'shop_name')->required(),
                Text::make('Ism', 'firstname')->nullable(),
                Text::make('Familiya', 'lastname')->nullable(),
                Text::make('Telefon raqami', 'phone_number')->required(),
                Text::make('Viloyat', 'region')->required(),
                Select::make('Faoliyat turlari', 'activity_types')
                ->options([
                    'Kitob' => 'Kitob',
                    'Kanstovar' => 'Kanstovar',
                ])
                ->multiple(),
                Image::make('Rasm', 'photo')->dir('seller_photos')->nullable(),
                Text::make('Parol', 'password'),
                Text::make('Parolni tiklash chegarasi', 'password_reset_limit'),
                Text::make('Eslatma tokeni', 'remember_token'),
                Select::make('Holati', 'status')
                ->options([
                    'pending' => 'Kutilmoqda',
                    'approved' => 'Tasdiqlangan',
                    'rejected' => 'Rad etilgan',
                ]),
                Switcher::make('Yopilgan', 'is_hidden')->onValue(1)->offValue(0),
                Date::make('Yaratilgan sana', 'created_at')->format('d.m.Y')->disabled(),
                Date::make('Yangilangan sana', 'updated_at')->format('d.m.Y')->disabled(),
            ]),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'shop_name' => ['required', 'string', 'max:255'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^\+998[0-9]{9}$/'],
            'region' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'password' => ['required', 'string', 'min:6'],
            'password_reset_limit' => ['nullable', 'integer'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'is_hidden' => ['boolean'],
        ];
    }

    protected function search(): array
    {
        return ['shop_name', 'firstname', 'lastname', 'phone_number', 'region'];
    }
}