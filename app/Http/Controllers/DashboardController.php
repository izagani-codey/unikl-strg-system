<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\FormTemplate;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = GrantRequest::query()
            ->with('requestType', 'user', 'verifiedBy', 'recommendedBy')
            ->latest();

        if ($user->role === 'admission') {
            $query->where('user_id', $user->id);
        }

        $this->applyFilters($query, $request, $user->role);

        $displayRequests = $query->paginate(15)->withQueryString();
        $dashboardStats  = $this->buildStats($user);
        $requestTypes    = RequestType::all();
        $formTemplates   = FormTemplate::with('uploader')->latest('created_at')->get();

        $urgentRequests = collect();
        if (in_array($user->role, ['staff1', 'staff2'])) {
            $urgentRequests = GrantRequest::where('deadline', '<=', now()->addDays(3))
                ->whereNotIn('status_id', [RequestStatus::APPROVED->value, RequestStatus::DECLINED->value])
                ->with('requestType', 'user')
                ->orderBy('deadline')
                ->limit(10)
                ->get();
        }

        // Route to the correct dashboard view for this role.
        return view('dashboard.' . $user->role, compact(
            'displayRequests',
            'requestTypes',
            'dashboardStats',
            'formTemplates',
            'urgentRequests',
        ));
    }

    // ==========================================
    // Private helpers
    // ==========================================

    private function buildStats(mixed $user): array
    {
        $base = GrantRequest::query();
        if ($user->role === 'admission') {
            $base->where('user_id', $user->id);
        }

        $counts = (clone $base)
            ->selectRaw('status_id, COUNT(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        return [
            'total'                 => (clone $base)->count(),
            'pending_verification'  => (int) ($counts[RequestStatus::PENDING_VERIFICATION->value] ?? 0),
            'with_staff_2'          => (int) ($counts[RequestStatus::PENDING_RECOMMENDATION->value] ?? 0),
            'returned_to_admission' => (int) ($counts[RequestStatus::RETURNED_TO_ADMISSION->value] ?? 0),
            'returned_to_staff_1'   => (int) ($counts[RequestStatus::RETURNED_TO_STAFF_1->value] ?? 0),
            'approved'              => (int) ($counts[RequestStatus::APPROVED->value] ?? 0),
            'declined'              => (int) ($counts[RequestStatus::DECLINED->value] ?? 0),
            'high_priority'         => (clone $base)->where('is_priority', true)->count(),
        ];
    }

    private function applyFilters(Builder $query, Request $request, string $role): void
    {
        // Priority filter
        if ($request->filled('priority')) {
            $query->where('is_priority', (bool) $request->input('priority'));
        }

        // Status filter - using enum values
        if ($request->filled('status')) {
            $statusId = $request->integer('status');
            if (RequestStatus::tryFrom($statusId)) {
                $query->where('status_id', $statusId);
            }
        }

        // Request type filter
        if ($request->filled('type')) {
            $query->where('request_type_id', $request->integer('type'));
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function (Builder $q) use ($search, $role) {
                $q->where('ref_number', 'like', "%{$search}%")
                  ->orWhere('payload', 'like', "%{$search}%");

                if ($role !== 'admission') {
                    $q->orWhereHas('user', function (Builder $uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            });
        }

        // Urgent filter
        if ($request->filled('urgent') && $request->boolean('urgent')) {
            $query->where(function (Builder $q) {
                $q->where('deadline', '<=', now()->addDays(3))
                  ->whereNotIn('status_id', [RequestStatus::APPROVED->value, RequestStatus::DECLINED->value]);
            });
        }
    }
}
