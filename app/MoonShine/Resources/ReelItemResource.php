<?php
declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\ReelItem;
use Illuminate\Database\Eloquent\Model;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\File;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use Illuminate\Support\Facades\Storage;

class ReelItemResource extends ModelResource
{
    protected string $model = ReelItem::class;
    protected string $title = 'Reel Videolari';

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Reel', 'reel', resource: ReelResource::class),
            File::make('720p Video', 'video_720p')->disk('public'),
            Number::make('Tartib', 'order')->sortable(),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Box::make('Video Sifatlarini Yuklash', [
                BelongsTo::make('Reel', 'reel', resource: ReelResource::class)->required(),
                
                Grid::make([
                    Column::make([
                        File::make('Video 720p', 'video_720p')
                            ->disk('public')
                            ->dir('reels/720')
                            ->required()
                            ->customName(fn($file) => $this->syncFileName($file)),
                    ])->columnSpan(4),

                    Column::make([
                        File::make('Video 480p', 'video_480p')
                            ->disk('public')
                            ->dir('reels/480')
                            ->required()
                            ->customName(fn($file) => $this->syncFileName($file)),
                    ])->columnSpan(4),

                    Column::make([
                        File::make('Video 360p', 'video_360p')
                            ->disk('public')
                            ->dir('reels/360')
                            ->required()
                            ->customName(fn($file) => $this->syncFileName($file)),
                    ])->columnSpan(4),
                ]),

                Number::make('Tartib', 'order')
                    ->required()
                    ->default(fn() => ReelItem::max('order') + 1),
            ]),
        ];
    }

    protected function syncFileName($file): string
    {
        if (!request()->has('video_sync_name')) {
            $name = 'v_' . time() . '_' . uniqid();
            request()->merge(['video_sync_name' => $name]);
        }
        return request('video_sync_name') . '.' . $file->getClientOriginalExtension();
    }

    public function onDeleting(Model $item): void
    {
        Storage::disk('public')->delete(array_filter([
            $item->video_720p,
            $item->video_480p,
            $item->video_360p,
        ]));
    }
}