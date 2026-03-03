<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

use App\Models\Couriers;

use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;

#[Icon('heroicons.outline.arrow-down-on-square-stack')]
class CouriersResource extends ModelResource
{
    protected string $model = Couriers::class;

    protected string $title = 'Kuryerlar';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Ism', 'first_name'),
                Text::make('Familiya', 'last_name'),
                Number::make('Balans', 'balance')->disabled(),
                Switcher::make('Status', 'status')->onValue('1')->offValue('0')->updateOnPreview(),
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
                Text::make('Ism', 'first_name'),
                Text::make('Familiya', 'last_name'),
                Number::make('Balans', 'balance')->disabled(),
                Text::make('Maxfiy kod', 'security_code')->default(bcrypt(Str::limit(rand(00000,99999), 50)))->disabled(),
                Switcher::make('Status', 'status')->onValue('1')->offValue('0')->updateOnPreview(),
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
}
