<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admission';
    }

    public function rules(): array
    {
        return [
            'request_type_id' => 'required|exists:request_types,id',
            'amount'          => 'nullable|numeric',
            'description'     => 'required|string',
            'document'        => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
            'deadline'        => 'nullable|date',
        ];
    }
}
