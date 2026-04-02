<?php

namespace App\Http\Controllers;

use App\Models\Request as GrantRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class BaseRequestController extends Controller
{
    use AuthorizesRequests;

    /**
     * Common validation rules for requests
     */
    protected function getCommonValidationRules(): array
    {
        return [
            'description' => 'required|string|max:1000',
            'request_type_id' => 'required|exists:request_types,id',
            'deadline' => 'nullable|date|after:today',
            'priority' => 'nullable|boolean',
        ];
    }

    /**
     * Get request with common relationships
     */
    protected function getRequestWithCommonRelations($id)
    {
        return GrantRequest::with([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'auditLogs' => function ($query) {
                $query->with('actor')->latest();
            }
        ])->findOrFail($id);
    }

    /**
     * Check if user can perform action on request
     */
    protected function authorizeRequestAction($grantRequest, $action): void
    {
        $this->authorize($action, $grantRequest);
    }

    /**
     * Standard success response
     */
    protected function successResponse($message, $route = null, $parameters = [])
    {
        if ($route) {
            return redirect()->route($route, $parameters)->with('success', $message);
        }
        return back()->with('success', $message);
    }

    /**
     * Standard error response
     */
    protected function errorResponse($message, $withInput = false)
    {
        return $withInput 
            ? back()->withInput()->with('error', $message)
            : back()->with('error', $message);
    }

    /**
     * Get current authenticated user
     */
    protected function getCurrentUser(): User
    {
        return Auth::user();
    }

    /**
     * Check if user has specific role
     */
    protected function userHasRole($role): bool
    {
        return $this->getCurrentUser()->role === $role;
    }

    /**
     * Get requests based on user role
     */
    protected function getRequestsForUserRole()
    {
        $user = $this->getCurrentUser();
        
        if ($user->role === 'admission') {
            return GrantRequest::where('user_id', $user->id)
                ->with(['requestType', 'verifiedBy', 'recommendedBy'])
                ->latest()
                ->get();
        }
        
        // For staff users, return all requests
        return GrantRequest::with(['requestType', 'user', 'verifiedBy', 'recommendedBy'])
            ->latest()
            ->get();
    }
}
