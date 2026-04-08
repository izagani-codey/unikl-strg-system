<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->user();
        $rules = [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ];

        // Staff ID validation - only required if not already set
        if (!$user->staff_id) {
            $rules['staff_id'] = ['required', 'string', 'max:50', Rule::unique(User::class)->ignore($user->id)];
        } else {
            $rules['staff_id'] = ['sometimes', 'string', 'max:50', Rule::unique(User::class)->ignore($user->id)];
        }

        // Optional staff information fields
        $rules['designation']    = ['nullable', 'string', 'max:255'];
        $rules['department']     = ['nullable', 'string', 'max:255'];
        $rules['phone']          = ['nullable', 'string', 'max:20', 'regex:/^[+]?[0-9\s\-\(\)]+$/'];
        $rules['employee_level'] = ['nullable', 'string', 'max:100'];

        return $rules;
    }
}