<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SystemHealthService
{
    /**
     * Check overall system health
     */
    public static function getSystemHealth(): array
    {
        return [
            'database' => self::checkDatabaseHealth(),
            'cache' => self::checkCacheHealth(),
            'storage' => self::checkStorageHealth(),
            'migrations' => self::checkMigrationHealth(),
            'models' => self::checkModelHealth(),
            'routes' => self::checkRouteHealth(),
            'views' => self::checkViewHealth(),
        ];
    }

    /**
     * Check database connectivity and performance
     */
    private static function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000; // Convert to milliseconds

            $connectionCount = DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'connections' => $connectionCount,
                'message' => 'Database is responding normally',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Database connection failed',
            ];
        }
    }

    /**
     * Check cache system
     */
    private static function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test_value', 60);
            $retrieved = Cache::get($testKey);
            
            $success = $retrieved === 'test_value';
            
            return [
                'status' => $success ? 'healthy' : 'unhealthy',
                'message' => $success ? 'Cache system is working' : 'Cache system failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Cache system error',
            ];
        }
    }

    /**
     * Check storage system
     */
    private static function checkStorageHealth(): array
    {
        try {
            $storagePath = storage_path();
            $writable = is_writable($storagePath);
            
            // Test file creation
            $testFile = $storagePath . '/health_test.txt';
            $writeSuccess = file_put_contents($testFile, 'test');
            $readSuccess = $writeSuccess && file_get_contents($testFile) === 'test';
            
            // Cleanup
            if (file_exists($testFile)) {
                unlink($testFile);
            }
            
            return [
                'status' => ($writable && $readSuccess) ? 'healthy' : 'unhealthy',
                'writable' => $writable,
                'message' => ($writable && $readSuccess) ? 'Storage is working' : 'Storage write issues detected',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Storage system error',
            ];
        }
    }

    /**
     * Check migration status
     */
    private static function checkMigrationHealth(): array
    {
        try {
            $migrations = DB::table('migrations')->orderBy('batch', 'desc')->get();
            $lastMigration = $migrations->first();
            
            return [
                'status' => 'healthy',
                'total_migrations' => $migrations->count(),
                'last_migration' => $lastMigration->migration,
                'last_batch' => $lastMigration->batch,
                'message' => 'Migrations are up to date',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Migration check failed',
            ];
        }
    }

    /**
     * Check model relationships
     */
    private static function checkModelHealth(): array
    {
        $issues = [];
        
        // Check critical models
        $criticalModels = [
            'App\Models\User',
            'App\Models\Request',
            'App\Models\RequestType',
            'App\Models\VotCode',
            'App\Models\FormTemplate',
        ];

        foreach ($criticalModels as $model) {
            try {
                $instance = new $model;
                $instance->getConnection()->getPdo(); // Test connection
            } catch (\Exception $e) {
                $issues[] = [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'unhealthy',
            'issues' => $issues,
            'message' => empty($issues) ? 'All models are accessible' : 'Model issues detected',
        ];
    }

    /**
     * Check route registration
     */
    private static function checkRouteHealth(): array
    {
        try {
            $routes = app('router')->getRoutes();
            $routeCount = count($routes);
            
            return [
                'status' => 'healthy',
                'total_routes' => $routeCount,
                'message' => "Routes are registered ({$routeCount} routes)",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Route system error',
            ];
        }
    }

    /**
     * Check view compilation
     */
    private static function checkViewHealth(): array
    {
        try {
            // Try to clear cache to test compilation
            $exitCode = 0;
            $output = [];
            
            exec('php artisan view:clear 2>&1', $output, $exitCode);
            
            $success = $exitCode === 0;
            
            return [
                'status' => $success ? 'healthy' : 'unhealthy',
                'exit_code' => $exitCode,
                'message' => $success ? 'Views compile successfully' : 'View compilation issues',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'View system error',
            ];
        }
    }

    /**
     * Get system performance metrics
     */
    public static function getPerformanceMetrics(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);
            
            return [
                'memory_current' => [
                    'bytes' => $memoryUsage,
                    'mb' => round($memoryUsage / 1024 / 1024, 2),
                ],
                'memory_peak' => [
                    'bytes' => $peakMemory,
                    'mb' => round($peakMemory / 1024 / 1024, 2),
                ],
                'execution_time' => microtime(true) - LARAVEL_START,
                'timestamp' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }
}
