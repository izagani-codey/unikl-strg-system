<?php

namespace App\Policies;

use App\Models\Request as GrantRequest;
use App\Models\User;

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

    public function updateStatus(User $user, GrantRequest $request): bool
    {
        if ($user->role === 'staff1') {
            return in_array((int) $request->status_id, [1, 4], true);
        }

        if ($user->role === 'staff2') {
            return in_array((int) $request->status_id, [2, 5, 6], true);
        }

        return false;
    }

    public function addComment(User $user, GrantRequest $request): bool
    {
        return in_array($user->role, ['staff1', 'staff2'], true)
            && in_array((int) $request->status_id, [1, 2, 4], true);
    }
}
