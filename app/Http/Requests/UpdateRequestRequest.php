<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $grantRequest = GrantRequest::find($this->route('id'));

        return $grantRequest
            && $this->user()
            && $this->user()->role === 'admission'
            && (int) $grantRequest->user_id === (int) $this->user()->id
            && (int) $grantRequest->status_id === RequestStatus::RETURNED_TO_ADMISSION->value;
    }

    public function rules(): array
    {
        return [
            'request_type_id' => 'required|exists:request_types,id',
            'description' => 'required|string',
            'vot_items' => 'required|array|min:1',
            'vot_items.*.vot_code' => 'required|string|exists:vot_codes,code',
            'vot_items.*.description' => 'nullable|string|max:255',
            'vot_items.*.amount' => 'required|numeric|min:0',
            'document'    => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
            'deadline'    => 'nullable|date',
            'signature_data' => 'nullable|string',
        ];
    }
}
