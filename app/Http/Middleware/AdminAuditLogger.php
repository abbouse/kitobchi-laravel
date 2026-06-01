<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request)) {
            try {
                $admin = Auth::guard('panel')->user();
                $route = $request->route();
                [$targetType, $targetId] = $this->targetFromRoute($route?->parameters() ?? []);

                AdminAuditLog::query()->create([
                    'admin_id' => $admin?->id,
                    'admin_name' => $admin?->name ?? $admin?->email ?? 'Admin',
                    'method' => $request->method(),
                    'route_name' => $route?->getName(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'action' => $this->actionLabel($route?->getName(), $request->method()),
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'request_data' => $this->safeRequestData($request),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 2000),
                    'status_code' => $response->getStatusCode(),
                ]);
            } catch (\Throwable) {
                // Audit should never break admin actions.
            }
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        return ! $request->isMethod('GET')
            && ! $request->is('boshqaruv/login')
            && Schema::hasTable('admin_audit_logs');
    }

    private function safeRequestData(Request $request): array
    {
        $except = [
            'password',
            'password_confirmation',
            'current_password',
            '_token',
            '_method',
            'photo',
            'image',
            'images',
            'document',
            'file',
        ];

        return collect($request->except($except))
            ->map(fn ($value) => is_scalar($value) || is_null($value) ? $value : json_decode(json_encode($value), true))
            ->take(80)
            ->all();
    }

    private function targetFromRoute(array $parameters): array
    {
        foreach ($parameters as $name => $value) {
            if (is_object($value) && isset($value->id)) {
                return [class_basename($value), (int) $value->id];
            }

            if (is_numeric($value)) {
                return [(string) $name, (int) $value];
            }
        }

        return [null, null];
    }

    private function actionLabel(?string $routeName, string $method): string
    {
        if (! $routeName) {
            return $method;
        }

        return str($routeName)
            ->after('boshqaruv.')
            ->replace(['.', '-'], ' ')
            ->headline()
            ->value();
    }
}
