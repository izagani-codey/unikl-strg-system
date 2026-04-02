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
use App\Models\VotCode;
use App\Services\OverrideService;
use App\Services\RequestPdfService;
use App\Services\WorkflowTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RequestController extends Controller
{
    // ==========================================
    // Global Requests Index (if needed)
    // ==========================================

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get base query based on user role
        $query = GrantRequest::with(['user', 'requestType', 'verifiedBy', 'recommendedBy']);
        
        // Filter based on role
        if ($user->isAdmission()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isStaff1()) {
            // Staff 1 can see requests that need verification
            $query->whereIn('status_id', [
                RequestStatus::PENDING_VERIFICATION->value, 
                RequestStatus::RETURNED_TO_STAFF_1->value
            ]);
        } elseif ($user->isStaff2()) {
            // Staff 2 can see all requests
            // No additional filtering needed
        }
        
        // Apply filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('ref_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($status = $request->get('status')) {
            $query->where('status_id', $status);
        }
        
        if ($type = $request->get('type')) {
            $query->where('request_type_id', $type);
        }
        
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        $requests = $query->orderBy('created_at', 'desc')->paginate(10);
        $statuses = RequestStatus::getAllCases();
        $requestTypes = RequestType::all();
        
        return view('requests.index', compact('requests', 'statuses', 'requestTypes'));
    }

    // ==========================================
    // ADMISSION — Create
    // ==========================================

    public function create()
    {
        $this->authorize('create', GrantRequest::class);
        $requestTypes = RequestType::all();
        $user = Auth::user();
        return view('requests.create', compact('requestTypes', 'user'));
    }

    public function store(StoreRequestRequest $request)
    {
        $this->authorize('create', GrantRequest::class);

        $user = Auth::user();

        // Normalize and calculate total from VOT items
        $votItems = $this->normalizeVotItems($request->input('vot_items', []));
        $total = collect($votItems)->sum(fn($item) => (float) ($item['amount'] ?? 0));

        // Calculate automatic priority based on deadline (staff only)
        $isPriority = false; // Admission cannot set priority
        $deadline = $request->input('deadline');
        
        // Only staff can have priority, admission users get normal priority
        if ($deadline && !$user->isAdmission()) {
            $daysUntil = now()->diffInDays(\Carbon\Carbon::parse($deadline), false);
            if ($daysUntil <= 5 && $daysUntil >= 0) {
                $isPriority = true;
            }
        }

        // Optional supplementary file upload
        $filePath = null;
        if ($request->hasFile('document')) {
            $filePath = $request->file('document')->store('requests/attachments', 'public');
        }

        $grantRequest = GrantRequest::create([
            'user_id'                 => $user->id,
            'request_type_id'         => $request->input('request_type_id'),
            'ref_number'              => $this->generateReferenceNumber(),
            'status_id'               => RequestStatus::PENDING_VERIFICATION->value,
            'payload'                 => ['description' => $request->input('description')],
            'vot_items'               => $votItems,
            'total_amount'            => $total,
            // Snapshot submitter profile at submission time
            'submitter_staff_id'      => $user->staff_id,
            'submitter_designation'   => $user->designation,
            'submitter_department'    => $user->department,
            'submitter_phone'         => $user->phone,
            'submitter_employee_level'=> $user->employee_level,
            // Signature
            'signature_data'          => $request->input('signature_data'),
            'signed_at'               => now(),
            'submitted_at'            => now(),
            'file_path'               => $filePath,
            'deadline'                => $request->input('deadline'),
            'is_priority'             => $isPriority,
        ]);

        // Generate filled PDF and attach it
        try {
            $pdfPath = RequestPdfService::generate($grantRequest);
            $grantRequest->update(['file_path' => $filePath ?? $pdfPath]);
        } catch (\Exception $e) {
            // PDF generation failure should not block submission
            \Log::warning('PDF generation failed for ' . $grantRequest->ref_number . ': ' . $e->getMessage());
        }

        return redirect()->route('dashboard')
            ->with('success', 'Request submitted successfully! Reference: ' . $grantRequest->ref_number);
    }

    // ==========================================
    // ADMISSION — Edit (returned requests)
    // ==========================================

    public function edit($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('update', $grantRequest);
        $requestTypes = RequestType::all();
        $user = Auth::user();
        return view('requests.edit', compact('grantRequest', 'requestTypes', 'user'));
    }

    public function update(UpdateRequestRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('update', $grantRequest);

        $user = Auth::user();
        $votItems = $this->normalizeVotItems($request->input('vot_items', []));
        $total = collect($votItems)->sum(fn($item) => (float) ($item['amount'] ?? 0));

        $filePath = $grantRequest->file_path;
        if ($request->hasFile('document')) {
            \Storage::disk('public')->delete($filePath);
            $filePath = $request->file('document')->store('requests/attachments', 'public');
        }

        $grantRequest->update([
            'request_type_id'         => $request->input('request_type_id'),
            'payload'                 => ['description' => $request->input('description')],
            'vot_items'               => $votItems,
            'total_amount'            => $total,
            'submitter_staff_id'      => $user->staff_id,
            'submitter_designation'   => $user->designation,
            'submitter_department'    => $user->department,
            'submitter_phone'         => $user->phone,
            'submitter_employee_level'=> $user->employee_level,
            'signature_data'          => $request->input('signature_data') ?: $grantRequest->signature_data,
            'signed_at'               => $request->input('signature_data') ? now() : $grantRequest->signed_at,
            'file_path'               => $filePath,
            'deadline'                => $request->input('deadline'),
            'is_priority'             => $request->boolean('priority', false),
            'revision_count'          => $grantRequest->revision_count + 1,
        ]);

        if ($grantRequest->status_id === RequestStatus::RETURNED_TO_ADMISSION->value) {
            WorkflowTransitionService::executeTransition(
                $grantRequest,
                RequestStatus::PENDING_VERIFICATION,
                ['notes' => 'Resubmitted after revision']
            );
        }

        // Re-generate PDF only when no original attachment is being preserved.
        try {
            $pdfPath = RequestPdfService::generate($grantRequest->fresh());
            $keepAttachmentPath = is_string($filePath) && str_starts_with($filePath, 'requests/attachments/');
            $grantRequest->update(['file_path' => $keepAttachmentPath ? $filePath : $pdfPath]);
        } catch (\Exception $e) {
            \Log::warning('PDF re-generation failed: ' . $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'Request updated and resubmitted.');
    }

    // ==========================================
    // ALL ROLES — View
    // ==========================================

    public function show($id)
    {
        $grantRequest = GrantRequest::with([
            'user', 'requestType', 'verifiedBy', 'recommendedBy',
            'comments.user', 'auditLogs.actor',
        ])->findOrFail($id);
        $this->authorize('view', $grantRequest);
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
            WorkflowTransitionService::executeTransition($grantRequest, $newStatus, [
                'notes'            => $request->input('notes'),
                'rejection_reason' => $request->input('rejection_reason'),
            ]);
            return redirect()->route('requests.show', $id)->with('success', 'Status updated successfully.');
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
        ]);

        return redirect()->back()->with('success', 'Comment added successfully.');
    }

    // ==========================================
    // STAFF 2 — Export to Excel
    // ==========================================

    public function exportExcel(Request $request)
    {
        $this->authorize('viewAny', GrantRequest::class);

        $query = GrantRequest::query()->with(['requestType', 'user', 'verifiedBy', 'recommendedBy']);

        if ($request->filled('status'))    $query->where('status_id', $request->integer('status'));
        if ($request->filled('type'))      $query->where('request_type_id', $request->integer('type'));
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->input('date_from'));
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->input('date_to'));

        $requests = $query->latest()->get();

        return \App\Services\ExcelExportService::exportRequests($requests);
    }

    // ==========================================
    // Print summary
    // ==========================================

    public function printSummary($id)
    {
        $grantRequest = GrantRequest::with([
            'user', 'requestType', 'verifiedBy', 'recommendedBy',
            'comments.user', 'auditLogs.actor',
        ])->findOrFail($id);
        $this->authorize('view', $grantRequest);
        return view('requests.print', compact('grantRequest'));
    }

    // ==========================================
    // Download generated PDF
    // ==========================================

    public function downloadPdf($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('view', $grantRequest);

        $storedPath = $grantRequest->file_path;
        $isStoredPdf = is_string($storedPath)
            && strtolower(pathinfo($storedPath, PATHINFO_EXTENSION)) === 'pdf'
            && \Storage::disk('public')->exists($storedPath);

        if ($isStoredPdf) {
            return \Storage::disk('public')->download($storedPath, $grantRequest->ref_number . '.pdf');
        }

        try {
            $generatedPdfPath = RequestPdfService::generate($grantRequest->fresh());
            return \Storage::disk('public')->download($generatedPdfPath, $grantRequest->ref_number . '.pdf');
        } catch (\Exception $e) {
            \Log::warning('PDF download generation failed for ' . $grantRequest->ref_number . ': ' . $e->getMessage());
            return back()->with('error', 'PDF not available for this request.');
        }
    }

    // ==========================================
    // Staff 2 Override Actions
    // ==========================================

    public function performOverride(Request $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('override', $grantRequest);

        $request->validate([
            'action_type' => 'required|in:approve,reject_reverse,bypass_verification,priority_override',
            'reason' => 'required|string|min:10|max:500',
            'confirm_reinstate' => 'nullable|accepted',
            'confirmation_phrase' => 'nullable|string|max:20',
        ]);

        if ($request->input('action_type') === 'reject_reverse') {
            $request->validate([
                'confirm_reinstate' => 'required|accepted',
                'confirmation_phrase' => 'required|in:REINSTATE',
            ], [
                'confirm_reinstate.required' => 'Please confirm reinstatement before proceeding.',
                'confirmation_phrase.in' => 'Type REINSTATE to confirm this sensitive action.',
            ]);
        }

        try {
            OverrideService::performOverride($grantRequest, $request->input('action_type'), $request->input('reason'));
            return redirect()->route('requests.show', $id)->with('success', 'Override action completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Override failed: ' . $e->getMessage());
        }
    }

    public function toggleOverrideMode(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isStaff2()) {
            return back()->with('error', 'Only Staff 2 can enable override mode.');
        }

        $user->toggleOverride();
        
        $status = $user->override_enabled ? 'enabled' : 'disabled';
        return back()->with('success', "Override mode {$status}.");
    }

    /**
     * Update request priority
     */
    public function updatePriority(Request $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('changeStatus', $grantRequest);

        $request->validate([
            'is_priority' => 'required|boolean',
        ]);

        $grantRequest->update([
            'is_priority' => $request->boolean('is_priority'),
        ]);

        $action = $request->boolean('is_priority') ? 'set to high priority' : 'removed from high priority';
        return back()->with('success', "Request priority {$action}.");
    }

    // ==========================================
    // Helpers
    // ==========================================

    private function generateReferenceNumber(): string
    {
        $prefix   = 'STRG';
        $year     = date('Y');
        $sequence = GrantRequest::whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }

    private function normalizeVotItems(array $rawItems): array
    {
        $votLookup = VotCode::query()
            ->pluck('description', 'code')
            ->toArray();

        return collect($rawItems)
            ->map(function ($item) use ($votLookup) {
                $code = trim((string) ($item['vot_code'] ?? ''));
                $amount = (float) ($item['amount'] ?? 0);

                if ($code === '' || !array_key_exists($code, $votLookup)) {
                    return null;
                }

                return [
                    'vot_code' => $code,
                    'description' => $votLookup[$code],
                    'amount' => $amount,
                ];
            })
            ->filter(fn ($item) => $item !== null)
            ->values()
            ->all();
    }
}
