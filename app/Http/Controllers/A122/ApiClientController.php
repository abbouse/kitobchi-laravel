<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = ApiClient::query()
            ->when($request->input('tab', 'active') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('tab') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($inner) use ($search) {
                    $inner->where('id', $search)
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('app_id', 'like', "%{$search}%")
                        ->orWhere('app_secret', 'like', "%{$search}%")
                        ->orWhere('abilities', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tab = $request->input('tab', 'active');
        $counts = [
            'all' => ApiClient::count(),
            'active' => ApiClient::where('is_active', true)->count(),
            'inactive' => ApiClient::where('is_active', false)->count(),
        ];

        return view('a122.api-clients.index', compact('clients', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.api-clients.edit');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'abilities' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ApiClient::create([
            'name'       => $data['name'],
            'app_id'     => 'app_' . Str::lower(Str::random(12)),
            'app_secret' => Str::random(48),
            'abilities'  => $data['abilities'] ?? '["read"]',
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.api-clients.index')->with('success', 'API mijoz yaratildi.');
    }

    public function edit(ApiClient $apiClient)
    {
        return view('a122.api-clients.edit', compact('apiClient'));
    }

    public function update(Request $request, ApiClient $apiClient)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'abilities' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $apiClient->update($data);
        return back()->with('success', 'Yangilandi.');
    }

    public function regenerate(ApiClient $apiClient)
    {
        $apiClient->update(['app_secret' => Str::random(48)]);
        return back()->with('success', 'Secret yangilandi.');
    }

    public function toggle(ApiClient $apiClient)
    {
        $apiClient->update(['is_active' => ! $apiClient->is_active]);

        return back()->with('success', $apiClient->is_active ? 'API mijoz faollashtirildi.' : 'API mijoz o‘chirildi.');
    }

    public function destroy(ApiClient $apiClient)
    {
        $apiClient->delete();
        return redirect()->route('admin.api-clients.index')->with('success', "O'chirildi.");
    }
}
