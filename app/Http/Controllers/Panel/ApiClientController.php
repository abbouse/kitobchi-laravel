<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    public function index()
    {
        $clients = ApiClient::latest()->get();
        return view('panel.api-clients.index', compact('clients'));
    }

    public function create()
    {
        return view('panel.api-clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'abilities' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // JSON tekshirish
        if (!empty($data['abilities'])) {
            json_decode($data['abilities']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['abilities' => 'JSON formati noto\'g\'ri.'])->withInput();
            }
        }

        ApiClient::create([
            'name'       => $data['name'],
            'app_id'     => 'app_' . Str::lower(Str::random(12)),
            'app_secret' => Str::random(48),
            'abilities'  => $data['abilities'] ?? '["read"]',
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('panel.api-clients.index')
            ->with('success', 'API mijoz yaratildi.');
    }

    public function show(ApiClient $apiClient)
    {
        return view('panel.api-clients.show', compact('apiClient'));
    }

    public function edit(ApiClient $apiClient)
    {
        return view('panel.api-clients.edit', compact('apiClient'));
    }

    public function update(Request $request, ApiClient $apiClient)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'abilities' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (!empty($data['abilities'])) {
            json_decode($data['abilities']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['abilities' => 'JSON formati noto\'g\'ri.'])->withInput();
            }
        }

        $apiClient->update([
            'name'      => $data['name'],
            'abilities' => $data['abilities'] ?? $apiClient->abilities,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('panel.api-clients.index')
            ->with('success', 'API mijoz yangilandi.');
    }

    /**
     * Secret kalitni qayta generatsiya qilish
     */
    public function regenerate(ApiClient $apiClient)
    {
        $apiClient->update(['app_secret' => Str::random(48)]);
        return back()->with('success', 'Yangi secret kalit yaratildi. Eski kalit endi ishlamaydi.');
    }

    /**
     * Faol/nofaol toggle
     */
    public function toggle(ApiClient $apiClient)
    {
        $apiClient->update(['is_active' => !$apiClient->is_active]);
        $msg = $apiClient->is_active ? 'Faollashtirildi.' : 'O\'chirildi.';
        return back()->with('success', $msg);
    }

    public function destroy(ApiClient $apiClient)
    {
        $apiClient->delete();
        return redirect()->route('panel.api-clients.index')
            ->with('success', 'API mijoz o\'chirildi.');
    }
}