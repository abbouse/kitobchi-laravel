<?php
declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Reel;
use App\Models\ReelItem;
use Illuminate\Database\Eloquent\Model;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Number;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\UI\Fields\File;
use MoonShine\Laravel\Resources\ModelResource;
use Illuminate\Support\Facades\Storage;

class ReelResource extends ModelResource
{
    protected string $model = Reel::class;
    protected string $title = 'Reels (Video Kolleksiyalar)';
    protected string $column = 'title';

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Sarlavha', 'title')->sortable(),
            Number::make('Tartib', 'order')->sortable(),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Grid::make([
                Column::make([
                    Box::make('Asosiy Ma\'lumotlar', [
                        Text::make('Sarlavha', 'title')->required(),
                        Textarea::make('Tavsif', 'description'),
                        Number::make('Tartib', 'order')
                            ->required()
                            ->default(fn() => Reel::max('order') + 1),
                    ]),
                ])->columnSpan(12),
            ]),

            HasMany::make('Videolar', 'items', resource: ReelItemResource::class)
                ->fields([
                    File::make('720p', 'video_720p')
                        ->disk('public')
                        ->dir('reels/720')
                        ->customName(fn($file) => $this->syncFileName($file)),
                    
                    File::make('480p', 'video_480p')
                        ->disk('public')
                        ->dir('reels/480')
                        ->customName(fn($file) => $this->syncFileName($file)),

                    File::make('360p', 'video_360p')
                        ->disk('public')
                        ->dir('reels/360')
                        ->customName(fn($file) => $this->syncFileName($file)),

                    Number::make('Tartib', 'order')->required(),
                ])
                ->creatable()
        ];
    }

    // Fayl nomini sinxronlash mantiqi
    protected function syncFileName($file): string
{
    if (!request()->has('video_sync_name')) {
        // Bo'shliqsiz va maxsus belgilarsiz nom yaratish
        $name = 'v_' . time() . '_' . str()->random(8);
        request()->merge(['video_sync_name' => $name]);
    }
    
    // extension'ni kichik harfga o'tkazish (MP4 -> mp4)
    $ext = strtolower($file->getClientOriginalExtension());
    
    return request('video_sync_name') . '.' . $ext;
}

    public function onDeleting(Model $item): void
    {
        foreach ($item->items as $reelItem) {
            Storage::disk('public')->delete(array_filter([
                $reelItem->video_720p,
                $reelItem->video_480p,
                $reelItem->video_360p,
            ]));
        }
    }
}