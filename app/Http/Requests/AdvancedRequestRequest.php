<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class AdvancedRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'request_type_id' => [
                'required',
                'integer',
                'exists:request_types,id',
                Rule::exists('request_types', 'id')->where('is_active', true),
            ],
            'title' => [
                'required',
                'string',
                'min:10',
                'max:200',
                'regex:/^[a-zA-Z0-9\s\-.,()&]+$/',
            ],
            'description' => [
                'required',
                'string',
                'min:50',
                'max:2000',
            ],
            'total_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:1000000',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'deadline' => [
                'required',
                'date',
                'after_or_equal:today',
                'before:' . now()->addMonths(12)->format('Y-m-d'),
            ],
            'priority' => [
                'nullable',
                'string',
                'in:low,medium,high,urgent',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:5',
            ],
            'attachments.*' => [
                'file',
                File::types(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])
                    ->max(5120), // 5MB max
            ],
            'vot_items' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],
            'vot_items.*.description' => [
                'required',
                'string',
                'min:5',
                'max:200',
            ],
            'vot_items.*.amount' => [
                'required',
                'numeric',
                'min:0',
                'max:500000',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'vot_items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
        ];

        // Add additional validation based on feature flags
        if (config('system.features.strict_file_validation', true)) {
            $rules['attachments.*'][] = 'mimes:pdf,doc,docx,jpg,jpeg,png';
            $rules['attachments.*'][] = 'max:2048'; // 2MB for strict mode
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'request_type_id.exists' => 'The selected request type is not available.',
            'title.regex' => 'Title may only contain letters, numbers, spaces, and basic punctuation.',
            'title.min' => 'Title must be at least 10 characters long.',
            'description.min' => 'Description must be at least 50 characters long.',
            'total_amount.regex' => 'Amount must be a valid monetary value (e.g., 100.50).',
            'total_amount.max' => 'Amount cannot exceed RM 1,000,000.',
            'deadline.after_or_equal' => 'Deadline must be today or a future date.',
            'deadline.before' => 'Deadline cannot be more than 12 months in the future.',
            'attachments.max' => 'You may upload a maximum of 5 files.',
            'attachments.*.max' => 'Each attachment may not exceed 5MB.',
            'vot_items.max' => 'You may add a maximum of 20 VOT items.',
            'vot_items.*.amount.regex' => 'VOT item amount must be a valid monetary value.',
        ];
    }

    /**
     * Get custom attributes for validation errors.
     */
    public function attributes(): array
    {
        return [
            'request_type_id' => 'request type',
            'total_amount' => 'total amount',
            'vot_items.*.description' => 'VOT item description',
            'vot_items.*.amount' => 'VOT item amount',
            'vot_items.*.quantity' => 'VOT item quantity',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate total amount matches sum of VOT items
            if ($this->has('vot_items')) {
                $votTotal = collect($this->input('vot_items', []))
                    ->sum(function ($item) {
                        return ($item['amount'] ?? 0) * ($item['quantity'] ?? 1);
                    });

                if (abs($votTotal - (float) $this->input('total_amount', 0)) > 0.01) {
                    $validator->errors()->add(
                        'total_amount',
                        'Total amount must match the sum of all VOT items.'
                    );
                }
            }

            // Validate deadline is not too close for high amounts
            if ($this->has('total_amount') && $this->has('deadline')) {
                $amount = (float) $this->input('total_amount');
                $deadline = $this->input('deadline');
                $daysUntilDeadline = now()->diffInDays($deadline);

                if ($amount > 100000 && $daysUntilDeadline < 7) {
                    $validator->errors()->add(
                        'deadline',
                        'Requests exceeding RM 100,000 require at least 7 days notice.'
                    );
                }
            }
        });
    }
}
