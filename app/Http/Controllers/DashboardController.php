<?php

namespace App\Http\Controllers;

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

        $displayRequests = $query->paginate(15);

        $statsBase = GrantRequest::query();
        if ($user->role === 'admission') {
            $statsBase->where('user_id', $user->id);
        }
        $formTemplates = \App\Models\FormTemplate::with('uploader')->latest('created_at')->get();
$urgentRequests = collect();
if (in_array($user->role, ['staff1', 'staff2'])) {
    $urgentRequests = GrantRequest::where('deadline', '<=', now()->addDays(3))
        ->whereNotIn('status_id', [5, 6])
        ->with('requestType', 'user')
        ->get();
}
return view('dashboard', compact('displayRequests', 'requestTypes', 'dashboardStats', 'formTemplates', 'urgentRequests'));

        $statusCounts = (clone $statsBase)
            ->selectRaw('status_id, COUNT(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        $dashboardStats = [
            'total' => (clone $statsBase)->count(),
            'pending_verification' => (int) ($statusCounts[1] ?? 0),
            'with_staff_2' => (int) ($statusCounts[2] ?? 0),
            'returned_to_admission' => (int) ($statusCounts[3] ?? 0),
            'returned_to_staff_1' => (int) ($statusCounts[4] ?? 0),
            'approved' => (int) ($statusCounts[5] ?? 0),
            'declined' => (int) ($statusCounts[6] ?? 0),
        ];

        $requestTypes = RequestType::all();

        return view('dashboard', compact('displayRequests', 'requestTypes', 'dashboardStats'));
    }

    private function applyFilters(Builder $query, Request $request, string $role): void
    {
        if ($request->filled('status')) {
            $query->where('status_id', $request->integer('status'));
        }

        if ($request->filled('type')) {
            $query->where('request_type_id', $request->integer('type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

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
    }
}
