<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class OverrideController extends Controller
{
    /**
     * Toggle override mode for Staff 2 users
     */
    public function toggleOverrideMode(Request $request)
    {
        $user = Auth::user();
        
        // Only Staff 2 can use override mode
        if (!$user->isStaff2()) {
            abort(403, 'Unauthorized action.');
        }
        
        // Toggle override mode
        if ($user->override_enabled) {
            $user->disableOverride();
            $message = 'Override mode disabled.';
        } else {
            $user->enableOverride();
            $message = 'Override mode enabled.';
        }
        
        return Redirect::back()->with('success', $message);
    }
}
