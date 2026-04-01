<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverrideLog extends Model
{
    protected $fillable = [
        'request_id',
        'user_id',
        'action_type',
        'reason',
        'original_data',
        'new_data',
    ];

    protected $casts = [
        'original_data' => 'array',
        'new_data' => 'array',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // Helper methods
    // ==========================================

    public function getActionTypeLabel(): string
    {
        return match($this->action_type) {
            'approve' => 'Direct Approval',
            'reject_reverse' => 'Rejection Reversal',
            'bypass_verification' => 'Bypass Verification',
            'priority_override' => 'Priority Override',
            default => 'Override Action',
        };
    }

    public function getActionTypeClass(): string
    {
        return match($this->action_type) {
            'approve' => 'bg-green-100 text-green-800',
            'reject_reverse' => 'bg-blue-100 text-blue-800',
            'bypass_verification' => 'bg-purple-100 text-purple-800',
            'priority_override' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
