<?php

namespace App\Policies;

use App\Http\Controllers\RequestController;
use App\Models\Request as GrantRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GrantRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admission', 'staff1', 'staff2'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admission';
    }

    public function view(User $user, GrantRequest $request): bool
    {
        if ($user->role === 'admission') {
            return (int) $request->user_id === (int) $user->id;
        }

        return in_array($user->role, ['staff1', 'staff2'], true);
    }

    public function print(User $user, GrantRequest $request): bool
    {
        return $this->view($user, $request);
    }

    public function revise(User $user, GrantRequest $request): bool
    {
        return $user->role === 'admission'
            && (int) $request->user_id === (int) $user->id
            && (int) $request->status_id === 3;
    }

    /**
     * Allow the action UI to appear for staff when any transition FROM this
     * status is possible for their role — not tied to a specific new status.
     * The actual new-status validation happens in isValidTransition().
     */
    public function updateStatus(User $user, GrantRequest $request): Response|bool
    {
        if (! in_array($user->role, ['staff1', 'staff2'], true)) {
            return Response::deny('Only staff members can update request status.');
        }

        $map     = RequestController::allowedTransitions();
        $allowed = $map[$user->role] ?? [];

        if (! array_key_exists((int) $request->status_id, $allowed)) {
            return Response::deny(
                'This request is at status "' . $request->statusLabel() . '" which cannot be actioned by your role.'
            );
        }

        return true;
    }

    /**
     * Comments are only useful while the request is still in the workflow.
     * Staff 1 acts on statuses 1 and 4; Staff 2 acts on status 2.
     * We restrict the comment box to the stage the user is actually working.
     */
    public function addComment(User $user, GrantRequest $request): bool
    {
        if ($user->role === 'staff1') {
            return in_array((int) $request->status_id, [1, 4], true);
        }

        if ($user->role === 'staff2') {
            return (int) $request->status_id === 2;
        }

        return false;
    }
}
