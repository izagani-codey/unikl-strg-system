<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Request as GrantRequest;
use App\Models\User;
use App\Repositories\RequestRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RequestService
{
    public function __construct(
        private RequestRepository $requestRepository,
        private WorkflowTransitionService $workflowService,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new request.
     */
    public function createRequest(array $data, User $user): GrantRequest
    {
        $payload = [
            'amount' => $data['amount'],
            'description' => $data['description'],
        ];

        $filePath = $this->handleFileUpload($data['document'] ?? null);

        $requestData = [
            'user_id' => $user->id,
            'request_type_id' => $data['request_type_id'],
            'ref_number' => $this->requestRepository->generateReferenceNumber(),
            'status_id' => RequestStatus::PENDING_VERIFICATION->value,
            'payload' => $payload,
            'file_path' => $filePath,
            'deadline' => $data['deadline'] ?? null,
            'is_priority' => $data['priority'] ?? false,
        ];

        $request = $this->requestRepository->create($requestData);

        // Create audit log
        $this->createAuditLog($request, null, RequestStatus::PENDING_VERIFICATION, 'Request submitted');

        // Send notification to staff1
        $this->notificationService->notifyNewRequest($request);

        return $request;
    }

    /**
     * Update request status.
     */
    public function updateStatus(GrantRequest $request, int $statusId, User $user, array $data = []): void
    {
        $oldStatus = $request->status_id;
        $newStatus = RequestStatus::from($statusId);

        // Validate transition
        if (!$this->workflowService->canTransition($user->role, $oldStatus, $newStatus)) {
            throw new \InvalidArgumentException('Invalid status transition');
        }

        // Update request
        $updateData = ['status_id' => $statusId];

        // Add staff assignments
        if ($user->role === 'staff1' && $newStatus === RequestStatus::PENDING_RECOMMENDATION) {
            $updateData['verified_by'] = $user->id;
            $updateData['verified_at'] = now();
        }

        if ($user->role === 'staff2' && in_array($newStatus, [RequestStatus::APPROVED, RequestStatus::DECLINED])) {
            $updateData['recommended_by'] = $user->id;
            $updateData['recommended_at'] = now();
        }

        // Add notes and rejection reason
        if (!empty($data['notes'])) {
            $updateData['staff_notes'] = $data['notes'];
        }

        if (!empty($data['rejection_reason'])) {
            $updateData['rejection_reason'] = $data['rejection_reason'];
        }

        // Handle override for staff2
        if ($user->role === 'staff2' && $oldStatus === RequestStatus::PENDING_VERIFICATION) {
            $updateData['is_overridden'] = true;
            $updateData['overridden_by'] = $user->id;
            $updateData['override_reason'] = 'Staff2 override from pending verification';
        }

        $this->requestRepository->update($request, $updateData);

        // Create audit log
        $this->createAuditLog($request, $oldStatus, $newStatus, $data['notes'] ?? '');

        // Send notifications
        $this->handleStatusNotifications($request, $oldStatus, $newStatus, $user);
    }

    /**
     * Add comment to request.
     */
    public function addComment(GrantRequest $request, string $content, User $user): Comment
    {
        $comment = $request->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        // Notify relevant users
        $this->notificationService->notifyNewComment($comment);

        return $comment;
    }

    /**
     * Update request details.
     */
    public function updateRequest(GrantRequest $request, array $data, User $user): GrantRequest
    {
        $payload = [
            'amount' => $data['amount'],
            'description' => $data['description'],
        ];

        $filePath = $this->handleFileUpload($data['document'] ?? null, $request->file_path);

        $updateData = [
            'request_type_id' => $data['request_type_id'],
            'payload' => $payload,
            'file_path' => $filePath,
            'deadline' => $data['deadline'] ?? null,
            'is_priority' => $data['priority'] ?? false,
            'revision_count' => $request->revision_count + 1,
        ];

        $this->requestRepository->update($request, $updateData);

        // Reset status if returned to admission
        if ($request->status_id === RequestStatus::RETURNED_TO_ADMISSION->value) {
            $this->updateStatus($request, RequestStatus::PENDING_VERIFICATION->value, $user);
        }

        // Create audit log
        $this->createAuditLog($request, $request->status_id, RequestStatus::from($request->status_id), 'Request updated');

        return $request->fresh();
    }

    /**
     * Handle file upload.
     */
    private function handleFileUpload(?UploadedFile $file, ?string $existingPath = null): ?string
    {
        if (!$file) {
            return $existingPath;
        }

        // Delete old file if exists
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $file->store('requests', 'public');
    }

    /**
     * Create audit log entry.
     */
    private function createAuditLog(GrantRequest $request, ?int $fromStatus, RequestStatus $toStatus, string $note = ''): void
    {
        $request->auditLogs()->create([
            'actor_id' => Auth::id(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus->value,
            'note' => $note,
        ]);
    }

    /**
     * Handle status change notifications.
     */
    private function handleStatusNotifications(GrantRequest $request, int $oldStatus, RequestStatus $newStatus, User $user): void
    {
        match ($newStatus) {
            RequestStatus::RETURNED_TO_ADMISSION => $this->notificationService->notifyReturnedToAdmission($request, $user),
            RequestStatus::RETURNED_TO_STAFF_1 => $this->notificationService->notifyReturnedToStaff1($request, $user),
            RequestStatus::APPROVED => $this->notificationService->notifyApproved($request, $user),
            RequestStatus::DECLINED => $this->notificationService->notifyDeclined($request, $user),
            default => null
        };
    }

    /**
     * Get requests with filtering.
     */
    public function getFilteredRequests(array $filters, User $user, int $perPage = 15)
    {
        return $this->requestRepository->getFilteredRequests($filters, $user, $perPage);
    }

    /**
     * Get request for display.
     */
    public function getRequestForDisplay(int $id): GrantRequest
    {
        return $this->requestRepository->getForDisplay($id);
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(User $user): array
    {
        return $this->requestRepository->getDashboardStats($user);
    }

    /**
     * Get urgent requests.
     */
    public function getUrgentRequests(User $user): \Illuminate\Support\Collection
    {
        return $this->requestRepository->getUrgentRequests($user);
    }

    /**
     * Delete request with cleanup.
     */
    public function deleteRequest(GrantRequest $request, User $user): bool
    {
        // Delete associated file
        if ($request->file_path) {
            Storage::disk('public')->delete($request->file_path);
        }

        // Create audit log
        $this->createAuditLog($request, $request->status_id, RequestStatus::from($request->status_id), 'Request deleted');

        return $this->requestRepository->delete($request);
    }

    /**
     * Bulk update request statuses.
     */
    public function bulkUpdateStatus(array $requestIds, int $statusId, User $user, array $data = []): void
    {
        foreach ($requestIds as $requestId) {
            $request = $this->requestRepository->findWithRelations($requestId);
            if ($request && $this->canUserUpdateRequest($user, $request)) {
                $this->updateStatus($request, $statusId, $user, $data);
            }
        }
    }

    /**
     * Check if user can update request.
     */
    private function canUserUpdateRequest(User $user, GrantRequest $request): bool
    {
        // Staff can update based on workflow rules
        if (in_array($user->role, ['staff1', 'staff2'])) {
            return $this->workflowService->canTransition(
                $user->role, 
                $request->status_id, 
                RequestStatus::from($request->status_id)
            );
        }

        // Admission can only update their own returned requests
        if ($user->role === 'admission') {
            return $request->user_id === $user->id && 
                   $request->status_id === RequestStatus::RETURNED_TO_ADMISSION->value;
        }

        return false;
    }

    /**
     * Get request statistics for reporting.
     */
    public function getRequestStatistics(\Carbon\Carbon $fromDate, \Carbon\Carbon $toDate, ?string $role = null): array
    {
        $query = GrantRequest::whereBetween('created_at', [$fromDate, $toDate]);

        if ($role) {
            $query->whereHas('user', fn($q) => $q->where('role', $role));
        }

        return [
            'total' => $query->count(),
            'approved' => $query->where('status_id', RequestStatus::APPROVED->value)->count(),
            'declined' => $query->where('status_id', RequestStatus::DECLINED->value)->count(),
            'pending' => $query->whereIn('status_id', [
                RequestStatus::PENDING_VERIFICATION->value,
                RequestStatus::PENDING_RECOMMENDATION->value
            ])->count(),
            'average_amount' => $query->avg('payload->amount') ?? 0,
        ];
    }
}
