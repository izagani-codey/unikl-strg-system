<?php

namespace App\Http\Requests;

use App\Models\Request as GrantRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $grantRequest = GrantRequest::find($this->route('id'));

        return $grantRequest
            && $this->user()
            && Gate::allows('changeStatus', $grantRequest);
    }

    public function rules(): array
    {
        return [
            'status_id' => 'required|integer|between:1,9',
            'notes'     => 'nullable|string',
            'rejection_reason' => 'nullable|string',
            'override_reason' => 'nullable|string',
            'staff1_signature_data' => 'nullable|string',
            'staff2_signature_data' => 'nullable|string',
            'dean_signature_data' => 'nullable|string',
        ];
    }
}
