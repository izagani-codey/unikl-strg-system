<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'user_id',
        'request_type_id',
        'ref_number',
        'status_id',
        'file_path',
        'payload',
        'staff_notes',
        'rejection_reason',
        'verified_by',
        'recommended_by',
        'revision_count',
        'deadline',
        'is_priority',
        'is_overridden',
        'overridden_by',
        'override_reason',
    ];

    protected $casts = [
        'payload'     => 'array',
        'is_priority' => 'boolean',
        'deadline'    => 'date',
        'is_overridden' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestType()
    {
        return $this->belongsTo(RequestType::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function recommendedBy()
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Helper methods using enum
    public function getStatus(): RequestStatus
    {
        return RequestStatus::from($this->status_id);
    }

    public function statusLabel(): string
    {
        return $this->getStatus()->getLabel();
    }

    public function statusClass(): string
    {
        return $this->getStatus()->getColor();
    }

    public function isFinal(): bool
    {
        return $this->getStatus()->isFinal();
    }

    public function canBeEditedByAdmission(): bool
    {
        return $this->getStatus()->canBeEditedByAdmission();
    }

    public function canBeActionedByStaff1(): bool
    {
        return $this->getStatus()->canBeActionedByStaff1();
    }

    public function canBeActionedByStaff2(): bool
    {
        return $this->getStatus()->canBeActionedByStaff2();
    }

    /**
     * Determine if this request can be overridden by staff.
     */
    public function canBeOverridden(): bool
    {
        return $this->isFinal();
    }

    /**
     * Get the staff role who made the current decision.
     */
    public function getDecisionMaker(): ?string
    {
        return match($this->status_id) {
            RequestStatus::APPROVED->value => 'staff2',
            RequestStatus::DECLINED->value => 'staff1',
            default => null,
        };
    }

    /**
     * Get the User object who made the current decision.
     */
    public function getDecisionUser()
    {
        if ($this->status_id === RequestStatus::APPROVED->value) {
            return $this->recommendedBy;
        }

        if ($this->status_id === RequestStatus::DECLINED->value) {
            $latestAuditLog = $this->auditLogs()
                ->where('action', 'declined')
                ->latest()
                ->first();

            return $latestAuditLog ? $latestAuditLog->user : null;
        }

        return null;
    }

    /**
     * Check if request is urgent (deadline within 3 days).
     */
    public function isUrgent(): bool
    {
        if (!$this->deadline) {
            return false;
        }

        return $this->deadline->diffInDays(now()) <= 3 && $this->deadline->isFuture();
    }

    /**
     * Get human-readable priority label.
     */
    public function priorityLabel(): string
    {
        return match(true) {
            $this->isUrgent() => 'URGENT ⚠️',
            $this->is_priority => 'HIGH PRIORITY',
            default => 'NORMAL',
        };
    }

    /**
     * Get CSS classes for priority badge.
     */
    public function priorityBadgeClass(): string
    {
        return match(true) {
            $this->isUrgent() => 'bg-red-500 text-white',
            $this->is_priority => 'bg-orange-500 text-white',
            default => 'bg-green-500 text-white',
        };
    }

    /**
     * Get number of days until deadline.
     */
    public function daysUntilDeadline(): ?int
    {
        if (!$this->deadline) {
            return null;
        }

        return $this->deadline->diffInDays(now());
    }

    /** 
     * Filter requests by priority flag.
     */
    public function scopeByPriority($query, $isPriority)
    {
        return $query->where('is_priority', $isPriority);
    }

    /**
     * Filter urgent requests (deadline within 3 days, not finalized).
     */
    public function scopeUrgent($query)
    {
        return $query->whereBetween('deadline', [now(), now()->addDays(3)])
            ->whereNotIn('status_id', [RequestStatus::APPROVED->value, RequestStatus::DECLINED->value])
            ->orderBy('deadline', 'asc');
    }

    /**
     * Filter by status using enum
     */
    public function scopeByStatus($query, RequestStatus $status)
    {
        return $query->where('status_id', $status->value);
    }
}
