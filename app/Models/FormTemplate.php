<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    protected $fillable = [
        'name',
        'template_type',
        'file_path',
        'field_mappings',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'field_mappings' => 'array',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function templateUsage()
    {
        return $this->hasMany(TemplateUsage::class, 'template_id');
    }

    // ==========================================
    // Helper methods
    // ==========================================

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getFieldTypeLabel(): string
    {
        return match($this->template_type) {
            'vot_form' => 'VOT Form',
            'grant_application' => 'Grant Application',
            'budget_request' => 'Budget Request',
            'travel_request' => 'Travel Request',
            default => 'General Form',
        };
    }

    public function getAvailableFields(): array
    {
        return [
            'user.name' => 'Full Name',
            'user.staff_id' => 'Staff ID',
            'user.designation' => 'Designation',
            'user.department' => 'Department',
            'user.phone' => 'Phone Number',
            'user.email' => 'Email Address',
            'user.employee_level' => 'Employee Level',
            'user.signature_data' => 'Digital Signature',
            'request.ref_number' => 'Reference Number',
            'request.request_type' => 'Request Type',
            'request.total_amount' => 'Total Amount',
            'request.deadline' => 'Deadline',
            'request.description' => 'Description',
            'request.submitted_at' => 'Submission Date',
        ];
    }

    public function getMappedFields(): array
    {
        return $this->field_mappings ?? [];
    }

    public function updateFieldMappings(array $mappings): void
    {
        $this->field_mappings = $mappings;
        $this->save();
    }
}
