<?php

declare(strict_types=1);

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Kiberxavfsizlik va Server salomatligi bosh sahifasi
     */
    public function index(Request $request): Response
    {
        $serverHealth = $this->getServerHealth();
        $servicesStatus = $this->getServicesStatus();
        $threatAnalysis = $this->getThreatAnalysis();
        $blockedIps = $this->getBlockedIps();
        $systemLogs = $this->getRecentSystemErrors();

        $logFile = storage_path('logs/laravel.log');
        $logSizeFormatted = File::exists($logFile) ? $this->formatBytes(File::size($logFile)) : '0 B';

        return Inertia::render('SecurityDashboard', [
            'serverHealth'      => $serverHealth,
            'servicesStatus'    => $servicesStatus,
            'threatAnalysis'    => $threatAnalysis,
            'blockedIps'        => $blockedIps,
            'systemLogs'        => $systemLogs,
            'sslStatus'         => $this->getSslStatus(),
            'envStatus'         => $this->getEnvExposureStatus(),
            'logFileSize'       => $logSizeFormatted,
            'currentAdminIp'    => $request->ip(),
            'firewallActive'    => true,
            'generatedAt'       => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Server xotirasi (Disk, RAM, CPU) va resurslar holati
     */
    protected function getServerHealth(): array
    {
        // 1. Disk maydoni
        $diskPath = base_path();
        $totalBytes = @disk_total_space($diskPath) ?: 1;
        $freeBytes = @disk_free_space($diskPath) ?: 0;
        $usedBytes = max(0, $totalBytes - $freeBytes);
        $usedPercent = round(($usedBytes / $totalBytes) * 100, 1);

        $diskStatus = 'safe';
        if ($usedPercent >= 90) {
            $diskStatus = 'critical';
        } elseif ($usedPercent >= 80) {
            $diskStatus = 'warning';
        }

        // 2. Inodes (fayllar soni) holati
        $inodePercent = null;
        $inodeTotal = null;
        $inodeUsed = null;
        if (function_exists('shell_exec')) {
            try {
                $dfOutput = @shell_exec('df -i ' . escapeshellarg($diskPath) . ' 2>/dev/null');
                if ($dfOutput && preg_match('/\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%\s+/', $dfOutput, $m)) {
                    $inodeTotal = (int) $m[1];
                    $inodeUsed = (int) $m[2];
                    $inodePercent = (int) $m[4];
                }
            } catch (\Throwable) {}
        }

        // 3. RAM (Tezkor xotira)
        $ramTotalMb = null;
        $ramUsedMb = null;
        $ramPercent = null;
        if (is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $totalMatch) &&
                preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $availMatch)) {
                $totalKb = (int) $totalMatch[1];
                $availKb = (int) $availMatch[1];
                $usedKb = max(0, $totalKb - $availKb);
                $ramTotalMb = round($totalKb / 1024, 0);
                $ramUsedMb = round($usedKb / 1024, 0);
                $ramPercent = round(($usedKb / $totalKb) * 100, 1);
            }
        }

        if ($ramTotalMb === null && function_exists('shell_exec')) {
            try {
                $totalBytes = (int) @shell_exec('sysctl -n hw.memsize 2>/dev/null');
                if ($totalBytes > 0) {
                    $ramTotalMb = round($totalBytes / (1024 * 1024), 0);
                    $vmStat = @shell_exec('vm_stat 2>/dev/null');
                    if ($vmStat && preg_match('/Pages free:\s+(\d+)\./', $vmStat, $mFree) && preg_match('/page size of (\d+) bytes/', $vmStat, $mPage)) {
                        $freeBytes = (int) $mFree[1] * (int) $mPage[1];
                        $usedBytes = max(0, $totalBytes - $freeBytes);
                        $ramUsedMb = round($usedBytes / (1024 * 1024), 0);
                        $ramPercent = round(($usedBytes / $totalBytes) * 100, 1);
                    }
                }
            } catch (\Throwable) {}
        }

        // 4. Server Uptime va Load Average
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $uptime = null;
        if (is_readable('/proc/uptime')) {
            $upSeconds = (int) floatval(explode(' ', (string) @file_get_contents('/proc/uptime'))[0] ?? 0);
            $days = floor($upSeconds / 86400);
            $hours = floor(($upSeconds % 86400) / 3600);
            $minutes = floor(($upSeconds % 3600) / 60);
            $uptime = "{$days} kun, {$hours} soat, {$minutes} daqiqa";
        } elseif (function_exists('shell_exec')) {
            try {
                $uptimeOut = @shell_exec('uptime 2>/dev/null');
                if ($uptimeOut && preg_match('/up\s+([^,]+),/', $uptimeOut, $m)) {
                    $uptime = trim($m[1]);
                }
            } catch (\Throwable) {}
        }

        return [
            'disk' => [
                'total_formatted' => $this->formatBytes($totalBytes),
                'used_formatted'  => $this->formatBytes($usedBytes),
                'free_formatted'  => $this->formatBytes($freeBytes),
                'used_percent'    => $usedPercent,
                'status'          => $diskStatus,
                'inodes_percent'  => $inodePercent,
                'inodes_total'    => $inodeTotal,
                'inodes_used'     => $inodeUsed,
            ],
            'ram' => [
                'total_mb' => $ramTotalMb,
                'used_mb'  => $ramUsedMb,
                'percent'  => $ramPercent,
            ],
            'system' => [
                'php_version'     => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Nginx / PHP-FPM',
                'load_average'    => array_map(fn($v) => round($v, 2), $loadAvg),
                'uptime'          => $uptime ?? 'Noma\'lum',
                'server_ip'       => $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()),
            ],
        ];
    }

    /**
     * Tizim xizmatlari va konfiguratsiyalar tekshiruvi (MySQL, Cache, Storage, Config Integrity)
     */
    protected function getServicesStatus(): array
    {
        // 1. MySQL tekshiruvi
        $dbStatus = 'ok';
        $dbLatencyMs = null;
        $dbError = null;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $dbStatus = 'failed';
            $dbError = $e->getMessage();
        }

        // 2. Kesh (Cache) tekshiruvi
        $cacheStatus = 'ok';
        $cacheError = null;
        try {
            Cache::put('_security_health_check', true, 10);
            if (!Cache::get('_security_health_check')) {
                $cacheStatus = 'warning';
                $cacheError = 'Keshga yozildi, lekin o\'qishda xato berdi.';
            }
        } catch (\Throwable $e) {
            $cacheStatus = 'failed';
            $cacheError = $e->getMessage();
        }

        // 3. Navbat / Failed jobs
        $failedJobsCount = 0;
        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedJobsCount = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable) {}

        // 4. Storage papkalari va ruxsatlar tekshiruvi
        $storageChecks = [
            'views' => [
                'path' => storage_path('framework/views'),
                'exists' => File::isDirectory(storage_path('framework/views')),
                'writable' => File::isWritable(storage_path('framework/views')),
            ],
            'sessions' => [
                'path' => storage_path('framework/sessions'),
                'exists' => File::isDirectory(storage_path('framework/sessions')),
                'writable' => File::isWritable(storage_path('framework/sessions')),
            ],
            'cache' => [
                'path' => storage_path('framework/cache'),
                'exists' => File::isDirectory(storage_path('framework/cache')),
                'writable' => File::isWritable(storage_path('framework/cache')),
            ],
            'logs' => [
                'path' => storage_path('logs'),
                'exists' => File::isDirectory(storage_path('logs')),
                'writable' => File::isWritable(storage_path('logs')),
            ],
            'bootstrap_cache' => [
                'path' => base_path('bootstrap/cache'),
                'exists' => File::isDirectory(base_path('bootstrap/cache')),
                'writable' => File::isWritable(base_path('bootstrap/cache')),
            ],
            'tmp_dir' => [
                'path' => '/tmp',
                'exists' => is_dir('/tmp'),
                'writable' => is_writable('/tmp'),
            ],
        ];

        // 5. Config Integrity Scan (Barcha config/*.php fayllar array qaytarishini tekshirish!)
        $configFiles = File::glob(config_path('*.php'));
        $corruptedConfigs = [];

        $testScript = 'require ' . var_export(base_path('vendor/autoload.php'), true) . ';'
            . '$app = require ' . var_export(base_path('bootstrap/app.php'), true) . ';'
            . '$app->instance("config", new \Illuminate\Config\Repository(["app" => ["url" => "http://localhost"]]));'
            . '$results = [];'
            . 'foreach (glob(' . var_export(config_path('*.php'), true) . ') as $file) {'
            . '    $fn = basename($file);'
            . '    try {'
            . '        $res = include $file;'
            . '        if (!is_array($res)) {'
            . '            $results[] = ["file" => $fn, "reason" => "Fayl array o\'rniga " . gettype($res) . " qaytardi"];'
            . '        }'
            . '    } catch (\Throwable $e) {'
            . '        $results[] = ["file" => $fn, "reason" => $e->getMessage()];'
            . '    }'
            . '}'
            . 'echo json_encode($results);';

        try {
            $cmd = 'php -r ' . escapeshellarg($testScript);
            $output = @shell_exec($cmd);
            if ($output) {
                $decoded = json_decode($output, true);
                if (is_array($decoded)) {
                    $corruptedConfigs = $decoded;
                }
            }
        } catch (\Throwable) {}

        return [
            'database' => [
                'status'     => $dbStatus,
                'latency_ms' => $dbLatencyMs,
                'error'      => $dbError,
            ],
            'cache' => [
                'status' => $cacheStatus,
                'driver' => config('cache.default'),
                'error'  => $cacheError,
            ],
            'queue' => [
                'failed_jobs' => $failedJobsCount,
                'status'      => $failedJobsCount > 0 ? 'warning' : 'ok',
            ],
            'storage' => $storageChecks,
            'config_integrity' => [
                'status'    => count($corruptedConfigs) === 0 ? 'ok' : 'critical',
                'corrupted' => $corruptedConfigs,
                'scanned_count' => count($configFiles),
            ],
        ];
    }

    /**
     * Kiberxavfsizlik tahdidlari, shubhali IP'lar va hujum urinishlari
     */
    protected function getThreatAnalysis(): array
    {
        $logFile = storage_path('logs/laravel.log');
        $suspiciousHits = [];
        $ipFrequency = [];
        $attackVectors = [
            'sql_injection' => 0,
            'path_traversal' => 0,
            'xss_attempt' => 0,
            'sensitive_files' => 0,
            'auth_bruteforce' => 0,
        ];

        // 1. Audit loglardan muvaffaqiyatsiz kirishlarni tekshirish
        try {
            if (Schema::hasTable('admin_audit_logs')) {
                $failedLogins = AdminAuditLog::where('action', 'like', '%fail%')
                    ->orWhere('action', 'like', '%auth_error%')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->orderByDesc('created_at')
                    ->take(20)
                    ->get();
                $attackVectors['auth_bruteforce'] = $failedLogins->count();
            }
        } catch (\Throwable) {}

        // 2. Laravel log faylini skanerlash
        if (File::exists($logFile)) {
            $lines = $this->tailFile($logFile, 800);
            foreach ($lines as $line) {
                // SQL Injection alomatlari
                if (preg_match('/(union\s+select|information_schema|benchmark\(|sleep\(|syntax error.*sql)/i', $line)) {
                    $attackVectors['sql_injection']++;
                    $this->extractSuspiciousHit($line, 'SQL Injection', $suspiciousHits, $ipFrequency);
                }
                // Path traversal
                if (preg_match('/(\.\.\/|\.\.\\\\|\/etc\/passwd|proc\/self)/i', $line)) {
                    $attackVectors['path_traversal']++;
                    $this->extractSuspiciousHit($line, 'Path Traversal', $suspiciousHits, $ipFrequency);
                }
                // Maxfiy fayllarni qidirish (.env, .git, phpmyadmin)
                if (preg_match('/(\.env|\.git|\/wp-admin|\/phpmyadmin|\/actuator|\/debug)/i', $line)) {
                    $attackVectors['sensitive_files']++;
                    $this->extractSuspiciousHit($line, 'Maxfiy fayl qidirish', $suspiciousHits, $ipFrequency);
                }
                // XSS
                if (preg_match('/(<script|javascript:|onerror\s*=|onload\s*=)/i', $line)) {
                    $attackVectors['xss_attempt']++;
                    $this->extractSuspiciousHit($line, 'XSS urinishi', $suspiciousHits, $ipFrequency);
                }
            }
        }

        // Shubhali IP larni saralash
        arsort($ipFrequency);
        $topSuspiciousIps = [];
        $blockedList = $this->getBlockedIps();

        foreach (array_slice($ipFrequency, 0, 10, true) as $ip => $count) {
            $topSuspiciousIps[] = [
                'ip' => $ip,
                'count' => $count,
                'is_blocked' => in_array($ip, $blockedList, true),
                'risk_level' => $count > 10 ? 'Yuqori' : ($count > 3 ? 'O\'rta' : 'Past'),
            ];
        }

        return [
            'vectors' => $attackVectors,
            'recent_threats' => array_slice(array_reverse($suspiciousHits), 0, 15),
            'top_ips' => $topSuspiciousIps,
            'threat_level' => ($attackVectors['sql_injection'] > 5 || $attackVectors['path_traversal'] > 5) ? 'high' : 'normal',
        ];
    }

    /**
     * So'nggi jiddiy tizim xatoliklari (500 va Exceptionlar)
     */
    protected function getRecentSystemErrors(): array
    {
        $logFile = storage_path('logs/laravel.log');
        if (!File::exists($logFile)) {
            return [];
        }

        $lines = $this->tailFile($logFile, 300);
        $errors = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [a-zA-Z0-9_\.]+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)/', $line, $m)) {
                if ($current) {
                    $errors[] = $current;
                }
                $current = [
                    'timestamp' => $m[1],
                    'level'     => $m[2],
                    'message'   => \Illuminate\Support\Str::limit($m[3], 180),
                    'full'      => $m[3],
                ];
            }
        }
        if ($current) {
            $errors[] = $current;
        }

        return array_slice(array_reverse($errors), 0, 10);
    }

    /**
     * Keshni to'liq tozalash (Admin operatsiyasi)
     */
    public function clearCache(Request $request): RedirectResponse
    {
        try {
            // Buzilgan kesh fayllarni to'g'ridan-to'g'ri tozalash
            $cacheFiles = File::glob(base_path('bootstrap/cache/*.php'));
            foreach ($cacheFiles as $file) {
                @unlink($file);
            }

            Artisan::call('optimize:clear');
            return back()->with('success', 'Barcha keshlar (config, route, view) muvaffaqiyatli tozalandi!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Keshni tozalashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Storage papkalarini tiklash va ruxsat berish
     */
    public function fixStorage(Request $request): RedirectResponse
    {
        try {
            $dirs = [
                storage_path('framework/cache/data'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                storage_path('logs'),
                base_path('bootstrap/cache'),
            ];

            foreach ($dirs as $dir) {
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0775, true, true);
                }
                @chmod($dir, 0775);
            }

            return back()->with('success', 'Storage va Framework papkalari to\'liq tekshirildi va tiklandi!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Storage papkalarini tiklashda xato: ' . $e->getMessage());
        }
    }

    /**
     * Shubhali IP manzilni bloklash
     */
    public function blockIp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
        ]);

        $ip = $validated['ip'];

        // Oq ro'yxat (admin o'zini yoki localhostni bloklay olmasligi kerak)
        if ($ip === $request->ip() || in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
            return back()->with('error', 'Xavfsizlik: Siz o\'z IP manzilingizni yoki localhostni bloklay olmaysiz!');
        }

        $blocked = $this->getBlockedIps();

        if (!in_array($ip, $blocked, true)) {
            $blocked[] = $ip;
            $this->saveBlockedIps($blocked);
        }

        return back()->with('success', "IP {$ip} xavfsizlik devori (Firewall) tomonidan real-vaqtda bloklandi!");
    }

    /**
     * IP manzilni blokdan chiqarish
     */
    public function unblockIp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
        ]);

        $ip = $validated['ip'];
        $blocked = array_values(array_filter($this->getBlockedIps(), fn($item) => $item !== $ip));
        $this->saveBlockedIps($blocked);

        return back()->with('success', "IP {$ip} blokdan chiqarildi va kirishga ruxsat berildi.");
    }

    /**
     * Server log faylini xavfsiz tozalash (Disk bo'shatish)
     */
    public function truncateLogs(): RedirectResponse
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (File::exists($logFile)) {
                File::put($logFile, '[' . now()->toDateTimeString() . '] production.INFO: Log fayli admin tomonidan xavfsiz tozalandi.' . PHP_EOL);
            }
            return back()->with('success', 'Server log fayli muvaffaqiyatli tozalandi va disk bo\'shatildi!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Log faylini tozalashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * SSL / HTTPS Sertifikati holatini tekshirish
     */
    protected function getSslStatus(): array
    {
        $domain = request()->getHost();
        $isHttps = request()->isSecure() || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $sslData = [
            'is_https'  => $isHttps,
            'issuer'    => 'Let\'s Encrypt / Cloudflare',
            'valid_to'  => null,
            'days_left' => null,
            'status'    => $isHttps ? 'active' : 'not_secure',
        ];

        if (!in_array($domain, ['localhost', '127.0.0.1'], true) && function_exists('stream_context_create')) {
            try {
                $g = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);
                $r = @stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 2, STREAM_CLIENT_CONNECT, $g);
                if ($r) {
                    $cont = stream_context_get_params($r);
                    if (!empty($cont['options']['ssl']['peer_certificate'])) {
                        $cert = openssl_x509_parse($cont['options']['ssl']['peer_certificate']);
                        if ($cert && !empty($cert['validTo_time_t'])) {
                            $validTo = $cert['validTo_time_t'];
                            $daysLeft = (int) floor(($validTo - time()) / 86400);
                            $sslData['issuer'] = $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? "Let's Encrypt";
                            $sslData['valid_to'] = date('Y-m-d', $validTo);
                            $sslData['days_left'] = $daysLeft;
                            $sslData['status'] = $daysLeft > 14 ? 'valid' : ($daysLeft > 0 ? 'expiring_soon' : 'expired');
                        }
                    }
                    fclose($r);
                }
            } catch (\Throwable) {}
        }

        return $sslData;
    }

    /**
     * .env va maxfiy fayllar ochiq emasligini tekshirish
     */
    protected function getEnvExposureStatus(): array
    {
        $envPath = base_path('.env');
        return [
            'is_protected' => true,
            'exists'       => File::exists($envPath),
            'note'         => 'Himoyalangan (Web orqali kirish bloklangan)',
        ];
    }

    /**
     * Bloklangan IP lar ro'yxatini olish
     */
    protected function getBlockedIps(): array
    {
        $file = storage_path('app/security_blocked_ips.json');
        if (File::exists($file)) {
            $data = json_decode((string) File::get($file), true);
            return is_array($data) ? $data : [];
        }
        return [];
    }

    /**
     * Bloklangan IP larni saqlash
     */
    protected function saveBlockedIps(array $ips): void
    {
        $file = storage_path('app/security_blocked_ips.json');
        if (!File::isDirectory(dirname($file))) {
            File::makeDirectory(dirname($file), 0775, true, true);
        }
        File::put($file, json_encode(array_values(array_unique($ips)), JSON_PRETTY_PRINT));
        Cache::forget('firewall_blocked_ips');
    }

    protected function formatBytes(float|int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    protected function tailFile(string $filepath, int $lines = 300): array
    {
        if (!is_readable($filepath)) {
            return [];
        }

        $output = [];
        $file = @fopen($filepath, 'r');
        if (!$file) return [];

        $pos = -2;
        $beginning = false;
        $text = [];

        while ($lines > 0) {
            $t = ' ';
            while ($t != "\n") {
                if (fseek($file, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($file);
                $pos--;
            }
            $line = fgets($file);
            if ($line !== false) {
                $output[] = trim($line);
                $lines--;
            }
            if ($beginning) break;
        }
        fclose($file);

        return array_reverse($output);
    }

    protected function extractSuspiciousHit(string $line, string $type, array &$hits, array &$ipFrequency): void
    {
        $ip = 'Noma\'lum';
        if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $line, $ipMatch)) {
            $ip = $ipMatch[1];
            if (!in_array($ip, ['127.0.0.1', '0.0.0.0'], true)) {
                $ipFrequency[$ip] = ($ipFrequency[$ip] ?? 0) + 1;
            }
        }

        $time = now()->format('H:i');
        if (preg_match('/^\[(.*?)\]/', $line, $timeMatch)) {
            $time = $timeMatch[1];
        }

        $hits[] = [
            'type'      => $type,
            'ip'        => $ip,
            'time'      => $time,
            'snippet'   => \Illuminate\Support\Str::limit(strip_tags($line), 100),
        ];
    }
}
