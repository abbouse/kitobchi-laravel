<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    private const LOCALES = ['ru', 'en', 'ja'];

    private function syncTranslations(Vacancy $vacancy, array $translations): void
    {
        foreach (self::LOCALES as $locale) {
            $payload = $translations[$locale] ?? [];
            $title = trim((string) ($payload['title'] ?? ''));
            $contractType = trim((string) ($payload['contract_type'] ?? ''));
            $location = trim((string) ($payload['location'] ?? ''));
            $description = trim((string) ($payload['description'] ?? ''));

            if ($title === '' && $contractType === '' && $location === '' && $description === '') {
                $vacancy->translations()->where('locale', $locale)->delete();
                continue;
            }

            $vacancy->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $title !== '' ? $title : null,
                    'contract_type' => $contractType !== '' ? $contractType : null,
                    'location' => $location !== '' ? $location : null,
                    'description' => $description !== '' ? $description : null,
                ]
            );
        }
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'active');
        $vacancies = Vacancy::query()
            ->with('translations')
            ->when($tab === 'active', fn ($query) => $query->where('is_active', true))
            ->when($tab === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($inner) use ($search) {
                    $inner->where('id', $search)
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('contract_type', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('translations', function ($translationQuery) use ($search) {
                            $translationQuery->where('title', 'like', "%{$search}%")
                                ->orWhere('contract_type', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Vacancy::count(),
            'active' => Vacancy::where('is_active', true)->count(),
            'inactive' => Vacancy::where('is_active', false)->count(),
        ];

        return view('a122.jobs.index', compact('vacancies', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.jobs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'icon'          => 'nullable|string|in:briefcase,code,palette,shop,megaphone,people,chart',
            'contract_type' => 'nullable|string|max:120',
            'location'      => 'nullable|string|max:255',
            'description'   => 'required|string',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
            'translations' => 'nullable|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.contract_type' => 'nullable|string|max:120',
            'translations.*.location' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $vacancy = Vacancy::create($data);
        $this->syncTranslations($vacancy, (array) $request->input('translations', []));
        return redirect()->route('admin.jobs.index')->with('success', "Vakansiya qo'shildi.");
    }

    public function edit(Vacancy $vacancy)
    {
        $vacancy->load('translations');
        return view('a122.jobs.edit', compact('vacancy'));
    }

    public function update(Request $request, Vacancy $vacancy)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'icon'          => 'nullable|string|in:briefcase,code,palette,shop,megaphone,people,chart',
            'contract_type' => 'nullable|string|max:120',
            'location'      => 'nullable|string|max:255',
            'description'   => 'required|string',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
            'translations' => 'nullable|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.contract_type' => 'nullable|string|max:120',
            'translations.*.location' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $vacancy->update($data);
        $this->syncTranslations($vacancy, (array) $request->input('translations', []));
        return redirect()->route('admin.jobs.index')->with('success', 'Vakansiya yangilandi.');
    }

    public function toggle(Vacancy $vacancy)
    {
        $vacancy->update(['is_active' => !$vacancy->is_active]);
        return back()->with('success', $vacancy->is_active ? 'Faollashtirildi.' : 'Yashirildi.');
    }

    public function destroy(Vacancy $vacancy)
    {
        $vacancy->delete();
        return back()->with('success', "Vakansiya o'chirildi.");
    }
}
