<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Services\WorkflowTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RequestController extends Controller
{
    // ==========================================
    // ADMISSION — Create & Edit
    // ==========================================

    public function create()
    {
        $this->authorize('create', GrantRequest::class);
        
        $requestTypes = RequestType::all();
        return view('requests.create', compact('requestTypes'));
    }

    public function store(StoreRequestRequest $request)
    {
        $this->authorize('create', GrantRequest::class);

        $payload = [
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
        ];

        $filePath = null;
        if ($request->hasFile('document')) {
            $filePath = $request->file('document')->store('requests', 'public');
        }

        $grantRequest = GrantRequest::create([
            'user_id' => Auth::id(),
            'request_type_id' => $request->input('request_type_id'),
            'ref_number' => $this->generateReferenceNumber(),
            'status_id' => RequestStatus::PENDING_VERIFICATION->value,
            'payload' => $payload,
            'file_path' => $filePath,
            'deadline' => $request->input('deadline'),
            'is_priority' => $request->boolean('priority', false),
        ]);

        return redirect()->route('dashboard')
                         ->with('success', 'Request submitted successfully!');
    }

    public function edit($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('update', $grantRequest);

        $requestTypes = RequestType::all();
        return view('requests.edit', compact('grantRequest', 'requestTypes'));
    }

    public function update(UpdateRequestRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('update', $grantRequest);

        $payload = [
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
        ];

        $filePath = $grantRequest->file_path;
        if ($request->hasFile('document')) {
            // Delete old file if exists
            if ($filePath) {
                \Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('document')->store('requests', 'public');
        }

        $grantRequest->update([
            'request_type_id' => $request->input('request_type_id'),
            'payload' => $payload,
            'file_path' => $filePath,
            'deadline' => $request->input('deadline'),
            'is_priority' => $request->boolean('priority', false),
            'revision_count' => $grantRequest->revision_count + 1,
        ]);

        // Reset workflow if returned to admission
        if ($grantRequest->status_id === RequestStatus::RETURNED_TO_ADMISSION->value) {
            WorkflowTransitionService::executeTransition(
                $grantRequest, 
                RequestStatus::PENDING_VERIFICATION,
                ['notes' => 'Resubmitted after revision']
            );
        }

        return redirect()->route('dashboard')
                         ->with('success', 'Request updated successfully!');
    }

    // ==========================================
    // ALL ROLES — View single request
    // ==========================================

    public function show($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('view', $grantRequest);
        
        $grantRequest->load([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'comments.user',
            'auditLogs.actor',
        ]);

        return view('requests.show', compact('grantRequest'));
    }

    // ==========================================
    // STAFF — Status transitions
    // ==========================================

    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('changeStatus', $grantRequest);

        $newStatus = RequestStatus::from($request->input('status_id'));
        
        try {
            $grantRequest = WorkflowTransitionService::executeTransition(
                $grantRequest,
                $newStatus,
                [
                    'notes' => $request->input('notes'),
                    'rejection_reason' => $request->input('rejection_reason'),
                ]
            );

            return redirect()->route('requests.show', $id)
                             ->with('success', 'Status updated successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ==========================================
    // STAFF — Add internal comment
    // ==========================================

    public function addComment(StoreCommentRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('addComment', $grantRequest);

        Comment::create([
            'request_id'  => $id,
            'user_id'     => auth()->id(),
            'content'     => $request->input('content'),
            'is_internal' => true,
            'created_at'  => now(),
        ]);

        return redirect()->back()
                         ->with('success', 'Comment added successfully.');
    }

    // ==========================================
    // STAFF 2 — Export functionality
    // ==========================================

    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', GrantRequest::class);

        $query = GrantRequest::query()->with([
            'requestType', 
            'user', 
            'verifiedBy', 
            'recommendedBy'
        ]);

        // Apply filters
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

        $requests = $query->get();

        $filename = 'requests_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($requests) {
            $file = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($file, [
                'Reference Number',
                'Request Type',
                'Applicant Name',
                'Applicant Email',
                'Status',
                'Amount',
                'Description',
                'Priority',
                'Deadline',
                'Created At',
                'Verified By',
                'Recommended By',
            ]);

            // CSV rows
            foreach ($requests as $request) {
                fputcsv($file, [
                    $request->ref_number,
                    $request->requestType->name,
                    $request->user->name,
                    $request->user->email,
                    $request->statusLabel(),
                    $request->payload['amount'] ?? '',
                    $request->payload['description'] ?? '',
                    $request->priorityLabel(),
                    $request->deadline?->format('Y-m-d'),
                    $request->created_at->format('Y-m-d H:i:s'),
                    $request->verifiedBy?->name,
                    $request->recommendedBy?->name,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==========================================
    // Print summary
    // ==========================================

    public function printSummary($id)
    {
        $grantRequest = GrantRequest::with([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'comments.user',
            'auditLogs.actor',
        ])->findOrFail($id);

        $this->authorize('view', $grantRequest);

        return view('requests.print', compact('grantRequest'));
    }

    // ==========================================
    // Helper methods
    // ==========================================

    private function generateReferenceNumber(): string
    {
        $prefix = 'REQ';
        $year = date('Y');
        $sequence = GrantRequest::whereYear('created_at', $year)->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}
