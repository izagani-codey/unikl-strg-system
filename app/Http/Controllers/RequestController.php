<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Services\RequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends BaseController
{
    public function __construct(
        private RequestService $requestService
    ) {}

    // ==========================================
    // ALL ROLES — List Requests
    // ==========================================

    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Request::class);
        
        $user = $this->currentUser();
        $filters = $request->only([
            'search', 'status', 'type', 'priority', 
            'date_from', 'date_to', 'urgent'
        ]);

        $requests = $this->requestService->getFilteredRequests($filters, $user);
        $requestTypes = RequestType::all();
        $statuses = RequestStatus::getAllCases();

        return view('requests.index', compact('requests', 'requestTypes', 'statuses'));
    }

    // ==========================================
    // ADMISSION — Create & Edit
    // ==========================================

    public function create()
    {
        $this->authorize('create', \App\Models\Request::class);
        
        $requestTypes = RequestType::all();
        return view('requests.create', compact('requestTypes'));
    }

    public function store(StoreRequestRequest $request)
    {
        $this->authorize('create', \App\Models\Request::class);

        $data = $request->validated();
        $data['document'] = $request->file('document');

        $grantRequest = $this->requestService->createRequest($data, $this->currentUser());

        return $this->successResponse('Request submitted successfully!', 'dashboard');
    }

    public function edit($id)
    {
        $grantRequest = $this->requestService->getRequestForDisplay($id);
        $this->authorize('update', $grantRequest);

        $requestTypes = RequestType::all();
        return view('requests.edit', compact('grantRequest', 'requestTypes'));
    }

    public function update(UpdateRequestRequest $request, $id)
    {
        $grantRequest = $this->requestService->getRequestForDisplay($id);
        $this->authorize('update', $grantRequest);

        $data = $request->validated();
        $data['document'] = $request->file('document');

        $this->requestService->updateRequest($grantRequest, $data, $this->currentUser());

        return $this->successResponse('Request updated successfully!', 'dashboard');
    }

    // ==========================================
    // ALL ROLES — View Request
    // ==========================================

    public function show($id)
    {
        $grantRequest = $this->requestService->getRequestForDisplay($id);
        $this->authorize('view', $grantRequest);

        return view('requests.show', compact('grantRequest'));
    }

    // ==========================================
    // STAFF — Status Updates
    // ==========================================

    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $grantRequest = $this->requestService->getRequestForDisplay($id);
        $this->authorize('changeStatus', $grantRequest);

        $data = $request->validated();
        $this->requestService->updateStatus($grantRequest, $data['status_id'], $this->currentUser(), $data);

        return $this->successResponse('Request status updated successfully!');
    }

    // ==========================================
    // STAFF — Comments
    // ==========================================

    public function comment(StoreCommentRequest $request, $id)
    {
        $grantRequest = $this->requestService->getRequestForDisplay($id);
        $this->authorize('addComment', $grantRequest);

        $data = $request->validated();
        $comment = $this->requestService->addComment($grantRequest, $data['content'], $this->currentUser());

        return $this->successResponse('Comment added successfully!');
    }

    // ==========================================
    // ALL ROLES — Print/Export
    // ==========================================

    public function print($id)
    {
        $grantRequest = $this->requestService->getRequestForDisplay($id);
        $this->authorize('print', $grantRequest);

        return view('requests.print', compact('grantRequest'));
    }

    // ==========================================
    // STAFF 2 — Export to CSV
    // ==========================================

    public function export(Request $request)
    {
        $this->authorize('export', \App\Models\Request::class);

        $filters = $request->only([
            'status', 'type', 'date_from', 'date_to'
        ]);

        $requests = $this->requestService->getFilteredRequests($filters, $this->currentUser(), 1000);

        $csvData = $this->formatRequestsForCsv($requests);
        
        return response()->streamDownload(function () use ($csvData) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, array_keys($csvData[0]));
            
            // Data rows
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        }, 'requests-export-' . now()->format('Y-m-d') . '.csv');
    }

    /**
     * Format requests for CSV export.
     */
    private function formatRequestsForCsv($requests): array
    {
        $csvData = [];
        
        foreach ($requests as $request) {
            $csvData[] = [
                'Reference Number' => $request->ref_number,
                'Applicant Name' => $request->user->name,
                'Applicant Email' => $request->user->email,
                'Request Type' => $request->requestType->name,
                'Amount' => $request->payload['amount'] ?? 0,
                'Description' => $request->payload['description'] ?? '',
                'Status' => RequestStatus::from($request->status_id)->getLabel(),
                'Submitted Date' => $request->created_at->format('Y-m-d H:i:s'),
                'Deadline' => $request->deadline?->format('Y-m-d') ?? 'N/A',
                'Priority' => $request->is_priority ? 'High' : 'Normal',
                'Verified By' => $request->verifiedBy?->name ?? 'N/A',
                'Verified Date' => $request->verified_at?->format('Y-m-d H:i:s') ?? 'N/A',
                'Recommended By' => $request->recommendedBy?->name ?? 'N/A',
                'Recommended Date' => $request->recommended_at?->format('Y-m-d H:i:s') ?? 'N/A',
                'Revision Count' => $request->revision_count ?? 0,
                'Staff Notes' => $request->staff_notes ?? '',
            ];
        }
        
        return $csvData;
    }

    // ==========================================
    // ADMIN — Bulk Operations
    // ==========================================

    public function bulkUpdateStatus(Request $request)
    {
        $this->authorize('bulkUpdate', \App\Models\Request::class);

        $data = $request->validate([
            'request_ids' => 'required|array',
            'request_ids.*' => 'integer',
            'status_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $this->requestService->bulkUpdateStatus(
            $data['request_ids'], 
            $data['status_id'], 
            $this->currentUser(), 
            $data
        );

        return $this->successResponse(count($data['request_ids']) . ' requests updated successfully!');
    }

    // ==========================================
    // ADMIN — Delete Request
    // ==========================================

    public function destroy($id)
    {
        $grantRequest = $this->requestService->getRequestForDisplay($id);
        $this->authorize('delete', $grantRequest);

        $this->requestService->deleteRequest($grantRequest, $this->currentUser());

        return $this->successResponse('Request deleted successfully!', 'dashboard');
    }
}
