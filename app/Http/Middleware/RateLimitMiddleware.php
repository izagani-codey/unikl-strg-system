<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request with rate limiting.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $limitName
     */
    public function handle(Request $request, Closure $next, string $limitName = null): Response
    {
        $limitName = $limitName ?: $this->getLimitName($request);
        
        if (!$this->shouldRateLimit($request)) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request, $limitName);
        $maxAttempts = $this->getMaxAttempts($limitName);
        $decayMinutes = $this->getDecayMinutes($limitName);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - RateLimiter::attempts($key)));

        return $response;
    }

    /**
     * Determine if the request should be rate limited.
     */
    protected function shouldRateLimit(Request $request): bool
    {
        // Don't rate limit GET requests for static content
        if ($request->isMethod('GET') && !$request->is(['login', 'register', 'password/*'])) {
            return false;
        }

        // Always rate limit authenticated user actions
        if ($request->user()) {
            return true;
        }

        // Rate limit unauthenticated actions
        $limitName = $this->getLimitName($request);
        return in_array($limitName, ['auth', 'login', 'register', 'password']);
    }

    /**
     * Get the rate limit name based on the request.
     */
    protected function getLimitName(Request $request): string
    {
        $route = $request->route();
        
        if ($route) {
            $routeName = $route->getName();
            
            if (str_contains($routeName, 'login')) return 'login';
            if (str_contains($routeName, 'register')) return 'register';
            if (str_contains($routeName, 'password')) return 'password';
            if (str_contains($routeName, 'comment')) return 'comment';
            if (str_contains($routeName, 'export')) return 'export';
        }

        return $request->user() ? 'authenticated' : 'guest';
    }

    /**
     * Resolve the rate limit signature.
     */
    protected function resolveRequestSignature(Request $request, string $limitName): string
    {
        if ($request->user()) {
            return sha1($limitName . '|' . $request->user()->id . '|' . $request->ip());
        }

        return sha1($limitName . '|' . $request->ip());
    }

    /**
     * Get the maximum number of attempts for the given limit name.
     */
    protected function getMaxAttempts(string $limitName): int
    {
        return match ($limitName) {
            'login' => 5,           // 5 login attempts
            'register' => 3,       // 3 registration attempts
            'password' => 3,       // 3 password reset attempts
            'comment' => 10,       // 10 comments per minute
            'export' => 5,         // 5 exports per hour
            'authenticated' => 60, // 60 requests per minute for authenticated users
            'guest' => 20,         // 20 requests per minute for guests
            default => 60,
        };
    }

    /**
     * Get the decay minutes for the given limit name.
     */
    protected function getDecayMinutes(string $limitName): int
    {
        return match ($limitName) {
            'login' => 15,          // 15 minutes
            'register' => 60,      // 1 hour
            'password' => 15,      // 15 minutes
            'comment' => 1,        // 1 minute
            'export' => 60,        // 1 hour
            'authenticated' => 1,   // 1 minute
            'guest' => 1,          // 1 minute
            default => 1,
        };
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $response = response()->json([
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => RateLimiter::availableIn($key),
        ], 429);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', 0);
        $response->headers->set('Retry-After', RateLimiter::availableIn($key));

        return $response;
    }
}
