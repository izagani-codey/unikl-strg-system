<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RequestType;
use App\Models\Request as GrantRequest;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;

class RequestController extends Controller
{
    // ==========================================
    // ADMISSION — Submit new request
    // ==========================================

    public function create()
    {
        $requestTypes = RequestType::all();
        return view('requests.create', compact('requestTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_type_id' => 'required|exists:request_types,id',
            'amount'          => 'nullable|numeric',
            'description'     => 'required|string',
            'document'        => 'required|mimes:pdf,jpg,png|max:5120',
            'deadline'        => 'nullable|date',
        ]);

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

    public function update(Request $request, $id)
    {
        $grantRequest = GrantRequest::where('id', $id)
                                    ->where('user_id', Auth::id())
                                    ->whereIn('status_id', [3])
                                    ->firstOrFail();

        $this->authorize('revise', $grantRequest);

        $request->validate([
            'amount'      => 'nullable|numeric',
            'description' => 'required|string',
            'document'    => 'nullable|mimes:pdf,jpg,png|max:5120',
            'deadline'    => 'nullable|date',
        ]);

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

        return view('requests.show', compact('grantRequest'));
    }

    // ==========================================
    // Printable summary
    // ==========================================

    public function printSummary($id)
    {
        $grantRequest = GrantRequest::with(['user', 'requestType', 'verifiedBy', 'recommendedBy'])->findOrFail($id);

        $this->authorize('print', $grantRequest);

        return view('requests.print', compact('grantRequest'));
    }

    // ==========================================
    // STAFF 1 + STAFF 2 — Update workflow status
    // ==========================================

    public function updateStatus(Request $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $user = Auth::user();

        $this->authorize('updateStatus', $grantRequest);

        $request->validate([
            'status_id' => 'required|integer|between:1,6',
            'notes'     => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        $newStatus = (int) $request->status_id;
        if (! $this->isValidTransition($user->role, (int) $grantRequest->status_id, $newStatus)) {
            return back()->with('error', 'Invalid status transition for your role.')->withInput();
        }

        $updateData = ['status_id' => $newStatus];

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

        AuditLog::create([
            'request_id'  => $grantRequest->id,
            'actor_id'    => $user->id,
            'from_status' => $oldStatus,
            'to_status'   => $newStatus,
            'note'        => $request->notes ?? null,
            'created_at'  => now(),
        ]);

        $this->dispatchStatusNotification($grantRequest, $newStatus);

        return redirect()->route('requests.show', $id)
                         ->with('success', 'Status updated successfully.');
    }

    // ==========================================
    // STAFF 2 — Add comment for Staff 1
    // ==========================================

    public function addComment(Request $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('addComment', $grantRequest);

        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

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

    private function isValidTransition(string $role, int $currentStatus, int $targetStatus): bool
    {
        $transitionMap = [
            'staff1' => [
                1 => [2, 3, 6],
                4 => [2, 3, 6],
            ],
            'staff2' => [
                2 => [4, 5, 6],
            ],
        ];

        return in_array($targetStatus, $transitionMap[$role][$currentStatus] ?? [], true);
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
