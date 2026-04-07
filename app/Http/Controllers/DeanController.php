<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Services\WorkflowTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeanController extends Controller
{
    public function dashboard()
    {
        return redirect()->route('dashboard');
    }

    public function requests()
    {
        return redirect()->route('dashboard');
    }

    public function approve(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        WorkflowTransitionService::executeTransition(
            $grantRequest,
            RequestStatus::DEAN_APPROVED,
            [
                'notes' => $httpRequest->input('notes'),
                'dean_signature_data' => $httpRequest->input('dean_signature_data')
            ]
        );

        return redirect()->route('dashboard')
            ->with('success', 'Request approved successfully!');
    }

    public function reject(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        WorkflowTransitionService::executeTransition(
            $grantRequest,
            RequestStatus::REJECTED,
            [
                'rejection_reason' => $httpRequest->input('reason'),
                'dean_signature_data' => $httpRequest->input('dean_signature_data')
            ]
        );

        return redirect()->route('dashboard')
            ->with('success', 'Request rejected successfully!');
    }

    public function returnToStaff1(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        WorkflowTransitionService::executeTransition(
            $grantRequest,
            RequestStatus::RETURNED,
            ['notes' => $httpRequest->input('reason')]
        );

        return redirect()->route('dashboard')
            ->with('success', 'Request returned successfully!');
    }

    public function returnToStaff2(Request $httpRequest, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $dean = Auth::user();

        WorkflowTransitionService::executeTransition(
            $grantRequest,
            RequestStatus::RETURNED,
            ['notes' => $httpRequest->input('reason')]
        );

        return redirect()->route('dashboard')
            ->with('success', 'Request returned successfully!');
    }
}
