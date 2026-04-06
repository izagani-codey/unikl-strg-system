<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Models\RequestType;
use App\Models\User;
use App\Repositories\RequestRepository;
use App\Repositories\StatisticsRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EnhancedDashboardService
{
    public function __construct(
        private RequestRepository $requestRepository,
        private StatisticsRepository $statisticsRepository,
        private UserRepository $userRepository
    ) {}

    /**
     * Get complete dashboard data for user with caching.
     */
    public function getDashboardData(User $user, array $filters = []): array
    {
        $cacheKey = "dashboard_{$user->id}_" . md5(serialize($filters));
        $cacheDuration = config('system.settings.cache_duration_minutes', 60);

        return Cache::remember($cacheKey, $cacheDuration, function () use ($user, $filters) {
            return [
                'displayRequests' => $this->requestRepository->getFilteredRequests($filters, $user),
                'dashboardStats' => $this->statisticsRepository->getDashboardStats($user),
                'requestTypes' => $this->getCachedRequestTypes(),
                'formTemplates' => $this->getCachedFormTemplates(),
                'urgentRequests' => $this->requestRepository->getUrgentRequests($user),
                'user' => $user,
                'filters' => $filters,
                'performanceMetrics' => $this->getPerformanceMetrics($user),
                'recentActivity' => $this->getRecentActivity($user),
            ];
        });
    }

    /**
     * Get cached request types.
     */
    private function getCachedRequestTypes(): Collection
    {
        return Cache::remember('request_types_active', 3600, function () {
            return RequestType::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description']);
        });
    }

    /**
     * Get cached form templates.
     */
    private function getCachedFormTemplates(): Collection
    {
        return Cache::remember('form_templates_active', 3600, function () {
            return FormTemplate::with('uploader')
                ->where('is_active', true)
                ->latest('created_at')
                ->get(['id', 'title', 'file_path', 'uploaded_by', 'created_at']);
        });
    }

    /**
     * Get performance metrics for the user.
     */
    private function getPerformanceMetrics(User $user): array
    {
        if (!config('system.features.advanced_analytics', true)) {
            return [];
        }

        $cacheKey = "performance_metrics_{$user->id}";
        return Cache::remember($cacheKey, 1800, function () use ($user) {
            $thirtyDaysAgo = now()->subDays(30);
            
            $userRequests = \App\Models\Request::where('user_id', $user->id)
                ->where('created_at', '>=', $thirtyDaysAgo);

            return [
                'avg_processing_time' => $this->calculateAverageProcessingTime($userRequests),
                'approval_rate' => $this->calculateApprovalRate($userRequests),
                'requests_per_week' => $this->calculateRequestsPerWeek($userRequests),
                'peak_day' => $this->findPeakSubmissionDay($userRequests),
            ];
        });
    }

    /**
     * Get recent activity for the user.
     */
    private function getRecentActivity(User $user): array
    {
        if (!config('system.features.audit_logging', true)) {
            return [];
        }

        $cacheKey = "recent_activity_{$user->id}";
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return \App\Models\ActivityLog::where('user_id', $user->id)
                ->with(['request' => function ($query) {
                    $query->select('id', 'ref_number', 'title');
                }])
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'action' => $activity->action,
                        'description' => $activity->description,
                        'ip_address' => $activity->ip_address,
                        'created_at' => $activity->created_at->diffForHumans(),
                        'request' => $activity->request ? [
                            'id' => $activity->request->id,
                            'ref_number' => $activity->request->ref_number,
                            'title' => $activity->request->title,
                        ] : null,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Calculate average processing time for requests.
     */
    private function calculateAverageProcessingTime($requests): float
    {
        $completedRequests = $requests->clone()
            ->whereIn('status_id', [8, 9]) // Approved or Declined
            ->whereNotNull('updated_at');

        if ($completedRequests->count() === 0) {
            return 0;
        }

        return $completedRequests->avg(function ($request) {
            return $request->created_at->diffInDays($request->updated_at);
        });
    }

    /**
     * Calculate approval rate for requests.
     */
    private function calculateApprovalRate($requests): float
    {
        $totalCompleted = $requests->clone()
            ->whereIn('status_id', [8, 9])
            ->count();

        if ($totalCompleted === 0) {
            return 0;
        }

        $approvedCount = $requests->clone()
            ->where('status_id', 8) // Approved
            ->count();

        return round(($approvedCount / $totalCompleted) * 100, 1);
    }

    /**
     * Calculate requests per week.
     */
    private function calculateRequestsPerWeek($requests): float
    {
        $weeksPeriod = max(1, now()->diffInDays($requests->min('created_at')) / 7);
        
        return round($requests->count() / $weeksPeriod, 1);
    }

    /**
     * Find peak submission day.
     */
    private function findPeakSubmissionDay($requests): string
    {
        $dayCounts = $requests
            ->groupBy(function ($request) {
                return $request->created_at->format('l');
            })
            ->map->count();

        return $dayCounts->sortDesc()->keys()->first() ?? 'N/A';
    }

    /**
     * Clear dashboard cache for user.
     */
    public function clearUserCache(User $user): void
    {
        $pattern = "dashboard_{$user->id}_*";
        Cache::forget($pattern);
        Cache::forget("performance_metrics_{$user->id}");
        Cache::forget("recent_activity_{$user->id}");
    }

    /**
     * Clear system-wide caches.
     */
    public function clearSystemCache(): void
    {
        Cache::forget('request_types_active');
        Cache::forget('form_templates_active');
    }
}
