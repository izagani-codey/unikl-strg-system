<?php

namespace App\Services;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\Comment;
use App\Models\Request as GrantRequest;
use App\Services\OverrideService;
use App\Services\WorkflowTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestWorkflowService
{
    protected $overrideService;
    protected $workflowTransitionService;

    public function __construct(
        OverrideService $overrideService,
        WorkflowTransitionService $workflowTransitionService
    ) {
        $this->overrideService = $overrideService;
        $this->workflowTransitionService = $workflowTransitionService;
    }

    public function updateRequestStatus(UpdateStatusRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $newStatus = $request->input('status_id');
        $user = Auth::user();

        // Check if user can perform this transition
        $this->authorizeStatusTransition($user, $grantRequest, $newStatus);

        // Execute the workflow transition
        $this->workflowTransitionService->executeTransition($grantRequest, $newStatus, [
            'notes' => $request->input('notes'),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return $grantRequest;
    }

    public function addComment(StoreCommentRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $user = Auth::user();

        Comment::create([
            'request_id' => $id,
            'user_id' => $user->id,
            'comment' => $request->input('comment'),
            'is_internal' => $request->input('is_internal', false),
        ]);

        return $grantRequest;
    }

    public function checkDeanApproval($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        
        // Check if request needs dean approval
        $needsDean = in_array($grantRequest->status_id, [
            \App\Enums\RequestStatus::PENDING_DEAN_VERIFICATION->value,
            \App\Enums\RequestStatus::PENDING_DEAN_APPROVAL->value,
        ]);
        
        if ($needsDean) {
            // Check if dean has already confirmed
            $deanConfirmed = $grantRequest->dean_confirmed_at !== null;
            $deanApprovedBy = $grantRequest->dean_approved_by !== null;
            
            return [
                'needs_dean' => true,
                'dean_confirmed' => $deanConfirmed,
                'dean_approved_by' => $deanApprovedBy,
                'dean_confirmed_at' => $deanConfirmed ? $grantRequest->dean_confirmed_at->format('Y-m-d H:i:s') : null,
                'message' => $deanApprovedBy ? 'Request has been approved by dean' : 'Request is pending dean approval',
            ];
        }
        
        return [
            'needs_dean' => false,
            'message' => 'Request does not require dean approval',
        ];
    }

    public function performOverride(Request $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $user = Auth::user();

        // Use existing OverrideService
        return $this->overrideService->performOverride($grantRequest, $user, [
            'action' => $request->input('action'),
            'reason' => $request->input('reason'),
        ]);
    }

    public function updatePriority(Request $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $user = Auth::user();

        // Check if user can update priority
        if (!in_array($user->role, ['staff1', 'staff2'])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only staff can update request priority.');
        }

        $priority = $request->input('priority');
        $grantRequest->update([
            'priority' => $priority,
            'priority_set_manually' => true,
            'priority_set_by' => $user->id,
            'priority_set_at' => now(),
        ]);

        return $grantRequest;
    }

    public function toggleOverrideMode(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'staff2') {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only Staff 2 can toggle override mode.');
        }

        return $user->toggleOverride();
    }

    private function authorizeStatusTransition($user, $grantRequest, $newStatus)
    {
        $allowedTransitions = \App\Http\Controllers\RequestController::allowedTransitions();
        $userRole = $user->role;
        $currentStatus = $grantRequest->status_id;

        if (!isset($allowedTransitions[$userRole][$currentStatus])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('No transitions available for current status.');
        }

        if (!in_array($newStatus, $allowedTransitions[$userRole][$currentStatus])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Invalid status transition.');
        }
    }
}
