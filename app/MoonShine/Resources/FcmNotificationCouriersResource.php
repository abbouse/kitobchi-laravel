<?php
declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

use Muneebkh2\LaravelFcmNotifications\Facades\LaravelFCM;
use App\Models\FcmNotifications;
use App\Models\Couriers;

use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;

#[Icon('heroicons.outline.bars-2')]
class FcmNotificationCouriersResource extends ModelResource
{
    protected string $model = FcmNotifications::class;
    protected string $title = 'Bildirishnoma: kuryerlarga';

    protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Bildirishnoma sarlavhasi', 'name'),
                Textarea::make('Matn (iloji boricha qisqa)', 'description'),
                Text::make('Qabul qiluvchi:', 'who')->default('courier')->disabled()
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
                Text::make('Bildirishnoma sarlavhasi', 'name'),
                Textarea::make('Matn (iloji boricha qisqa)', 'description'),
                Text::make('Qabul qiluvchi:', 'who')->default('courier')->disabled()->onAfterApply(function(Model $item, $value) {
    // 1. Devices jadvalidan kuryerlarning barcha tokenlarini olamiz
    $tokens = \DB::table('connected_devices')
        ->where('user_type', 'courier')
        ->whereNotNull('fcm_token')
        ->pluck('fcm_token')
        ->unique()
        ->toArray();

    if (empty($tokens)) return;

    // 2. Ma'lumotlarni tayyorlash
    $pushData = [
        'app_key' => 'courier', 
        'title'   => htmlspecialchars_decode($item->name, ENT_QUOTES),
        'body'    => htmlspecialchars_decode($item->description, ENT_QUOTES),
        'tokens'  => $tokens,
        'data'    => [
            'type' => 'general',
            'id'   => (string)$item->id
        ]
    ];

    // 3. Kontrollerni chaqirish
    try {
        $request = new \Illuminate\Http\Request();
        $request->replace($pushData);
        app(\App\Http\Controllers\PushController::class)->sendPush($request);
    } catch (\Exception $e) {
        \Log::error('Couriers Push Error: ' . $e->getMessage());
    }
})
                ])->canSee(fn() => auth()->user()->moonshine_user_role_id === 1),
        ];
    }
    protected function rules(mixed $item): array
    {
        return [
            'title' => ['unique:fcm_notifications'],
            'description' => ['unique:fcm_notifications']
        ];
    }
    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where('who', 'courier');
    }
}
