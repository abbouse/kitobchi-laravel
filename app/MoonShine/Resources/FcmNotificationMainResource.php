<?php
declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

use App\Models\FcmNotifications;
use App\Models\Couriers;
use App\Models\Users;

use MoonShine\UI\Components\Icon;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;

use MoonShine\Laravel\Resources\ModelResource;

#[Icon('heroicons.outline.bars-2')]
class FcmNotificationMainResource extends ModelResource
{
    protected string $model = FcmNotifications::class;
    protected string $title = 'Bildirishnoma: foydalanuvchilarga';

     protected function indexFields(): iterable
{
        return [
                ID::make()->sortable(),
                Text::make('Bildirishnoma sarlavhasi', 'name'),
                Textarea::make('Matn (iloji boricha qisqa)', 'description'),
                Text::make('Qabul qiluvchi:', 'who')->default('users')
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
                Text::make('Bildirishnoma sarlavhasi', 'name')->required(),
                Textarea::make('Matn (iloji boricha qisqa)', 'description')->required(),
                Text::make('Qabul qiluvchi:', 'who')
    ->default('users')
    ->disabled()
    ->onAfterApply(function(Model $item, $value) {
        // 1. Devices jadvalidan barcha 'user' tipidagi tokenlarni olamiz
        $tokens = \DB::table('connected_devices')
            ->where('user_type', 'user') // Faqat mijozlar uchun
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->unique() // Bir xil tokenlar bo'lsa olib tashlaymiz
            ->toArray();

        if (empty($tokens)) {
            \Log::warning('MoonShine Push: Yuborish uchun faol tokenlar topilmadi.');
            return;
        }

        // 2. Ma'lumotlarni tayyorlash
        $pushData = [
            'app_key' => 'kitobchi', 
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
            // Laravel Request obyektini to'g'ri formatda yaratish
            $request = new \Illuminate\Http\Request();
            $request->replace($pushData); 
            
            $pushController = app(\App\Http\Controllers\PushController::class);
            $response = $pushController->sendPush($request);
            
            \Log::info('MoonShine Push Result: ' . $response->getContent());
        } catch (\Exception $e) {
            \Log::error('MoonShine Push Error: ' . $e->getMessage());
        }
    }),
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
        return $builder->where('who', 'users');
    }
}
