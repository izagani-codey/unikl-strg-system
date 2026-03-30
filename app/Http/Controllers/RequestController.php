<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Models\RequestType;
use App\Models\Request as GrantRequest;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;

class RequestController extends Controller
{
    /**
     * Enforce allowed status transitions by role.
     *
     * Status codes:
     * 1=Pending Verification
     * 2=With Staff 2
     * 3=Returned to Admission
     * 4=Returned to Staff 1
     * 5=Approved
     * 6=Declined
     */
    private function isValidTransition(string $role, int $currentStatus, int $newStatus): bool
    {
        if ($role === 'staff1') {
            // Staff1 can act only on active queue items.
            if (!in_array($currentStatus, [1, 4], true)) {
                return false;
            }

            // Staff1 actions:
            // 1 or 4 -> 2 (send to staff2), 3 (return to admission), 6 (decline)
            return in_array($newStatus, [2, 3, 6], true);
        }

        if ($role === 'staff2') {
            // Staff2 actions:
            // 2 -> 4 (return to staff1), 5 (approve), 6 (decline)
            // 5 -> 6 (override approved request to declined)
            // 6 -> 5 (override declined request to approved)
            if ($currentStatus === 2) {
                return in_array($newStatus, [4, 5, 6], true);
            }

            if ($currentStatus === 5) {
                return $newStatus === 6;
            }

            if ($currentStatus === 6) {
                return $newStatus === 5;
            }

            return false;
        }

        return false;

    
    }

    // ==========================================
    // ADMISSION — Submit new request
    // ==========================================

    public function create()
    {
        $requestTypes = RequestType::all();
        return view('requests.create', compact('requestTypes'));
    }

    public function store(StoreRequestRequest $request)
    {
        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('documents', 'public');
        }

        // Auto priority if deadline within 2 weeks
        $isPriority = false;
        if ($request->deadline) {
            $isPriority = now()->diffInDays($request->deadline) <= 14;
        }

        $grantRequest = GrantRequest::create([
            'user_id'         => Auth::id(),
            'request_type_id' => $request->request_type_id,
            'ref_number'      => 'REQ-' . strtoupper(uniqid()),
            'status_id'       => 1,
            'file_path'       => $path,
            'deadline'        => $request->deadline,
            'is_priority'     => $isPriority,
            'payload'         => [
                'amount'      => $request->amount,
                'description' => $request->description,
                'email'       => Auth::user()->email,
            ],
        ]);

        AuditLog::create([
            'request_id'  => $grantRequest->id,
            'actor_id'    => Auth::id(),
            'from_status' => 0,
            'to_status'   => 1,
            'note'        => 'Initial submission by applicant.',
            'created_at'  => now(),
        ]);

        $this->notifyRole(
            'staff1',
            'New request submitted',
            'A new request (' . $grantRequest->ref_number . ') requires verification.',
            route('requests.show', $grantRequest->id)
        );

