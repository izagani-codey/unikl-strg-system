<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
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
        $validStatusValues = implode(',', array_map(
            static fn (RequestStatus $status): int => $status->value,
            RequestStatus::cases()
        ));

        return [
            'status_id' => "required|integer|in:{$validStatusValues}",
            'notes'     => 'nullable|string',
            'rejection_reason' => 'nullable|string',
            'override_reason' => 'nullable|string',
            'staff1_signature_data' => 'nullable|string',
            'staff2_signature_data' => 'nullable|string',
            'dean_signature_data' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = $this->user()?->role;
            $newStatus = RequestStatus::tryFrom((int) $this->input('status_id'));

            $staff2RequiresSignature = $newStatus && in_array($newStatus, [
                RequestStatus::STAFF2_APPROVED,
                RequestStatus::REJECTED,
            ], true);

            if ($role === 'staff2' && $staff2RequiresSignature && empty($this->input('staff2_signature_data'))) {
                $validator->errors()->add('staff2_signature_data', 'Staff 2 signature is required.');
            }

            $deanRequiresSignature = $newStatus && in_array($newStatus, [
                RequestStatus::DEAN_APPROVED,
                RequestStatus::REJECTED,
            ], true);

            if ($role === 'dean' && $deanRequiresSignature && empty($this->input('dean_signature_data'))) {
                $validator->errors()->add('dean_signature_data', 'Dean signature is required.');
            }
        });
    }
}
