<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyLevel;
use App\Services\BadgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/** Boshqaruv: kitobxon darajalari (chegaralar, nomlar, imtiyozlar) va nishonlar statistikasi. */
class LoyaltyController extends Controller
{
    public function index(): Response
    {
        $byLevel = DB::table('users')->selectRaw('position, count(*) c')->groupBy('position')->pluck('c', 'position');
        $byBadge = Schema::hasTable('user_badges')
            ? DB::table('user_badges')->selectRaw('badge_key, count(*) c')->groupBy('badge_key')->pluck('c', 'badge_key')
            : collect();

        return Inertia::render('Darajalar', [
            'levels' => LoyaltyLevel::query()->orderBy('sort')->get()->map(fn (LoyaltyLevel $l) => [
                'id' => $l->id, 'key' => $l->key, 'sort' => $l->sort,
                'name_uz' => $l->name_uz, 'name_ru' => $l->name_ru, 'name_en' => $l->name_en, 'name_ja' => $l->name_ja,
                'min_orders' => $l->min_orders, 'min_spent' => $l->min_spent, 'color' => $l->color,
                'perks_uz' => $l->perks_uz, 'perks_ru' => $l->perks_ru, 'perks_en' => $l->perks_en, 'perks_ja' => $l->perks_ja,
                'users' => (int) ($byLevel[$l->key] ?? 0),
            ]),
            'badges' => collect(BadgeService::DEFINITIONS)->map(fn ($d, $key) => [
                'key' => $key, 'metric' => $d['metric'], 'target' => $d['target'], 'users' => (int) ($byBadge[$key] ?? 0),
            ])->values(),
        ]);
    }

    public function update(Request $request, LoyaltyLevel $level): RedirectResponse
    {
        $data = $request->validate([
            'name_uz' => 'required|string|max:80',
            'name_ru' => 'nullable|string|max:80',
            'name_en' => 'nullable|string|max:80',
            'name_ja' => 'nullable|string|max:80',
            'min_orders' => 'required|integer|min:0|max:100000',
            'min_spent' => 'required|integer|min:0|max:10000000000',
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'perks_uz' => 'nullable|string|max:2000',
            'perks_ru' => 'nullable|string|max:2000',
            'perks_en' => 'nullable|string|max:2000',
            'perks_ja' => 'nullable|string|max:2000',
        ]);

        // Birinchi daraja — hamma uchun boshlang'ich
        $first = LoyaltyLevel::query()->orderBy('sort')->value('id');
        if ((int) $first === (int) $level->id) {
            $data['min_orders'] = 0;
            $data['min_spent'] = 0;
        }
        $level->update($data);

        // Chegaralar pastdan yuqoriga o'sib borishi kerak
        $prev = null;
        foreach (LoyaltyLevel::query()->orderBy('sort')->get() as $l) {
            if ($prev && ($l->min_orders < $prev->min_orders || $l->min_spent < $prev->min_spent)) {
                session()->flash('error', "Diqqat: \"{$l->name_uz}\" chegarasi oldingi darajadan past — tartibni tekshiring.");
                break;
            }
            $prev = $l;
        }

        return back()->with('success', 'Daraja saqlandi. Foydalanuvchilar keyingi xarid yoki faollikda qayta hisoblanadi.');
    }
}
