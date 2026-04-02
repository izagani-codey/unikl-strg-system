<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ErrorHandlerService
{
    /**
     * Handle common request errors with proper logging
     */
    public static function handleRequestError(Exception $e, $context = [])
    {
        $errorData = [
            'message' => $e->getMessage(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];

        // Log the error
        Log::error('Request error occurred', $errorData);

        // Return user-friendly message
        if ($e instanceof ValidationException) {
            return self::handleValidationError($e);
        }

        return self::getGenericErrorMessage($e);
    }

    /**
     * Handle validation errors specifically
     */
    private static function handleValidationError(ValidationException $e): string
    {
        $errors = $e->errors();
        $firstError = collect($errors)->flatten()->first();
        
        return $firstError ?? 'Validation failed. Please check your input.';
    }

    /**
     * Get user-friendly error message
     */
    private static function getGenericErrorMessage(Exception $e): string
    {
        // Map common errors to user-friendly messages
        $errorMap = [
            'SQLSTATE[HY000]: General error' => 'A database error occurred. Please try again.',
            'SQLSTATE[23000]: Integrity constraint violation' => 'This action conflicts with existing data.',
            'Connection refused' => 'Unable to connect to the server. Please try again.',
            'timeout' => 'The request took too long to complete. Please try again.',
            '404' => 'The requested resource was not found.',
            '403' => 'You do not have permission to perform this action.',
            '500' => 'An internal server error occurred. Please try again.',
        ];

        $errorMessage = $e->getMessage();
        
        foreach ($errorMap as $pattern => $userMessage) {
            if (str_contains($errorMessage, $pattern)) {
                return $userMessage;
            }
        }

        // Return generic message for unknown errors
        return 'An error occurred: ' . $errorMessage;
    }

    /**
     * Log successful operations for audit trail
     */
    public static function logSuccess($action, $context = [])
    {
        $logData = [
            'action' => $action,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'success' => true,
        ];

        Log::info('Request completed successfully', $logData);
    }

    /**
     * Handle database transaction errors
     */
    public static function handleDatabaseError(Exception $e, $operation = '')
    {
        $errorData = [
            'operation' => $operation,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
        ];

        Log::error('Database operation failed', $errorData);

        if (str_contains($e->getMessage(), 'constraint')) {
            return 'This action conflicts with existing data. Please check related records.';
        }

        return 'A database error occurred. Please try again or contact support.';
    }

    /**
     * Handle file upload errors
     */
    public static function handleFileUploadError(Exception $e, $fileName = '')
    {
        $errorData = [
            'file_name' => $fileName,
            'error' => $e->getMessage(),
            'size' => request()->file('file')?->getSize() ?? 0,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
        ];

        Log::error('File upload failed', $errorData);

        if (str_contains($e->getMessage(), 'too large')) {
            return 'The file is too large. Maximum size is 5MB.';
        }

        if (str_contains($e->getMessage(), 'mimetype')) {
            return 'Invalid file type. Please upload PDF, JPG, or PNG files.';
        }

        return 'File upload failed: ' . $e->getMessage();
    }
}
