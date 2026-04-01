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
                    RequestStatus::DECLINED->value,
                    RequestStatus::PENDING_DEAN_VERIFICATION->value, // Dean confirmation by Staff 1
                ],
                RequestStatus::RETURNED_TO_STAFF_1->value => [
                    RequestStatus::PENDING_RECOMMENDATION->value,
                    RequestStatus::RETURNED_TO_ADMISSION->value,
                    RequestStatus::PENDING_DEAN_VERIFICATION->value, // Dean confirmation by Staff 1
                ],
            ],
            'staff2' => [
                RequestStatus::PENDING_RECOMMENDATION->value => [
                    RequestStatus::PENDING_DEAN_VERIFICATION->value, // Dean confirmation by Staff 2
                    RequestStatus::RETURNED_TO_STAFF_2->value,
                    RequestStatus::DECLINED->value,
                ],
                RequestStatus::RETURNED_TO_STAFF_2->value => [
                    RequestStatus::PENDING_DEAN_VERIFICATION->value, // Dean confirmation by Staff 2
                    RequestStatus::DECLINED->value,
                ],
            ],
            'dean' => [
                RequestStatus::PENDING_DEAN_APPROVAL->value => [
                    RequestStatus::APPROVED->value,
                    RequestStatus::RETURNED_TO_STAFF_1->value,
                    RequestStatus::RETURNED_TO_STAFF_2->value,
                    RequestStatus::DECLINED->value,
                ],
            ],
            'admission' => [
                RequestStatus::RETURNED_TO_ADMISSION->value => [
                    RequestStatus::PENDING_VERIFICATION->value,
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
        } elseif ($newStatus === RequestStatus::PENDING_DEAN_APPROVAL) {
            $request->update(['recommended_by' => $user->id]);
        } elseif ($newStatus === RequestStatus::PENDING_DEAN_VERIFICATION) {
            // Dean confirmation by staff
            $request->update([
                'dean_approved_by' => $user->id,
                'dean_approved_at' => now(),
                'dean_notes' => 'Confirmed by ' . ($user->role === 'staff1' ? 'Staff 1' : 'Staff 2') . ' on behalf of Dean',
            ]);
        } elseif (in_array($newStatus, [RequestStatus::APPROVED, RequestStatus::DECLINED]) && $user->isDean()) {
            $request->update([
                'dean_approved_by' => $user->id,
                'dean_approved_at' => now(),
            ]);
        }
    }

    /**
     * Dispatch notifications based on transition
     */
    private static function dispatchNotifications(Request $request, RequestStatus $from, RequestStatus $to): void
    {
        if ($to === RequestStatus::PENDING_RECOMMENDATION) {
            self::notifyStaff2($request);
        } elseif ($to === RequestStatus::PENDING_DEAN_APPROVAL) {
            self::notifyDean($request);
        } elseif ($to === RequestStatus::PENDING_DEAN_VERIFICATION) {
            self::notifyAdmission($request, 'Request confirmed by Dean - pending verification');
        } elseif ($to === RequestStatus::RETURNED_TO_ADMISSION) {
            self::notifyAdmission($request, 'Request returned for revision');
        } elseif ($to === RequestStatus::RETURNED_TO_STAFF_1) {
            self::notifyStaff1($request);
        } elseif ($to === RequestStatus::RETURNED_TO_STAFF_2) {
            self::notifyStaff2($request, 'Request returned for additional review');
        } elseif ($to === RequestStatus::APPROVED || $to === RequestStatus::DECLINED) {
            self::notifyAdmission($request, 
                $to === RequestStatus::APPROVED ? 'Request approved' : 'Request declined'
            );
        }
    }

    private static function notifyStaff2(Request $request, ?string $customMessage = null): void
    {
        $staff2Users = \App\Models\User::where('role', 'staff2')->get();
        
        foreach ($staff2Users as $user) {
            \App\Models\Notification::createForUser(
                $user->id,
                'request_ready_for_recommendation',
                'Request Ready for Recommendation',
                $customMessage ?? "Request {$request->ref_number} is ready for your review.",
                route('requests.show', $request->id),
                ['request_id' => $request->id]
            );
        }
    }

    private static function notifyDean(Request $request): void
    {
        $deanUsers = \App\Models\User::where('role', 'dean')->get();
        
        foreach ($deanUsers as $user) {
            \App\Models\Notification::createForUser(
                $user->id,
                'request_pending_dean_approval',
                'Request Pending Dean Approval',
                "Request {$request->ref_number} is ready for your final approval.",
                route('dean.requests.show', $request->id),
                ['request_id' => $request->id]
            );
        }
    }

    private static function notifyStaff1(Request $request): void
    {
        $staff1Users = \App\Models\User::where('role', 'staff1')->get();
        
        foreach ($staff1Users as $user) {
            \App\Models\Notification::createForUser(
                $user->id,
                'request_returned_for_verification',
                'Request Returned for Verification',
                "Request {$request->ref_number} has been returned for additional verification.",
                route('requests.show', $request->id),
                ['request_id' => $request->id]
            );
        }
    }

    private static function notifyAdmission(Request $request, string $title): void
    {
        \App\Models\Notification::createForUser(
            $request->user_id,
            'request_updated',
            $title,
            "Request {$request->ref_number} has been updated. Please review the changes.",
            route('requests.show', $request->id),
            ['request_id' => $request->id]
        );
    }
}
