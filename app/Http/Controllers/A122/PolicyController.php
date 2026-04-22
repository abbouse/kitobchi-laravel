<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PolicyController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'active');
        $policies = Policy::with('translations')
            ->when($tab === 'active', fn ($query) => $query->where('is_active', true))
            ->when($tab === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($inner) use ($search) {
                    $inner->where('id', $search)
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('translations', function ($translationQuery) use ($search) {
                            $translationQuery->where('title', 'like', "%{$search}%")
                                ->orWhere('content', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'total' => Policy::count(),
            'active' => Policy::where('is_active', true)->count(),
            'inactive' => Policy::where('is_active', false)->count(),
        ];

        return view('a122.policies.index', compact('policies', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.policies.edit');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'slug'       => 'nullable|string|max:255|unique:policies,slug',
            'content'    => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
            'show_in_app' => 'nullable|boolean',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        $data['is_active']   = $request->boolean('is_active');
        $data['show_in_app'] = $request->boolean('show_in_app');

        Policy::create($data);
        return redirect()->route('admin.policies.index')->with('success', "Siyosat qo'shildi.");
    }

    public function edit(Policy $policy)
    {
        return view('a122.policies.edit', compact('policy'));
    }

    public function update(Request $request, Policy $policy)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
            'show_in_app' => 'nullable|boolean',
        ]);

        $data['slug']       = Str::slug($data['title']);
        $data['is_active']  = $request->boolean('is_active');
        $data['show_in_app'] = $request->boolean('show_in_app');

        $policy->update($data);
        return back()->with('success', 'Siyosat yangilandi.');
    }

    public function toggle(Policy $policy)
    {
        $policy->update(['is_active' => !$policy->is_active]);
        return back()->with('success', $policy->is_active ? 'Faollashtirildi.' : "O'chirildi.");
    }

    public function destroy(Policy $policy)
    {
        $policy->delete();
        return back()->with('success', "Siyosat o'chirildi.");
    }
}
