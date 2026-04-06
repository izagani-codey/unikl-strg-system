<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class WorkflowTransitionService
{
    /**
     * Define allowed transitions for each role
     */
    public static function getAllowedTransitions(): array
    {
        $transitions = [
            'staff1' => [
                RequestStatus::PENDING_VERIFICATION->value => [
                    RequestStatus::PENDING_RECOMMENDATION->value,
                    RequestStatus::RETURNED_TO_ADMISSION->value,
                    RequestStatus::DECLINED->value,
                ],
                RequestStatus::RETURNED_TO_STAFF_1->value => [
                    RequestStatus::PENDING_RECOMMENDATION->value,
                    RequestStatus::RETURNED_TO_ADMISSION->value,
                    RequestStatus::DECLINED->value,
                ],
            ],
            'staff2' => [
                RequestStatus::PENDING_RECOMMENDATION->value => [
                    RequestStatus::APPROVED->value, // Direct approval when dean interface is disabled
                    RequestStatus::RETURNED_TO_STAFF_1->value,
                    RequestStatus::RETURNED_TO_STAFF_2->value,
                    RequestStatus::DECLINED->value,
                ],
                RequestStatus::RETURNED_TO_STAFF_2->value => [
                    RequestStatus::APPROVED->value, // Direct approval when dean interface is disabled
                    RequestStatus::RETURNED_TO_STAFF_1->value,
                    RequestStatus::DECLINED->value,
                ],
            ],
            'admission' => [
                RequestStatus::RETURNED_TO_ADMISSION->value => [
                    RequestStatus::PENDING_VERIFICATION->value,
                ],
            ],
        ];

        // Add dean transitions only when feature flag is enabled
        if (config('system.features.dean_interface', false)) {
            $transitions['dean'] = [
                RequestStatus::PENDING_DEAN_APPROVAL->value => [
                    RequestStatus::APPROVED->value,
                    RequestStatus::RETURNED_TO_STAFF_1->value,
                    RequestStatus::RETURNED_TO_STAFF_2->value,
                    RequestStatus::DECLINED->value,
                ],
            ];
            
            // Update staff2 transitions to go through dean when enabled
            $transitions['staff2'][RequestStatus::PENDING_RECOMMENDATION->value] = [
                RequestStatus::PENDING_DEAN_APPROVAL->value,
                RequestStatus::RETURNED_TO_STAFF_1->value,
                RequestStatus::RETURNED_TO_STAFF_2->value,
                RequestStatus::DECLINED->value,
            ];
            
            $transitions['staff2'][RequestStatus::RETURNED_TO_STAFF_2->value] = [
                RequestStatus::PENDING_DEAN_APPROVAL->value,
                RequestStatus::RETURNED_TO_STAFF_1->value,
                RequestStatus::DECLINED->value,
            ];
        }

        return $transitions;
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

        // Validate signature requirement for Staff2 and Dean
        self::validateSignatureRequirement($user, $newStatus, $data);

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

        // Save stage signatures if provided
        self::saveStageSignatures($request, $user, $data);

        // Dispatch notifications (best-effort; must not block transition success)
        try {
            self::dispatchNotifications($request, $oldStatus, $newStatus);
        } catch (\Throwable $e) {
            \Log::warning('Workflow transition notification dispatch failed', [
                'request_id' => $request->id,
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'error' => $e->getMessage(),
            ]);
        }

        // Regenerate PDF if signature was added
        if (self::hasSignatureData($user->role, $data)) {
            try {
                $template = $request->requestType->defaultTemplate;
                RequestPdfService::generate($request, $template);
            } catch (\Throwable $e) {
                \Log::warning('PDF regeneration failed after signature', [
                    'request_id' => $request->id,
                    'user_role' => $user->role,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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
            self::notifyRole(
                role: 'staff2',
                request: $request,
                type: 'request_ready_for_recommendation',
                title: 'Request Ready for Recommendation',
                message: "Request {$request->ref_number} is ready for your review.",
            );
        } elseif ($to === RequestStatus::PENDING_DEAN_APPROVAL) {
            self::notifyRole(
                role: 'dean',
                request: $request,
                type: 'request_pending_dean_approval',
                title: 'Request Pending Dean Approval',
                message: "Request {$request->ref_number} is ready for your final approval.",
            );
        } elseif ($to === RequestStatus::RETURNED_TO_ADMISSION) {
            self::notifyAdmission($request, 'Request returned for revision');
        } elseif ($to === RequestStatus::RETURNED_TO_STAFF_1) {
            self::notifyRole(
                role: 'staff1',
                request: $request,
                type: 'request_returned_for_verification',
                title: 'Request Returned for Verification',
                message: "Request {$request->ref_number} has been returned for additional verification.",
            );
        } elseif ($to === RequestStatus::RETURNED_TO_STAFF_2) {
            self::notifyRole(
                role: 'staff2',
                request: $request,
                type: 'request_ready_for_recommendation',
                title: 'Request Ready for Recommendation',
                message: "Request {$request->ref_number} has been returned for additional review.",
            );
        } elseif ($to === RequestStatus::APPROVED || $to === RequestStatus::DECLINED) {
            self::notifyAdmission($request, 
                $to === RequestStatus::APPROVED ? 'Request approved' : 'Request declined'
            );
        }
    }

    private static function notifyRole(string $role, Request $request, string $type, string $title, string $message): void
    {
        $users = \App\Models\User::where('role', $role)->get();
        $url = route('requests.show', $request->id);

        foreach ($users as $user) {
            self::notifyUser($user->id, $type, $title, $message, $url, $request->id);
        }
    }

    private static function notifyAdmission(Request $request, string $title): void
    {
        self::notifyUser(
            $request->user_id,
            'request_updated',
            $title,
            "Request {$request->ref_number} has been updated. Please review the changes.",
            route('requests.show', $request->id),
            $request->id
        );
    }

    private static function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $url,
        int $requestId
    ): void {
        \App\Models\Notification::createForUser(
            $userId,
            $type,
            $title,
            $message,
            $url,
            ['request_id' => $requestId]
        );
    }

    /**
     * Save stage signatures based on user role
     */
    private static function saveStageSignatures(Request $request, User $user, array $data): void
    {
        $signatureField = match ($user->role) {
            'staff2' => 'staff2_signature_data',
            'dean' => 'dean_signature_data',
            default => null,
        };

        $timestampField = match ($user->role) {
            'staff2' => 'staff2_signed_at',
            'dean' => 'dean_signed_at',
            default => null,
        };

        if ($signatureField && $timestampField && isset($data[$signatureField])) {
            $request->update([
                $signatureField => $data[$signatureField],
                $timestampField => now(),
            ]);
        }
    }

    /**
     * Validate that Staff2 and Dean provide signatures for approval/decline actions
     */
    private static function validateSignatureRequirement(User $user, RequestStatus $newStatus, array $data): void
    {
        // Only enforce for Staff2 and Dean roles
        if (!in_array($user->role, ['staff2', 'dean'], true)) {
            return;
        }

        // Require signature for final actions (APPROVED, DECLINED)
        $requiresSignature = in_array($newStatus, [
            RequestStatus::APPROVED,
            RequestStatus::DECLINED,
        ], true);

        if (!$requiresSignature) {
            return;
        }

        // Check if signature data is provided based on role
        $signatureField = match ($user->role) {
            'staff2' => 'staff2_signature_data',
            'dean' => 'dean_signature_data',
            default => null,
        };

        if (!$signatureField || empty($data[$signatureField])) {
            $roleLabel = $user->role === 'staff2' ? 'Staff 2' : 'Dean';
            throw new AuthorizationException(
                "{$roleLabel} signature is required to " . strtolower($newStatus->getLabel()) . " this request."
            );
        }
    }

    /**
     * Check if signature data is present for the user role
     */
    private static function hasSignatureData(string $role, array $data): bool
    {
        $signatureField = match ($role) {
            'staff2' => 'staff2_signature_data',
            'dean' => 'dean_signature_data',
            default => null,
        };

        return $signatureField && !empty($data[$signatureField]);
    }
}
