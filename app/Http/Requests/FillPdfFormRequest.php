<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FillPdfFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow any authenticated user to fill forms
    }

    public function rules(): array
    {
        return [
            'template_id' => 'required|exists:form_templates,id',
        ];
    }
}
