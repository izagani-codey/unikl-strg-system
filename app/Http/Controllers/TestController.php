<?php

namespace App\Http\Controllers;

use App\Models\Request as GrantRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TestController extends Controller
{
    public function testAuth()
    {
        $user = Auth::user();
        $request = GrantRequest::first();
        
        if (!$request) {
            return response()->json(['error' => 'No requests found']);
        }
        
        return response()->json([
            'user_role' => $user->role,
            'request_id' => $request->id,
            'can_view_any' => Gate::forUser($user)->allows('viewAny', GrantRequest::class),
            'can_view' => Gate::forUser($user)->allows('view', $request),
            'policy_result' => $this->testPolicy($user, $request),
        ]);
    }
    
    private function testPolicy($user, $request)
    {
        $policy = new \App\Policies\RequestPolicy();
        
        return [
            'viewAny_result' => $policy->viewAny($user),
            'view_result' => $policy->view($user, $request),
        ];
    }
}
