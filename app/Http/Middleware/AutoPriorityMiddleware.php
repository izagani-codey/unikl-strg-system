<?php

namespace App\Http\Middleware;

use App\Models\Request;
use Closure;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response;

class AutoPriorityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(HttpRequest $request, Closure $next): Response
    {
        $response = $next($request);

        // Update priorities for requests with deadlines when staff view requests
        if ($request->user() && in_array($request->user()->role, ['staff1', 'staff2'])) {
            if ($request->routeIs('requests.show') && $request->route('id')) {
                $requestId = $request->route('id');
                $grantRequest = Request::find($requestId);
                
                if ($grantRequest && $grantRequest->deadline) {
                    $grantRequest->updatePriorityFromDeadline();
                }
            }
        }

        return $response;
    }
}
