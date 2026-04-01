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
        return [
            'name'           => ['required', 'string', 'max:255'],
            'staff_id'       => ['required', 'string', 'max:50', Rule::unique(User::class)->ignore($this->user()->id)],
            'designation'    => ['required', 'string', 'max:255'],
            'department'     => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'employee_level' => ['nullable', 'string', 'max:100'],
            'email'          => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}