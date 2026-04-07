<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\FormTemplate;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\VotCode;
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
                RequestStatus::SUBMITTED->value, 
                RequestStatus::RETURNED->value
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
        $votCodes = \App\Models\VotCode::active()->ordered()->get();
        $user = Auth::user();
        return view('requests.create', compact('requestTypes', 'votCodes', 'user'));
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
            'status_id'               => RequestStatus::SUBMITTED->value,
            'payload'                 => [
                'description' => $request->input('description'),
                'dynamic_fields' => $request->input('dynamic_fields', []),
            ],
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
            // Get default template for this request type
            $requestType = RequestType::find($request->input('request_type_id'));
            $template = $requestType?->defaultTemplate;
            
            $pdfPath = RequestPdfService::generate($grantRequest, $template);
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

        $existingAdditionalDocuments = collect($grantRequest->payload['additional_documents'] ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();
        $newAdditionalDocuments = [];
        if ($request->hasFile('additional_documents')) {
            foreach ($request->file('additional_documents') as $document) {
                $newAdditionalDocuments[] = $document->store('requests/supporting-documents', 'public');
            }
        }
        $allAdditionalDocuments = array_values(array_merge($existingAdditionalDocuments, $newAdditionalDocuments));

        $payload = array_merge($grantRequest->payload ?? [], [
            'description' => $request->input('description'),
            'additional_documents' => $allAdditionalDocuments,
        ]);

        $grantRequest->update([
            'request_type_id'         => $request->input('request_type_id'),
            'payload'                 => $payload,
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
            'is_priority'             => false, // Admission edits should never set priority
            'revision_count'          => $grantRequest->revision_count + 1,
        ]);

        if ($grantRequest->status_id === RequestStatus::RETURNED->value) {
            WorkflowTransitionService::executeTransition(
                $grantRequest,
                RequestStatus::SUBMITTED,
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
            'templateUsages' => fn ($query) => $query->latest()->with('template'),
        ])->findOrFail($id);
        $this->authorize('view', $grantRequest);
        return view('requests.show', compact('grantRequest'));
    }

    public function printSummary($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('view', $grantRequest);
        return view('requests.print', compact('grantRequest'));
    }

    // ==========================================
    // STAFF — Status transitions
    // ==========================================

    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('changeStatus', $grantRequest);

        if (auth()->user()?->role === 'staff2' && $request->hasFile('staff2_supporting_documents')) {
            $existingAdditionalDocuments = collect($grantRequest->payload['additional_documents'] ?? [])
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->values()
                ->all();

            $newFiles = [];
            foreach ($request->file('staff2_supporting_documents') as $document) {
                $newFiles[] = $document->store('requests/supporting-documents', 'public');
            }

            $grantRequest->update([
                'payload' => array_merge($grantRequest->payload ?? [], [
                    'additional_documents' => array_values(array_merge($existingAdditionalDocuments, $newFiles)),
                ]),
            ]);
            $grantRequest = $grantRequest->fresh();
        }

        $newStatus = RequestStatus::from($request->input('status_id'));

        try {
            WorkflowTransitionService::executeTransition($grantRequest, $newStatus, [
                'notes'            => $request->input('notes'),
                'rejection_reason' => $request->input('rejection_reason'),
                'staff1_signature_data' => $request->input('staff1_signature_data'),
                'staff2_signature_data' => $request->input('staff2_signature_data'),
                'dean_signature_data' => $request->input('dean_signature_data'),
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
            'created_at'  => now(),
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

    /**
     * Get dynamic form fields HTML for request type
     */
    public function getDynamicFields(Request $request, $typeId)
    {
        $requestType = RequestType::findOrFail($typeId);
        
        if (!$requestType->field_schema || empty($requestType->field_schema)) {
            return response()->json(['html' => null, 'fields' => []]);
        }

        $html = view('components.dynamic-form-fields', [
            'fields' => $requestType->field_schema,
            'prefix' => 'dynamic_fields',
            'values' => [],
        ])->render();

        return response()->json([
            'html' => $html,
            'fields' => $requestType->field_schema,
        ]);
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

    private function normalizeVotItems(array $votItems): array
    {
        return collect($votItems)->map(function ($item) {
            return [
                'vot_code' => $item['vot_code'] ?? null, // Keep as vot_code to match form
                'amount' => (float) ($item['amount'] ?? 0),
                'description' => $item['description'] ?? '',
            ];
        })->filter(function ($item) {
            return !empty($item['vot_code']) && $item['amount'] > 0;
        })->values()->all();
    }

    private function getExistingSupportingDocuments(GrantRequest $request): array
    {
        return collect($request->payload['additional_documents'] ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();
    }

    private function appendSupportingDocuments(GrantRequest $request, array $uploadedFiles): array
    {
        $existingFiles = $this->getExistingSupportingDocuments($request);
        $newFiles = [];

        foreach ($uploadedFiles as $uploadedFile) {
            if ($uploadedFile) {
                $newFiles[] = $uploadedFile->store('requests/supporting-documents', 'public');
            }
        }

        return array_values(array_merge($existingFiles, $newFiles));
    }
}
