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
            'request_type_id'         => 'required|exists:request_types,id',
            'description'             => 'required|string',
            'vot_items'               => 'required|array|min:1',
            'vot_items.*.vot_code'    => 'required|string|max:50',
            'vot_items.*.description' => 'required|string|max:255',
            'vot_items.*.amount'      => 'required|numeric|min:0.01',
            'signature_data'          => 'required|string', // base64 PNG from signature pad
            'deadline'                => 'nullable|date|after:today',
            'priority'                => 'nullable|boolean',
            // Optional file attachment (no longer required — PDF generated automatically)
            'document'                => [
                'nullable', 'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vot_items.required'             => 'At least one VOT item is required.',
            'vot_items.min'                  => 'At least one VOT item is required.',
            'vot_items.*.vot_code.required'  => 'Each VOT item must have a VOT code.',
            'vot_items.*.description.required' => 'Each VOT item must have a description.',
            'vot_items.*.amount.required'    => 'Each VOT item must have an amount.',
            'vot_items.*.amount.min'         => 'Each VOT amount must be greater than 0.',
            'signature_data.required'        => 'Please sign the form before submitting.',
        ];
    }
}