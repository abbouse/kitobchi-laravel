<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Models\VacancyTranslation;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function index()
    {
        $vacancies = Vacancy::orderBy('sort_order')->orderByDesc('id')->get();

        return view('panel.vacancies.index', compact('vacancies'));
    }

    public function create()
    {
        return view('panel.vacancies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge($this->vacancyBaseRules(), $this->vacancyTranslationRules()));

        $payload = [
            'title' => $data['title'],
            'icon' => ($data['icon'] ?? '') ?: null,
            'contract_type' => $data['contract_type'] ?? null,
            'location' => $data['location'] ?? null,
            'description' => $data['description'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];

        $vacancy = Vacancy::create($payload);

        $this->syncVacancyTranslations($request, $vacancy);

        return redirect()->route('panel.vacancies.index')
            ->with('success', 'Vakansiya qo‘shildi.');
    }

    public function edit(Vacancy $vacancy)
    {
        $vacancy->load('translations');

        return view('panel.vacancies.edit', compact('vacancy'));
    }

    public function update(Request $request, Vacancy $vacancy)
    {
        $data = $request->validate(array_merge($this->vacancyBaseRules(), $this->vacancyTranslationRules()));

        $vacancy->update([
            'title' => $data['title'],
            'icon' => ($data['icon'] ?? '') ?: null,
            'contract_type' => $data['contract_type'] ?? null,
            'location' => $data['location'] ?? null,
            'description' => $data['description'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncVacancyTranslations($request, $vacancy);

        return redirect()->route('panel.vacancies.index')
            ->with('success', 'Vakansiya yangilandi.');
    }

    public function toggle(Vacancy $vacancy)
    {
        $vacancy->update(['is_active' => ! $vacancy->is_active]);

        return back()->with('success', $vacancy->is_active ? 'Faollashtirildi.' : 'Yashirildi.');
    }

    public function destroy(Vacancy $vacancy)
    {
        $vacancy->delete();

        return back()->with('success', 'Vakansiya o‘chirildi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function vacancyBaseRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|in:'.implode(',', array_keys(Vacancy::iconOptions())),
            'contract_type' => 'nullable|string|max:120',
            'location' => 'nullable|string|max:255',
            'description' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vacancyTranslationRules(): array
    {
        $rules = [];
        foreach (['ru', 'en', 'ja'] as $loc) {
            $rules["translations.{$loc}.title"] = 'nullable|string|max:255';
            $rules["translations.{$loc}.contract_type"] = 'nullable|string|max:120';
            $rules["translations.{$loc}.location"] = 'nullable|string|max:255';
            $rules["translations.{$loc}.description"] = 'nullable|string';
        }

        return $rules;
    }

    private function syncVacancyTranslations(Request $request, Vacancy $vacancy): void
    {
        foreach (['ru', 'en', 'ja'] as $loc) {
            $title = $request->input("translations.{$loc}.title");
            $description = $request->input("translations.{$loc}.description");
            $contractType = $request->input("translations.{$loc}.contract_type");
            $location = $request->input("translations.{$loc}.location");

            $hasAny = collect([$title, $description, $contractType, $location])
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->isNotEmpty();

            if (! $hasAny) {
                $vacancy->translations()->where('locale', $loc)->delete();

                continue;
            }

            VacancyTranslation::updateOrCreate(
                ['vacancy_id' => $vacancy->id, 'locale' => $loc],
                [
                    'title' => is_string($title) && trim($title) !== '' ? trim($title) : null,
                    'description' => is_string($description) && trim($description) !== '' ? $description : null,
                    'contract_type' => is_string($contractType) && trim($contractType) !== '' ? trim($contractType) : null,
                    'location' => is_string($location) && trim($location) !== '' ? trim($location) : null,
                ]
            );
        }
    }
}
