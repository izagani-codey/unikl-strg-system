<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;

class RequestPolicy
{
    /**
     * Can the user view this request?
     */
    public function view(User $user, Request $request): bool
    {
        // Admission users can only view their own requests
        if ($user->role === 'admission') {
            return $user->id === $request->user_id;
        }

        // Staff users (staff1, staff2) can view any request
        // (you'll add stage-specific checks later)
        if (in_array($user->role, ['staff1', 'staff2'])) {
            return true;
        }

        return false;
    }

    /**
     * Can the user update/edit this request?
     */
    public function update(User $user, Request $request): bool
    {
        // Only admission can edit, and only their own requests
        if ($user->role === 'admission') {
            return $user->id === $request->user_id && in_array($request->status, [1, 3, 4]);
        }

        return false;
    }

    /**
     * Can the user change the status of this request?
     */
    public function changeStatus(User $user, Request $request): bool
    {
        // Admission cannot change status
        if ($user->role === 'admission') {
            return false;
        }

        // Staff1 can only change status on requests in their stage (1 or 4)
        if ($user->role === 'staff1') {
            return in_array($request->status, [1, 4]);
        }

        // Staff2 can only change status on requests in their stage (2)
        if ($user->role === 'staff2') {
            return $request->status === 2;
        }

        return false;
    }

    /**
     * Can the user add comments to this request?
     */
    public function addComment(User $user, Request $request): bool
    {
        // Admission cannot comment
        if ($user->role === 'admission') {
            return false;
        }

        // Staff1 can comment on requests in their stage
        if ($user->role === 'staff1') {
            return in_array($request->status, [1, 4]);
        }

        // Staff2 can comment on requests in their stage
        if ($user->role === 'staff2') {
            return $request->status === 2;
        }

        return false;
    }
}