        return redirect()->route('dashboard')
                         ->with('success', 'Request submitted successfully!');
    }

    // ==========================================
    // ADMISSION — Edit a returned request
    // ==========================================

    public function edit($id)
    {
        $grantRequest = GrantRequest::where('id', $id)
                                    ->where('user_id', Auth::id())
                                    ->whereIn('status_id', [3]) // only returned requests
                                    ->firstOrFail();

        $this->authorize('revise', $grantRequest);

        $requestTypes = RequestType::all();
        return view('requests.edit', compact('grantRequest', 'requestTypes'));
    }

    public function update(UpdateRequestRequest $request, $id)
    {
        $grantRequest = GrantRequest::where('id', $id)
                                    ->where('user_id', Auth::id())
                                    ->whereIn('status_id', [3])
                                    ->firstOrFail();

        $this->authorize('revise', $grantRequest);

        // New file uploaded
        $path = $grantRequest->file_path;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('documents', 'public');
        }

        $isPriority = false;
        if ($request->deadline) {
            $isPriority = now()->diffInDays($request->deadline) <= 14;
        }

        $oldStatus = $grantRequest->status_id;

        $grantRequest->update([
            'status_id'    => 1, // reset back to start
            'file_path'    => $path,
            'deadline'     => $request->deadline,
            'is_priority'  => $isPriority,
            'rejection_reason' => null, // clear old rejection reason
            'revision_count'   => $grantRequest->revision_count + 1,
            'payload'      => [
                'amount'      => $request->amount,
                'description' => $request->description,
                'email'       => Auth::user()->email,
            ],
        ]);

        AuditLog::create([
            'request_id'  => $grantRequest->id,
            'actor_id'    => Auth::id(),
            'from_status' => $oldStatus,
            'to_status'   => 1,
            'note'        => 'Resubmitted after revision. Revision #' . $grantRequest->revision_count,
            'created_at'  => now(),
        ]);

        $this->notifyRole(
            'staff1',
            'Request resubmitted',
            'Request ' . $grantRequest->ref_number . ' has been resubmitted and needs re-verification.',
            route('requests.show', $grantRequest->id)
        );

        return redirect()->route('dashboard')
                         ->with('success', 'Request resubmitted successfully!');
    }

    // ==========================================
    // ALL ROLES — View single request
    // ==========================================

    public function show($id)
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
        $this->recordRequestView($grantRequest);

        return view('requests.show', compact('grantRequest'));
    }

    private function recordRequestView(GrantRequest $grantRequest): void
    {
        if (! Auth::check()) {
            return;
        }

        AuditLog::create([
            'request_id'  => $grantRequest->id,
            'actor_id'    => Auth::id(),
            'from_status' => $grantRequest->status_id,
            'to_status'   => $grantRequest->status_id,
            'note'        => 'Viewed request details.',
            'created_at'  => now(),
        ]);
    }

    // ==========================================
    // Printable summary
    // ==========================================


    public function printSummary($id)
    {
        $grantRequest = GrantRequest::with([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'auditLogs' => fn ($q) => $q->with('actor')->orderBy('created_at'),
        ])->findOrFail($id);

        $this->authorize('print', $grantRequest);

        return view('requests.print', compact('grantRequest'));
    }

    // ==========================================
    // STAFF 2 — Export filtered request list
    // ==========================================
    public function exportCsv(Request $request)
    {
        $query = GrantRequest::query()->with([
            'requestType',
            'user',
            'verifiedBy',
            'recommendedBy',
        ])->latest('created_at');

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

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ref_number', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $filename = 'staff2-requests-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Ref Number',
            'Request Type',
            'Applicant Name',
            'Applicant Email',
            'Amount',
            'Submitted At',
            'Deadline',
            'Status',
            'Staff 1 (Verified By)',
            'Staff 2 (Recommended By)',
            'Staff Notes (latest)',
            'Rejection Reason',
        ];

        return response()->streamDownload(function () use ($query, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $query->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $req) {
                    fputcsv($handle, [
                        $req->ref_number,
                        $req->requestType?->name ?? '',
                        $req->user?->name ?? '',
                        $req->user?->email ?? '',
                        $req->payload['amount'] ?? '',
                        $req->created_at?->format('Y-m-d H:i') ?? '',
                        $req->deadline?->format('Y-m-d') ?? '',
                        $req->statusLabel(),
                        $req->verifiedBy?->name ?? '',
                        $req->recommendedBy?->name ?? '',
                        $req->staff_notes ?? '',
                        $req->rejection_reason ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    // ==========================================
    // STAFF 1 + STAFF 2 — Update workflow status
    // ==========================================

    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $user = Auth::user();

        $newStatus = (int) $request->validated()['status_id'];
        if (! $this->isValidTransition($user->role, (int) $grantRequest->status_id, $newStatus)) {
            return back()->with('error', 'Invalid status transition for your role.')->withInput();
        }

        $updateData = ['status_id' => $newStatus];
        $isOverride = false;

        if ($user->role === 'staff2' && $grantRequest->canBeOverridden()) {
            $isOverride = true;
            $updateData['is_overridden'] = true;
            $updateData['overridden_by'] = $user->id;

            if ($request->filled('override_reason')) {
                $updateData['override_reason'] = $request->override_reason;
            }
        }

        // Staff 1 verifying
        if ($user->role === 'staff1') {
            $updateData['verified_by']  = $user->id;
            $updateData['staff_notes']  = $request->notes;
        }

        // Staff 2 recommending
        if ($user->role === 'staff2') {
            $updateData['recommended_by'] = $user->id;
            $updateData['staff_notes']    = $request->notes;
        }

        // Rejection reason (visible to admission)
        if ($request->filled('rejection_reason')) {
            $updateData['rejection_reason'] = $request->rejection_reason;
        }

        $oldStatus = $grantRequest->status_id;
        $grantRequest->update($updateData);

        $logNote = $request->notes ?? null;
        if ($isOverride) {
            $logNote = 'OVERRIDE: ' . trim($request->override_reason ?? $request->notes ?? '');
        }

        AuditLog::create([
            'request_id'  => $grantRequest->id,
            'actor_id'    => $user->id,
            'from_status' => $oldStatus,
            'to_status'   => $newStatus,
            'note'        => $logNote,
            'created_at'  => now(),
        ]);

        $this->dispatchStatusNotification($grantRequest, (int) $request->status_id);

        return redirect()->route('requests.show', $id)
                         ->with('success', 'Status updated successfully.');
    }

    // ==========================================
    // STAFF 2 — Add comment for Staff 1
    // ==========================================

    public function addComment(StoreCommentRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);

        Comment::create([
            'request_id'  => $id,
            'user_id'     => Auth::id(),
            'body'        => $request->body,
            'is_internal' => true,
            'created_at'  => now(),
        ]);

        return redirect()->route('requests.show', $id)
                         ->with('success', 'Comment added.');
    }

    private function dispatchStatusNotification(GrantRequest $request, int $statusId): void
    {
        if ($statusId === 2) {
            $this->notifyRole('staff2', 'Request forwarded to Staff 2', 'Request ' . $request->ref_number . ' is ready for recommendation.', route('requests.show', $request->id));
            return;
        }

        if ($statusId === 3) {
            $this->notifyUser((int) $request->user_id, 'Request returned for revision', 'Request ' . $request->ref_number . ' has been returned to you with comments.', route('requests.show', $request->id));
            return;
        }

        if ($statusId === 4) {
            $this->notifyRole('staff1', 'Request returned to Staff 1', 'Request ' . $request->ref_number . ' has been returned for re-verification.', route('requests.show', $request->id));
            return;
        }

        if (in_array($statusId, [5, 6], true)) {
            $title = $statusId === 5 ? 'Request approved' : 'Request declined';
            $this->notifyUser((int) $request->user_id, $title, 'Request ' . $request->ref_number . ' has been updated. Please review the final decision.', route('requests.show', $request->id));
        }
    }

    private function notifyRole(string $role, string $title, string $message, ?string $link = null): void
    {
        $users = User::query()->where('role', $role)->get(['id']);

        foreach ($users as $recipient) {
            $this->notifyUser((int) $recipient->id, $title, $message, $link);
        }
    }

    private function notifyUser(int $userId, string $title, string $message, ?string $link = null): void
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }
}
