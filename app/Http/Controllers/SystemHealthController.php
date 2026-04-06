<?php

namespace App\Http\Controllers;

use App\Models\Request;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    /**
     * Display system health dashboard.
     */
    public function index(HttpRequest $request)
    {
        // Only allow admin users
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }

        $health = [
            'system' => $this->getSystemHealth(),
            'database' => $this->getDatabaseHealth(),
            'cache' => $this->getCacheHealth(),
            'storage' => $this->getStorageHealth(),
            'performance' => $this->getPerformanceMetrics(),
            'security' => $this->getSecurityStatus(),
        ];

        return view('system.health', compact('health'));
    }

    /**
     * Get overall system health.
     */
    private function getSystemHealth(): array
    {
        return [
            'status' => 'healthy',
            'uptime' => $this->getUptime(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'maintenance_mode' => app()->isDownForMaintenance(),
        ];
    }

    /**
     * Get database health metrics.
     */
    private function getDatabaseHealth(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            return [
                'status' => 'connected',
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'size_mb' => $this->getDatabaseSize(),
                'tables_count' => $this->getTableCount(),
                'slow_queries' => $this->getSlowQueriesCount(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cache health metrics.
     */
    private function getCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'ok';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $retrieved === $testValue ? 'working' : 'error',
                'driver' => config('cache.default'),
                'prefix' => config('cache.prefix'),
                'ttl' => config('cache.default_ttl', 3600),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get storage health metrics.
     */
    private function getStorageHealth(): array
    {
        $paths = [
            'storage' => storage_path(),
            'public' => public_path(),
            'logs' => storage_path('logs'),
            'uploads' => storage_path('app/uploads'),
        ];

        $storageInfo = [];
        foreach ($paths as $name => $path) {
            $storageInfo[$name] = [
                'path' => $path,
                'writable' => is_writable($path),
                'size_mb' => $this->getDirectorySize($path),
                'free_space_mb' => disk_free_space(dirname($path)) / 1024 / 1024,
            ];
        }

        return $storageInfo;
    }

    /**
     * Get performance metrics.
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'requests_today' => Request::whereDate('created_at', today())->count(),
            'active_users_today' => User::whereDate('last_login_at', today())->count(),
            'avg_response_time' => $this->getAverageResponseTime(),
            'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024,
            'cache_hit_rate' => $this->getCacheHitRate(),
            'slow_requests_today' => $this->getSlowRequestsCount(),
        ];
    }

    /**
     * Get security status.
     */
    private function getSecurityStatus(): array
    {
        return [
            'https_enabled' => request()->secure(),
            'debug_mode_off' => !config('app.debug'),
            'app_key_set' => !empty(config('app.key')),
            'session_secure' => config('session.secure'),
            'csrf_protection' => true,
            'file_validation' => config('system.features.strict_file_validation'),
            'audit_logging' => config('system.features.audit_logging'),
        ];
    }

    /**
     * Helper methods for metrics collection.
     */
    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return "Load: {$load[0]}";
        }
        return 'N/A';
    }

    private function getMemoryUsage(): array
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        return [
            'current_mb' => round($memory / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit_mb' => $this->getMemoryLimit(),
        ];
    }

    private function getCpuUsage(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2) . '%';
        }
        return 'N/A';
    }

    private function getDiskUsage(): array
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        
        return [
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_percent' => round((($total - $free) / $total) * 100, 2),
        ];
    }

    private function getMemoryLimit(): float
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return -1;
        }
        
        return $this->parseBytes($limit) / 1024 / 1024;
    }

    private function parseBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $bytes = (int) substr($value, 0, -1);
        
        return match ($unit) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };
    }

    private function getDirectorySize(string $path): float
    {
        $size = 0;
        foreach (glob(rtrim($path, '/') . '/*') as $file) {
            $size += is_file($file) ? filesize($file) : $this->getDirectorySize($file);
        }
        return round($size / 1024 / 1024, 2);
    }

    private function getDatabaseSize(): float
    {
        try {
            if (config('database.default') === 'sqlite') {
                return filesize(database_path(config('database.connections.sqlite.database'))) / 1024 / 1024;
            }
            
            $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()");
            return $result[0]->size ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTableCount(): int
    {
        try {
            if (config('database.default') === 'sqlite') {
                $tables = DB::select("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
                return $tables[0]->count ?? 0;
            }
            
            $tables = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()");
            return $tables[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getSlowQueriesCount(): int
    {
        // This would need to be implemented with actual performance data
        return 0;
    }

    private function getAverageResponseTime(): float
    {
        // This would need to be implemented with actual performance data
        return 0;
    }

    private function getCacheHitRate(): float
    {
        // This would need to be implemented with actual cache statistics
        return 0;
    }

    private function getSlowRequestsCount(): int
    {
        // This would need to be implemented with actual performance data
        return 0;
    }
}
