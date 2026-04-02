<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\OverrideLog;
use App\Models\Request;
use App\Models\User;
use App\Notifications\OverrideNotification;
use Illuminate\Support\Facades\Auth;

class OverrideService
{
    /**
     * Perform an override action on a request
     */
    public static function performOverride(Request $request, string $action, string $reason): bool
    {
        $user = Auth::user();
        
        if (!$user->canOverride()) {
            throw new \Exception('You do not have override privileges enabled.');
        }

        // Store original data
        $originalData = [
            'status_id' => $request->status_id,
            'is_priority' => $request->is_priority,
            'verified_by' => $request->verified_by,
            'recommended_by' => $request->recommended_by,
        ];

        // Perform the override action
        $success = match($action) {
            'approve' => self::directApproval($request, $user, $reason),
            'reject_reverse' => self::reverseRejection($request, $user, $reason),
            'bypass_verification' => self::bypassVerification($request, $user, $reason),
            'priority_override' => self::overridePriority($request, $user, $reason),
            default => false,
        };

        if ($success) {
            // Store new data
            $newData = [
                'status_id' => $request->fresh()->status_id,
                'is_priority' => $request->fresh()->is_priority,
                'verified_by' => $request->fresh()->verified_by,
                'recommended_by' => $request->fresh()->recommended_by,
            ];

            // Log the override
            OverrideLog::create([
                'request_id' => $request->id,
                'user_id' => $user->id,
                'action_type' => $action,
                'reason' => $reason,
                'original_data' => $originalData,
                'new_data' => $newData,
            ]);

            // Send notifications
            self::sendOverrideNotifications($request, $user, $action, $reason);
        }

        return $success;
    }

    /**
     * Direct approval bypassing Staff 1
     */
    private static function directApproval(Request $request, User $user, string $reason): bool
    {
        $request->update([
            'status_id' => RequestStatus::APPROVED->value,
            'recommended_by' => $user->id,
            'is_overridden' => true,
            'overridden_by' => $user->id,
            'override_reason' => $reason,
            'overridden_at' => now(),
        ]);

        return true;
    }

    /**
     * Reverse a rejection
     */
    private static function reverseRejection(Request $request, User $user, string $reason): bool
    {
        $request->update([
            'status_id' => RequestStatus::PENDING_VERIFICATION->value,
            'verified_by' => null,
            'recommended_by' => null,
            'is_overridden' => true,
            'overridden_by' => $user->id,
            'override_reason' => $reason,
            'overridden_at' => now(),
        ]);

        return true;
    }

    /**
     * Bypass Staff 1 verification
     */
    private static function bypassVerification(Request $request, User $user, string $reason): bool
    {
        $request->update([
            'status_id' => RequestStatus::PENDING_RECOMMENDATION->value,
            'verified_by' => $user->id,
            'is_overridden' => true,
            'overridden_by' => $user->id,
            'override_reason' => $reason,
            'overridden_at' => now(),
        ]);

        return true;
    }

    /**
     * Override priority setting
     */
    private static function overridePriority(Request $request, User $user, string $reason): bool
    {
        $request->update([
            'is_priority' => !$request->is_priority,
            'is_overridden' => true,
            'overridden_by' => $user->id,
            'override_reason' => $reason,
            'overridden_at' => now(),
        ]);

        return true;
    }

    /**
     * Send notifications for override actions
     */
    private static function sendOverrideNotifications(Request $request, User $overrideUser, string $action, string $reason): void
    {
        $message = match($action) {
            'approve' => "Request {$request->ref_number} was directly approved by {$overrideUser->name} (Staff 2 Override)",
            'reject_reverse' => "Request {$request->ref_number} rejection was reversed by {$overrideUser->name} (Staff 2 Override)",
            'bypass_verification' => "Request {$request->ref_number} verification was bypassed by {$overrideUser->name} (Staff 2 Override)",
            'priority_override' => "Request {$request->ref_number} priority was changed by {$overrideUser->name} (Staff 2 Override)",
            default => "Override action performed on request {$request->ref_number} by {$overrideUser->name}",
        };

        // Notify Staff 1 if they were involved
        if ($request->verifiedBy && $request->verifiedBy->id !== $overrideUser->id) {
            $request->verifiedBy->notify(new OverrideNotification($request, $message, $action));
        }

        // Notify admission user
        $request->user->notify(new OverrideNotification($request, $message, $action));

        // Additional explicit confirmation notifications for reinstatement actions
        if ($action === 'reject_reverse') {
            \App\Models\Notification::createForUser(
                $request->user_id,
                'request_reinstated',
                'Request Reinstated',
                "Request {$request->ref_number} has been reinstated into the workflow.",
                route('requests.show', $request->id),
                ['request_id' => $request->id, 'action' => $action]
            );

            \App\Models\Notification::createForUser(
                $overrideUser->id,
                'reinstatement_confirmation',
                'Reinstatement Confirmed',
                "You reinstated request {$request->ref_number}. This action was logged and is now active.",
                route('requests.show', $request->id),
                ['request_id' => $request->id, 'action' => $action]
            );
        }
    }

    /**
     * Get override history for a request
     */
    public static function getOverrideHistory(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        return $request->overrideLogs()
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * Check if a request can be overridden
     */
    public static function canOverride(Request $request, User $user): bool
    {
        if (!$user->canOverride()) {
            return false;
        }

        // Staff 2 can override any non-final request
        return !$request->getStatus()->isFinal();
    }
}
