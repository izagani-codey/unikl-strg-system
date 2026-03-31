<?php

namespace App\Http\Requests;

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
            && (int) $grantRequest->status_id === 3;
    }

    public function rules(): array
    {
        return [
            'amount'      => 'required|numeric|min:0',
            'description' => 'required|string',
            'document'    => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
            'deadline'    => 'nullable|date',
        ];
    }
}
