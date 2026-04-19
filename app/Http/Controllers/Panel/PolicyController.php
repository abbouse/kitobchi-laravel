<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PolicyController extends Controller
{
    public function index()
    {
        $policies = Policy::orderBy('sort_order')->orderBy('id')->with('translations')->get();

        return view('panel.policies.index', compact('policies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge($this->policyBaseRules(null), $this->policyTranslationRules()));

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $policy = Policy::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'show_in_app' => $request->boolean('show_in_app'),
        ]);

        $this->syncPolicyTranslations($request, $policy);

        return back()->with('success', "Siyosat muvaffaqiyatli qo'shildi.");
    }

    public function update(Request $request, Policy $policy)
    {
        $data = $request->validate(array_merge($this->policyBaseRules($policy->id), $this->policyTranslationRules()));

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $policy->update([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'show_in_app' => $request->boolean('show_in_app'),
        ]);

        $this->syncPolicyTranslations($request, $policy);

        return back()->with('success', 'Siyosat yangilandi.');
    }

    public function toggle(Request $request, Policy $policy)
    {
        $policy->update(['is_active' => ! $policy->is_active]);

        return back()->with('success', $policy->is_active ? 'Siyosat faollashtirildi.' : "Siyosat o'chirildi.");
    }

    public function destroy(Policy $policy)
    {
        $policy->delete();

        return back()->with('success', "Siyosat o'chirildi.");
    }

    /**
     * @return array<string, mixed>
     */
    private function policyBaseRules(?int $ignorePolicyId): array
    {
        $slugRule = 'nullable|string|max:255|unique:policies,slug';
        if ($ignorePolicyId !== null) {
            $slugRule .= ','.$ignorePolicyId;
        }

        return [
            'title' => 'required|string|max:255',
            'slug' => $slugRule,
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'show_in_app' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function policyTranslationRules(): array
    {
        $rules = [];
        foreach (['ru', 'en', 'ja'] as $loc) {
            $rules["translations.{$loc}.title"] = 'nullable|string|max:255';
            $rules["translations.{$loc}.content"] = 'nullable|string';
        }

        return $rules;
    }

    private function syncPolicyTranslations(Request $request, Policy $policy): void
    {
        foreach (['ru', 'en', 'ja'] as $loc) {
            $title = $request->input("translations.{$loc}.title");
            $content = $request->input("translations.{$loc}.content");

            $hasTitle = is_string($title) && trim($title) !== '';
            $hasContent = is_string($content) && trim(strip_tags($content)) !== '';

            if (! $hasTitle || ! $hasContent) {
                $policy->translations()->where('locale', $loc)->delete();

                continue;
            }

            PolicyTranslation::updateOrCreate(
                ['policy_id' => $policy->id, 'locale' => $loc],
                [
                    'title' => trim((string) $title),
                    'content' => (string) $content,
                ]
            );
        }
    }
}
