<?php

namespace App\Http\Controllers;

use App\Models\Request as GrantRequest;
use App\Models\RequestType;

class Staff2AdminController extends Controller
{
    public function index()
    {
        $totalRequests = GrantRequest::count();
        $withStaff2 = GrantRequest::where('status_id', 2)->count();
        $approved = GrantRequest::where('status_id', 5)->count();
        $declined = GrantRequest::where('status_id', 6)->count();

        $byType = RequestType::query()
            ->withCount('requests')
            ->orderByDesc('requests_count')
            ->take(6)
            ->get();

        $recentHighPriority = GrantRequest::query()
            ->with('user', 'requestType')
            ->where('is_priority', true)
            ->latest()
            ->take(8)
            ->get();

        return view('staff2.admin-panel', compact(
            'totalRequests',
            'withStaff2',
            'approved',
            'declined',
            'byType',
            'recentHighPriority'
        ));
    }
}
