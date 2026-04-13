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
        $rules = [
            'request_type_id'         => 'required|exists:request_types,id',
            'description'             => 'required|string|max:500',
            'dynamic_fields'          => 'nullable|array',
            'vot_items'               => 'required|array|min:1',
            'vot_items.*.vot_code'    => 'required|string|exists:vot_codes,code',
            'vot_items.*.description' => 'required|string|max:255',
            'vot_items.*.amount'      => 'required|numeric|min:0',
            'signature_data'          => 'required|string',
            'deadline'                => 'required|date|after:today',
            'priority'                => 'nullable|boolean',
            'document'                => [
                'nullable', 'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];

        // Add dynamic field validation based on request type
        $requestTypeId = $this->input('request_type_id');
        if ($requestTypeId) {
            $requestType = \App\Models\RequestType::find($requestTypeId);
            if ($requestType && $requestType->field_schema) {
                foreach ($requestType->field_schema as $field) {
                    $fieldName = "dynamic_fields.{$field['name']}";
                    $isRequired = $field['required'] ?? false;
                    
                    switch ($field['type']) {
                        case 'text':
                        case 'textarea':
                            $rules[$fieldName] = $isRequired ? 'required|string' : 'nullable|string';
                            break;
                        case 'number':
                            $rules[$fieldName] = $isRequired ? 'required|numeric' : 'nullable|numeric';
                            break;
                        case 'select':
                            $rules[$fieldName] = $isRequired ? 'required|string' : 'nullable|string';
                            break;
                        case 'date':
                            $rules[$fieldName] = $isRequired ? 'required|date' : 'nullable|date';
                            break;
                        case 'checkbox':
                            $rules[$fieldName] = 'nullable|boolean';
                            break;
                        case 'date_range':
                            if (isset($field['fields'])) {
                                foreach ($field['fields'] as $rangeField) {
                                    $rangeFieldName = "dynamic_fields.{$rangeField}";
                                    $rules[$rangeFieldName] = $isRequired ? 'required|date' : 'nullable|date';
                                }
                            }
                            break;
                    }
                }
            }
        }

        return $rules;
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
            'signature_data.required'           => 'Please sign the form before submitting.',
            'deadline.required'                => 'Deadline is required.',
            'deadline.after'                  => 'Deadline must be after today.',
            'document.max'                    => 'Document file size must not exceed 5MB.',
            'document.mimes'                  => 'Document must be a PDF, JPG, or PNG file.',
        ];
    }
}
