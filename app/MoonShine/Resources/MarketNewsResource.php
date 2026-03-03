<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\MarketNews;

use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;

#[Icon('heroicons.outline.rss')]
class MarketNewsResource extends ModelResource
{
    protected string $model = MarketNews::class;

    protected string $title = 'Yangiliklar';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Image::make('Rasm', 'imgUrl')->dir('blog'),
                Text::make('Sarlavha', 'title'),
                Select::make('Joylashuvi', 'align')->options([
                        'top' => 'Yuqorida',
                        'center' => 'O\'rtada'
                    ])->required(),
                Switcher::make('Status', 'status')->updateOnPreview()->hint('Agar yoqilsa yangilik faollashadi va marketpleysda ko\'rina boshlaydi')
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
                Image::make('Rasm', 'imgUrl')->dir('blog'),
                Text::make('Sarlavha', 'title'),
                Textarea::make('Yangilik matni', 'description'),
                Select::make('Joylashuv', 'align')->options([
                        'top' => 'Yuqorida',
                        'center' => 'O\'rtada'
                    ])->required(),
                Switcher::make('Status', 'status')->updateOnPreview()->hint('Agar yoqilsa yangilik faollashadi va marketpleysda ko\'rina boshlaydi')
            ]),
        ];
    }
}
