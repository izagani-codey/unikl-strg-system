<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class WorkflowTransitionService
{
    /**
     * Define allowed transitions for each role
     */
    public static function getAllowedTransitions(): array
    {
        return [
            'staff1' => [
                RequestStatus::PENDING_VERIFICATION->value => [
                    RequestStatus::PENDING_RECOMMENDATION->value,
                    RequestStatus::RETURNED_TO_ADMISSION->value,
                ],
                RequestStatus::RETURNED_TO_STAFF_1->value => [
                    RequestStatus::PENDING_RECOMMENDATION->value,
                    RequestStatus::RETURNED_TO_ADMISSION->value,
                ],
            ],
            'staff2' => [
                RequestStatus::PENDING_RECOMMENDATION->value => [
                    RequestStatus::APPROVED->value,
                    RequestStatus::DECLINED->value,
                ],
            ],
        ];
    }

    /**
     * Check if a transition is allowed for a user
     */
    public static function canTransition(Request $request, RequestStatus $newStatus, User $user): bool
    {
        $transitions = self::getAllowedTransitions();
        $roleTransitions = $transitions[$user->role] ?? [];

        if (!isset($roleTransitions[$request->status_id])) {
            return false;
        }

        return in_array($newStatus->value, $roleTransitions[$request->status_id]);
    }

    /**
     * Execute a status transition with full validation
     */
    public static function executeTransition(Request $request, RequestStatus $newStatus, array $data = []): Request
    {
        $user = Auth::user();
        
        if (!self::canTransition($request, $newStatus, $user)) {
            throw new AuthorizationException('You are not authorized to perform this status transition.');
        }

        $oldStatus = RequestStatus::from($request->status_id);
        
        // Create audit log
        self::createAuditLog($request, $oldStatus, $newStatus, $user, $data);

        // Update request
        $request->update([
            'status_id' => $newStatus->value,
            'staff_notes' => $data['notes'] ?? $request->staff_notes,
            'rejection_reason' => $data['rejection_reason'] ?? $request->rejection_reason,
        ]);

        // Update verification/recommendation tracking
        self::updateTrackingFields($request, $newStatus, $user);

        // Dispatch notifications
        self::dispatchNotifications($request, $oldStatus, $newStatus);

        return $request->fresh();
    }

    /**
     * Create audit log entry
     */
    private static function createAuditLog(Request $request, RequestStatus $from, RequestStatus $to, User $user, array $data): void
    {
        \App\Models\AuditLog::create([
            'request_id' => $request->id,
            'actor_id' => $user->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'note' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Update verification/recommendation tracking fields
     */
    private static function updateTrackingFields(Request $request, RequestStatus $newStatus, User $user): void
    {
        if ($newStatus === RequestStatus::PENDING_RECOMMENDATION) {
            $request->update(['verified_by' => $user->id]);
        } elseif ($newStatus->value >= 5) { // Approved or Declined
            $request->update(['recommended_by' => $user->id]);
        }
    }

    /**
     * Dispatch notifications based on transition
     */
    private static function dispatchNotifications(Request $request, RequestStatus $from, RequestStatus $to): void
    {
        match($to) {
            RequestStatus::PENDING_RECOMMENDATION => self::notifyStaff2($request),
            RequestStatus::RETURNED_TO_ADMISSION => self::notifyAdmission($request, 'Request returned for revision'),
            RequestStatus::RETURNED_TO_STAFF_1 => self::notifyStaff1($request),
            RequestStatus::APPROVED, RequestStatus::DECLINED => self::notifyAdmission($request, 
                $to === RequestStatus::APPROVED ? 'Request approved' : 'Request declined'
            ),
            default => null,
        };
    }

    private static function notifyStaff2(Request $request): void
    {
        self::createNotification(
            'staff2',
            'Request ready for recommendation',
            "Request {$request->ref_number} is ready for your review.",
            route('requests.show', $request->id)
        );
    }

    private static function notifyStaff1(Request $request): void
    {
        self::createNotification(
            'staff1',
            'Request returned for re-verification',
            "Request {$request->ref_number} has been returned for additional verification.",
            route('requests.show', $request->id)
        );
    }

    private static function notifyAdmission(Request $request, string $title): void
    {
        \App\Models\Notification::create([
            'user_id' => $request->user_id,
            'title' => $title,
            'message' => "Request {$request->ref_number} has been updated. Please review the changes.",
            'link' => route('requests.show', $request->id),
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    private static function createNotification(string $role, string $title, string $message, string $link): void
    {
        \App\Models\User::where('role', $role)->each(function ($user) use ($title, $message, $link) {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'is_read' => false,
                'created_at' => now(),
            ]);
        });
    }
}
