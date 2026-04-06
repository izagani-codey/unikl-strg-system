<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Enhanced error logging with context
            if (request()) {
                Log::error('Application Error', [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'user_id' => request()->user()?->id,
                    'user_role' => request()->user()?->role,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle AJAX requests with JSON responses
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getErrorMessage($exception),
                'errors' => method_exists($exception, 'errors') ? $exception->errors() : [],
                'code' => $exception->getCode() ?: 500,
            ], $this->getExceptionStatusCode($exception));
        }

        return parent::render($request, $exception);
    }

    /**
     * Get user-friendly error message.
     */
    private function getErrorMessage(Throwable $exception): string
    {
        if (config('app.debug')) {
            return $exception->getMessage();
        }

        return match (get_class($exception)) {
            'Illuminate\Auth\AuthenticationException' => 'Please log in to continue.',
            'Illuminate\Auth\Access\AuthorizationException' => 'You do not have permission to perform this action.',
            'Illuminate\Validation\ValidationException' => 'The provided data is invalid. Please check your input.',
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 'The requested resource was not found.',
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 'The page you are looking for could not be found.',
            'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException' => 'The request method is not allowed for this route.',
            default => 'An error occurred while processing your request. Please try again.',
        };
    }

    /**
     * Get appropriate HTTP status code for exception.
     */
    private function getExceptionStatusCode(Throwable $exception): int
    {
        return match (get_class($exception)) {
            'Illuminate\Auth\AuthenticationException' => 401,
            'Illuminate\Auth\Access\AuthorizationException' => 403,
            'Illuminate\Validation\ValidationException' => 422,
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 404,
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 404,
            'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException' => 405,
            default => 500,
        };
    }
}
