<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $grantRequest = GrantRequest::find($this->route('id'));

        return $grantRequest
            && $this->user()
            && $this->user()->role === 'admission'
            && (int) $grantRequest->user_id === (int) $this->user()->id
            && (int) $grantRequest->status_id === RequestStatus::RETURNED->value;
    }

    public function rules(): array
    {
        $request = $this->route('id');
        $grantRequest = \App\Models\Request::find($request);
        
        return [
            'request_type_id' => [
                'required',
                Rule::exists('request_types', 'id')->where(function ($query) use ($grantRequest) {
                    // Allow the original request type even if it's been disabled
                    if ($grantRequest) {
                        $query->where('id', $grantRequest->request_type_id)
                              ->orWhere('is_active', true);
                    } else {
                        $query->where('is_active', true);
                    }
                }),
            ],
            'description' => 'required|string|max:500',
            'vot_items' => 'required|array|min:1',
            'vot_items.*.vot_code' => 'required|string|exists:vot_codes,code',
            'vot_items.*.description' => 'required|string|max:255',
            'vot_items.*.amount' => 'required|numeric|min:0',
            'document'    => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'additional_documents' => 'nullable|array|max:10',
            'additional_documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'deadline'    => 'required|date|after:today',
            'signature_data' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'request_type_id.required'        => 'Please select a request type.',
            'request_type_id.exists'         => 'Selected request type is invalid.',
            'description.required'            => 'Description is required.',
            'description.max'                => 'Description must not exceed 500 characters.',
            'vot_items.required'            => 'At least one VOT item is required.',
            'vot_items.*.vot_code.required' => 'Each VOT item must have a VOT code.',
            'vot_items.*.vot_code.exists'  => 'Invalid VOT code selected.',
            'vot_items.*.description.required' => 'Each VOT item must have a description.',
            'vot_items.*.description.max'    => 'VOT description must not exceed 255 characters.',
            'vot_items.*.amount.required'    => 'Each VOT item must have an amount.',
            'vot_items.*.amount.min'         => 'VOT amount must be zero or greater.',
            'additional_documents.max'         => 'Maximum 10 additional documents allowed.',
            'deadline.required'                => 'Deadline is required.',
            'deadline.after'                  => 'Deadline must be after today.',
        ];
    }
}
