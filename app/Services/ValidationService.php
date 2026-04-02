<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidationService
{
    /**
     * Validate VOT items with comprehensive rules
     */
    public static function validateVotItems($votItems): array
    {
        $rules = [
            'vot_items' => 'required|array|min:1',
            'vot_items.*.vot_code' => 'required|string|exists:vot_codes,code',
            'vot_items.*.description' => 'required|string|max:255',
            'vot_items.*.amount' => 'required|numeric|min:0|max:999999.99',
        ];

        $messages = [
            'vot_items.required' => 'At least one VOT item is required.',
            'vot_items.min' => 'At least one VOT item is required.',
            'vot_items.*.vot_code.required' => 'VOT code is required for each item.',
            'vot_items.*.vot_code.exists' => 'Selected VOT code is invalid.',
            'vot_items.*.description.required' => 'Description is required for each VOT item.',
            'vot_items.*.amount.required' => 'Amount is required for each VOT item.',
            'vot_items.*.amount.numeric' => 'Amount must be a valid number.',
            'vot_items.*.amount.min' => 'Amount cannot be negative.',
        ];

        return Validator::make(['vot_items' => $votItems], $rules, $messages)
            ->validate();
    }

    /**
     * Validate request creation with all rules
     */
    public static function validateRequestCreation($data): array
    {
        $rules = [
            'request_type_id' => 'required|exists:request_types,id',
            'description' => 'required|string|max:1000',
            'deadline' => 'nullable|date|after:today',
            'priority' => 'nullable|boolean',
            'signature_data' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        $messages = [
            'request_type_id.required' => 'Request type is required.',
            'request_type_id.exists' => 'Selected request type is invalid.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'deadline.after' => 'Deadline must be a future date.',
            'signature_data.required' => 'Digital signature is required.',
            'document.mimes' => 'Document must be PDF, JPG, or PNG.',
            'document.max' => 'Document size cannot exceed 5MB.',
        ];

        return Validator::make($data, $rules, $messages)
            ->validate();
    }

    /**
     * Validate user profile data
     */
    public static function validateUserProfile($data): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'staff_id' => 'required|string|max:50',
            'designation' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'employee_level' => 'nullable|string|max:100',
            'signature_data' => 'nullable|string',
        ];

        $messages = [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'staff_id.required' => 'Staff ID is required.',
            'designation.required' => 'Designation is required.',
            'department.required' => 'Department is required.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'employee_level.max' => 'Employee level cannot exceed 100 characters.',
        ];

        return Validator::make($data, $rules, $messages)
            ->validate();
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $maxSize = 5120): array
    {
        $rules = [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:' . $maxSize,
        ];

        $messages = [
            'file.required' => 'File is required.',
            'file.mimes' => 'File must be PDF, JPG, or PNG.',
            'file.max' => "File size cannot exceed {$maxSize}KB.",
        ];

        return Validator::make(['file' => $file], $rules, $messages)
            ->validate();
    }

    /**
     * Validate status transition
     */
    public static function validateStatusTransition($fromStatus, $toStatus, $userRole): array
    {
        $allowedTransitions = \App\Http\Controllers\RequestController::allowedTransitions();
        
        if (!isset($allowedTransitions[$userRole][$fromStatus])) {
            throw ValidationException::withMessages([
                'status' => 'No transitions available from current status.'
            ]);
        }

        if (!in_array($toStatus, $allowedTransitions[$userRole][$fromStatus])) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status transition for your role.'
            ]);
        }

        return ['valid' => true];
    }

    /**
     * Sanitize and normalize VOT items
     */
    public static function normalizeVotItems($votItems): array
    {
        return collect($votItems)->map(function ($item) {
            return [
                'vot_code' => $item['vot_code'] ?? null,
                'amount' => (float) ($item['amount'] ?? 0),
                'description' => $item['description'] ?? '',
            ];
        })->filter(function ($item) {
            return !empty($item['vot_code']) && $item['amount'] > 0;
        })->values()->all();
    }
}
