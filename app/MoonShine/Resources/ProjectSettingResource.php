<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectSetting;

use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;

use MoonShine\Laravel\Resources\ModelResource;

class ProjectSettingResource extends ModelResource
{
    protected string $model = ProjectSetting::class;
    protected string $title = 'ILOVA YANGILANISHLARI';
protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::MASS_DELETE, Action::DELETE, Action::CREATE);
    }
    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Kitobchi Business (iOS)', 'business_version_ios'),
                Text::make('Kitobchi Business (Android)', 'business_version_android'),
                Text::make('Kitobchi (iOS)', 'market_version_ios'),
                Text::make('Kitobchi (Android)', 'market_version_android'),
                Text::make('Kitobchi Kuryer (iOS)', 'courier_version_ios'),
                Text::make('Kitobchi Kuryer (Android)', 'courier_version_android'),
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
                Text::make('Kitobchi Business (iOS)', 'business_version_ios'),
                Text::make('Kitobchi Business (Android)', 'business_version_android'),
                Text::make('Kitobchi (iOS)', 'market_version_ios'),
                Text::make('Kitobchi (Android)', 'market_version_android'),
                Text::make('Kitobchi Kuryer (iOS)', 'courier_version_ios'),
                Text::make('Kitobchi Kuryer (Android)', 'courier_version_android'),
            ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
}
