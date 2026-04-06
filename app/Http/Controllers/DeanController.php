<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DeanController extends Controller
{
    public function dashboard()
    {
        // Canonical dean dashboard is served by DashboardController at /dashboard.
        return redirect()->route('dashboard');
    }

    public function requests()
    {
        return redirect()->route('dashboard');
    }

    public function show($id)
    {
        return redirect()->route('requests.show', $id);
    }

    public function approve(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        // Check if dean can approve this request
        if (!$grantRequest->canBeActionedByDean()) {
            abort(403, 'You cannot approve this request at this stage.');
        }

        $notes = $httpRequest->input('notes');
        $grantRequest->approveByDean($dean, $notes);

        // Create notification for admission user
        $grantRequest->user->notifications()->create([
            'title' => 'Request Approved',
            'message' => "Your request {$grantRequest->ref_number} has been approved by the Dean.",
            'url' => route('requests.show', $grantRequest->id),
            'type' => 'success',
        ]);

        return redirect()->route('dean.dashboard')
            ->with('success', 'Request approved successfully!');
    }

    public function reject(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        // Check if dean can reject this request
        if (!$grantRequest->canBeActionedByDean()) {
            abort(403, 'You cannot reject this request at this stage.');
        }

        $reason = $httpRequest->input('reason');
        $grantRequest->rejectByDean($dean, $reason);

        // Create notification for admission user
        $grantRequest->user->notifications()->create([
            'title' => 'Request Declined',
            'message' => "Your request {$grantRequest->ref_number} has been declined by the Dean.",
            'url' => route('requests.show', $grantRequest->id),
            'type' => 'error',
        ]);

        return redirect()->route('dean.dashboard')
            ->with('success', 'Request rejected successfully!');
    }

    public function returnToStaff1(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        // Check if dean can return this request
        if (!$grantRequest->canBeActionedByDean()) {
            abort(403, 'You cannot return this request at this stage.');
        }

        $reason = $httpRequest->input('reason');
        $grantRequest->returnToStaff1($dean, $reason);

        // Create notification for staff1
        $staff1Users = User::where('role', 'staff1')->get();
        foreach ($staff1Users as $staff1) {
            $staff1->notifications()->create([
                'title' => 'Request Returned',
                'message' => "Request {$grantRequest->ref_number} has been returned to Staff 1 by the Dean.",
                'url' => route('requests.show', $grantRequest->id),
                'type' => 'warning',
            ]);
        }

        return redirect()->route('dean.dashboard')
            ->with('success', 'Request returned to Staff 1 successfully!');
    }

    public function returnToStaff2(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        // Check if dean can return this request
        if (!$grantRequest->canBeActionedByDean()) {
            abort(403, 'You cannot return this request at this stage.');
        }

        $reason = $httpRequest->input('reason');
        $grantRequest->returnToStaff2($dean, $reason);

        // Create notification for staff2
        $staff2Users = User::where('role', 'staff2')->get();
        foreach ($staff2Users as $staff2) {
            $staff2->notifications()->create([
                'title' => 'Request Returned',
                'message' => "Request {$grantRequest->ref_number} has been returned to Staff 2 by the Dean.",
                'url' => route('requests.show', $grantRequest->id),
                'type' => 'warning',
            ]);
        }

        return redirect()->route('dean.dashboard')
            ->with('success', 'Request returned to Staff 2 successfully!');
    }
}
