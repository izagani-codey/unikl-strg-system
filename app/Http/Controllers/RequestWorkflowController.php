<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\Comment;
use App\Models\Request as GrantRequest;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestWorkflowController extends Controller
{
    protected $requestWorkflowService;

    public function __construct(RequestWorkflowService $requestWorkflowService)
    {
        $this->requestWorkflowService = $requestWorkflowService;
    }

    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        try {
            $result = $this->requestWorkflowService->updateRequestStatus($request, $id);
            
            return redirect()->route('requests.show', $id)->with('success', 'Status updated successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    public function addComment(StoreCommentRequest $request, $id)
    {
        try {
            $comment = $this->requestWorkflowService->addComment($request, $id);
            
            return back()->with('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add comment: ' . $e->getMessage());
        }
    }

    public function checkDeanApproval(Request $request, $id)
    {
        try {
            $deanStatus = $this->requestWorkflowService->checkDeanApproval($id);
            return response()->json($deanStatus);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check dean approval: ' . $e->getMessage()
            ], 500);
        }
    }

    public function performOverride(Request $request, $id)
    {
        try {
            $result = $this->requestWorkflowService->performOverride($request, $id);
            
            return back()->with('success', 'Override performed successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to perform override: ' . $e->getMessage());
        }
    }

    public function updatePriority(Request $request, $id)
    {
        try {
            $result = $this->requestWorkflowService->updatePriority($request, $id);
            
            return back()->with('success', 'Priority updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update priority: ' . $e->getMessage());
        }
    }

    public function toggleOverrideMode(Request $request)
    {
        try {
            $result = $this->requestWorkflowService->toggleOverrideMode($request);
            
            return back()->with('success', 'Override mode toggled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle override mode: ' . $e->getMessage());
        }
    }
}
