<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Request as GrantRequest;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Base query builder — we build the query differently per role
        // then apply filters on top before executing
        if ($user->role === 'staff1' || $user->role === 'staff2') {

            $query = GrantRequest::with('requestType', 'user', 'verifiedBy')
                                 ->latest();

            // Filter by status if provided
            if ($request->filled('status')) {
                $query->where('status_id', $request->status);
            }

            // Filter by request type if provided
            if ($request->filled('type')) {
                $query->where('request_type_id', $request->type);
            }

            // Filter by date range if provided
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search by ref number or submitter name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ref_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $displayRequests = $query->get();

        } else {
            // Admission — only their own, with same filters
            $query = GrantRequest::where('user_id', $user->id)
                                 ->with('requestType')
                                 ->latest();

            if ($request->filled('status')) {
                $query->where('status_id', $request->status);
            }

            if ($request->filled('type')) {
                $query->where('request_type_id', $request->type);
            }

            $displayRequests = $query->get();
        }

        // Pass request types for the filter dropdown
        $requestTypes = \App\Models\RequestType::all();

        return view('dashboard', compact('displayRequests', 'requestTypes'));
    }
}