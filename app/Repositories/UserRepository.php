<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $role): Collection
    {
        return $this->newQuery()
            ->where('role', $role)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get staff users (staff1 and staff2).
     */
    public function getStaffUsers(): Collection
    {
        return $this->newQuery()
            ->whereIn('role', ['staff1', 'staff2'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get user statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->newQuery()->count(),
            'admission' => $this->newQuery()->where('role', 'admission')->count(),
            'staff1' => $this->newQuery()->where('role', 'staff1')->count(),
            'staff2' => $this->newQuery()->where('role', 'staff2')->count(),
            'verified' => $this->newQuery()->whereNotNull('email_verified_at')->count(),
            'unverified' => $this->newQuery()->whereNull('email_verified_at')->count(),
        ];
    }

    /**
     * Get recent users.
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->newQuery()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search users.
     */
    public function search(string $term, string $role = null): Collection
    {
        $query = $this->newQuery()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });

        if ($role) {
            $query->where('role', $role);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    /**
     * Check if user exists by email.
     */
    public function existsByEmail(string $email): bool
    {
        return $this->newQuery()->where('email', $email)->exists();
    }

    /**
     * Get users with request counts.
     */
    public function getWithRequestCounts(): Collection
    {
        return $this->newQuery()
            ->selectRaw('users.*, COUNT(requests.id) as request_count')
            ->leftJoin('requests', 'users.id', '=', 'requests.user_id')
            ->groupBy('users.id')
            ->orderBy('request_count', 'desc')
            ->get();
    }

    /**
     * Get active users (with recent activity).
     */
    public function getActive(int $days = 30): Collection
    {
        return $this->newQuery()
            ->where('last_login_at', '>=', now()->subDays($days))
            ->orderBy('last_login_at', 'desc')
            ->get();
    }

    /**
     * Create user with verification.
     */
    public function createVerified(array $data): User
    {
        $user = $this->create($data);
        
        // Auto-verify for demo purposes
        $user->email_verified_at = now();
        $user->save();
        
        return $user;
    }

    /**
     * Update user role.
     */
    public function updateRole(User $user, string $role): bool
    {
        return $user->update(['role' => $role]);
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus(User $user): bool
    {
        return $user->update(['is_active' => !$user->is_active]);
    }

    /**
     * Get users for dropdown options.
     */
    public function getForDropdown(string $role = null): Collection
    {
        $query = $this->newQuery()
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name');

        if ($role) {
            $query->where('role', $role);
        }

        return $query->get();
    }
}
