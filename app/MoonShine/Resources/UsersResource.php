<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Phone;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;

class UsersResource extends ModelResource
{
    protected string $model = User::class;
    public string $titleField = 'name';
    protected string $title = 'Foydalanuvchilar ro\'yxati';
    protected string $column = 'id';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Ism', 'name', fn($item) => $item->name . ' ' . $item->lastname),
                Phone::make('Raqam', 'phone_number')->badge('purple'),
                Select::make('Position', 'position')
                    ->options([
                        "O'quvchi" => "O'quvchi"
                    ])->nullable(),
                Switcher::make('Holati', 'verified')->onValue('1')->offValue('0')->updateOnPreview(),
                Number::make('Tek. kodi', 'verifyCode')->disabled(),
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
                Text::make('Ism', 'name')->required(),
                Text::make('Familiya', 'lastname'),
                Phone::make('Raqam', 'phone_number')->required(),
                Select::make('Position', 'position')
                    ->options([
                        "O'quvchi" => "O'quvchi"
                    ])->nullable(),
                    Number::make('Haqiqiy balans (kivi)', 'real_balance')->disabled(),
                Select::make('Jins', 'sex')
                    ->options([
                        'erkak' => 'Erkak',
                        'ayol' => 'Ayol'
                    ])->nullable(),
                Switcher::make('Holati', 'verified')->onValue('1')->offValue('0'),
                Number::make('Tek. kodi', 'verifyCode')->disabled(),
                Text::make('Email', 'email'),
                Text::make('Aktual token', 'remember_token'),
                Text::make('FCM token', 'fcm_token')->disabled(),
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
    
    protected function search(): array
    {
        return ['id', 'name', 'lastname', 'phone_number'];
    }
}
