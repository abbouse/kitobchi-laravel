<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\ApiClientRequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    private const DEFAULT_LIMIT_PER_SECOND = 8;
    private const DEFAULT_LIMIT_PER_MINUTE = 240;

    private function normalizeAbilities(?string $input): array
    {
        $value = trim((string) $input);
        if ($value === '') {
            return ['read'];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $abilities = $decoded;
        } else {
            $abilities = preg_split('/[\s,]+/', $value) ?: [];
        }

        $abilities = collect($abilities)
            ->map(fn ($ability) => trim((string) $ability))
            ->filter()
            ->map(fn ($ability) => strtolower($ability))
            ->unique()
            ->values()
            ->all();

        return empty($abilities) ? ['read'] : $abilities;
    }

    private function buildLogsQuery(Request $request)
    {
        if (! Schema::hasTable('api_client_request_logs')) {
            return ApiClientRequestLog::query()->whereRaw('1 = 0');
        }

        return ApiClientRequestLog::query()
            ->with('client:id,name,app_id')
            ->when($request->filled('client_id'), fn ($query) => $query->where('api_client_id', (int) $request->input('client_id')))
            ->when($request->filled('method'), fn ($query) => $query->where('method', strtoupper((string) $request->input('method'))))
            ->when($request->filled('status_group'), function ($query) use ($request) {
                $group = (string) $request->input('status_group');
                if (in_array($group, ['2xx', '4xx', '5xx'], true)) {
                    $prefix = (int) $group[0];
                    $query->whereBetween('status_code', [$prefix * 100, $prefix * 100 + 99]);
                }
            })
            ->when($request->filled('path'), fn ($query) => $query->where('path', 'like', '%' . trim((string) $request->input('path')) . '%'))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')));
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('api_clients')) {
            $clients = ApiClient::query()->whereRaw('1 = 0')->paginate(20);
            $counts = ['all' => 0, 'active' => 0, 'inactive' => 0];
            $tab = $request->input('tab', 'active');

            return view('a122.api-clients.index', compact('clients', 'counts', 'tab'))
                ->with('warning', '`api_clients` jadvali topilmadi. `php artisan migrate`dan keyin sahifa to‘liq ishlaydi.');
        }

        $ratePerSecondColumnExists = Schema::hasColumn('api_clients', 'rate_limit_per_second');
        $ratePerMinuteColumnExists = Schema::hasColumn('api_clients', 'rate_limit_per_minute');

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

        return view('a122.api-clients.index', compact('clients', 'counts', 'tab', 'ratePerSecondColumnExists', 'ratePerMinuteColumnExists'));
    }

    public function create()
    {
        return view('a122.api-clients.create');
    }

    public function docs()
    {
        $endpoints = [
            ['GET', '/api/v1/client/products/sellers/list', 'Sellerlar ro‘yxati', 'read'],
            ['GET', '/api/v1/client/products/sellers/by-qr/{token}', 'QR bo‘yicha seller', 'read'],
            ['GET', '/api/v1/client/products/sellers/profile/{id}/{page}', 'Seller profil va mahsulotlar', 'read'],
            ['GET', '/api/v1/client/products/recommendation/{col}', 'Mahsulot tavsiyalari', 'read'],
            ['GET', '/api/v1/client/products/{col}', 'Mahsulotlar ro‘yxati', 'read'],
            ['GET', '/api/v1/client/search', 'Global qidiruv', 'read'],
            ['GET', '/api/v1/client/search/categories', 'Kategoriya ro‘yxati', 'read'],
            ['GET', '/api/v1/client/search/category/{cat_id}/{type}', 'Kategoriya ichidagi mahsulotlar', 'read'],
        ];

        $defaultLimits = [
            'per_second' => self::DEFAULT_LIMIT_PER_SECOND,
            'per_minute' => self::DEFAULT_LIMIT_PER_MINUTE,
        ];

        return view('a122.api-clients.docs', compact('endpoints', 'defaultLimits'));
    }

    public function logs(Request $request)
    {
        $logs = $this->buildLogsQuery($request)
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $clients = Schema::hasTable('api_clients')
            ? ApiClient::query()->orderBy('name')->get(['id', 'name', 'app_id'])
            : collect();

        return view('a122.api-clients.logs', compact('logs', 'clients'));
    }

    public function exportLogs(Request $request)
    {
        $logs = $this->buildLogsQuery($request)
            ->latest()
            ->limit(5000)
            ->get();

        $fileName = 'api-client-logs-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['time', 'client_id', 'client_name', 'app_id', 'method', 'path', 'status_code', 'duration_ms', 'ip_address', 'user_agent']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->api_client_id,
                    $log->client?->name,
                    $log->client?->app_id,
                    $log->method,
                    $log->path,
                    $log->status_code,
                    $log->duration_ms,
                    $log->ip_address,
                    $log->user_agent,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'abilities' => 'nullable|string',
            'is_active' => 'boolean',
            'rate_limit_per_second' => 'nullable|integer|min:1|max:10000',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:500000',
        ]);

        ApiClient::create([
            'name' => $data['name'],
            'app_id' => 'app_' . Str::lower(Str::random(12)),
            'app_secret' => Str::random(48),
            'abilities' => $this->normalizeAbilities($data['abilities'] ?? null),
            'is_active' => $request->boolean('is_active', true),
            'rate_limit_per_second' => $data['rate_limit_per_second'] ?? self::DEFAULT_LIMIT_PER_SECOND,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? self::DEFAULT_LIMIT_PER_MINUTE,
        ]);

        return redirect()->route('admin.api-clients.index')->with('success', 'API mijoz yaratildi.');
    }

    public function edit(ApiClient $apiClient)
    {
        $recentLogs = $apiClient->requestLogs()
            ->latest()
            ->limit(30)
            ->get();

        return view('a122.api-clients.edit', compact('apiClient', 'recentLogs'));
    }

    public function update(Request $request, ApiClient $apiClient)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'abilities' => 'nullable|string',
            'is_active' => 'boolean',
            'rate_limit_per_second' => 'nullable|integer|min:1|max:10000',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:500000',
        ]);
        $apiClient->update([
            'name' => $data['name'],
            'abilities' => $this->normalizeAbilities($data['abilities'] ?? null),
            'is_active' => $request->boolean('is_active'),
            'rate_limit_per_second' => $data['rate_limit_per_second'] ?? self::DEFAULT_LIMIT_PER_SECOND,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? self::DEFAULT_LIMIT_PER_MINUTE,
        ]);
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
