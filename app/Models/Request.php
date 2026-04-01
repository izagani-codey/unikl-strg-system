<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'user_id', 'request_type_id', 'ref_number', 'status_id',
        'file_path', 'payload',
        'vot_items', 'total_amount',
        'submitter_staff_id', 'submitter_designation', 'submitter_department',
        'submitter_phone', 'submitter_employee_level',
        'signature_data', 'signed_at', 'submitted_at',
        'staff_notes', 'rejection_reason',
        'verified_by', 'recommended_by',
        'dean_approved_by', 'dean_approved_at', 'dean_notes', 'dean_rejection_reason',
        'revision_count', 'deadline', 'is_priority',
        'is_overridden', 'overridden_by', 'override_reason', 'overridden_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'vot_items'   => 'array',
        'is_priority' => 'boolean',
        'is_overridden' => 'boolean',
        'deadline'    => 'date',
        'signed_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'dean_approved_at' => 'datetime',
        'overridden_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function user()         { return $this->belongsTo(User::class); }
    public function requestType()  { return $this->belongsTo(RequestType::class); }
    public function verifiedBy()   { return $this->belongsTo(User::class, 'verified_by'); }
    public function recommendedBy(){ return $this->belongsTo(User::class, 'recommended_by'); }
    public function deanApprovedBy(){ return $this->belongsTo(User::class, 'dean_approved_by'); }
    public function overriddenBy() { return $this->belongsTo(User::class, 'overridden_by'); }
    public function comments()     { return $this->hasMany(Comment::class); }
    public function auditLogs()    { return $this->hasMany(AuditLog::class); }
    public function documents()    { return $this->hasMany(Document::class); }
    public function overrideLogs() { return $this->hasMany(OverrideLog::class); }

    // ==========================================
    // VOT helpers
    // ==========================================

    public function getVotItems(): array
    {
        return $this->vot_items ?? [];
    }

    public function computedTotal(): float
    {
        return collect($this->getVotItems())->sum(fn($item) => (float) ($item['amount'] ?? 0));
    }

    public function hasSignature(): bool
    {
        return !empty($this->signature_data);
    }

    // ==========================================
    // Status helpers
    // ==========================================

    public function getStatus(): RequestStatus       { return RequestStatus::from($this->status_id); }
    public function statusLabel(): string             { return $this->getStatus()->getLabel(); }
    public function statusClass(): string             { return $this->getStatus()->getColor(); }
    public function isFinal(): bool                   { return $this->getStatus()->isFinal(); }
    public function canBeEditedByAdmission(): bool    { return $this->getStatus()->canBeEditedByAdmission(); }
    public function canBeActionedByStaff1(): bool     { return $this->getStatus()->canBeActionedByStaff1(); }
    public function canBeActionedByStaff2(): bool     { return $this->getStatus()->canBeActionedByStaff2(); }
    public function canBeActionedByDean(): bool       { return $this->getStatus()->canBeActionedByDean(); }
    public function canBeOverridden(): bool           { return $this->isFinal(); }

    // ==========================================
    // Priority / deadline helpers
    // ==========================================

    public function isUrgent(): bool
    {
        if (!$this->deadline) return false;
        return $this->deadline->diffInDays(now()) <= 3 && $this->deadline->isFuture();
    }

    public function priorityLabel(): string
    {
        if ($this->isUrgent())   return 'URGENT ⚠️';
        if ($this->is_priority)  return 'HIGH PRIORITY';
        if ($this->isAutoHighPriority()) return 'HIGH PRIORITY (Auto)';
        return 'NORMAL';
    }

    public function priorityBadgeClass(): string
    {
        if ($this->isUrgent())  return 'bg-red-500 text-white';
        if ($this->is_priority) return 'bg-orange-500 text-white';
        if ($this->isAutoHighPriority()) return 'bg-yellow-500 text-white';
        return 'bg-green-500 text-white';
    }

    public function isAutoHighPriority(): bool
    {
        if (!$this->deadline) return false;
        $daysUntil = $this->daysUntilDeadline();
        return $daysUntil !== null && $daysUntil <= 5 && $daysUntil > 3;
    }

    public function calculateAutoPriority(): void
    {
        if (!$this->deadline) return;
        
        $daysUntil = $this->daysUntilDeadline();
        if ($daysUntil !== null && $daysUntil <= 5) {
            $this->is_priority = true;
            $this->save();
        }
    }

    public function updatePriorityFromDeadline(): void
    {
        if (!$this->deadline) return;
        
        $daysUntil = $this->daysUntilDeadline();
        $shouldBeHighPriority = $daysUntil !== null && $daysUntil <= 5;
        
        // Only auto-update if not manually set (we could add a is_manual_priority flag)
        if ($shouldBeHighPriority && !$this->is_priority) {
            $this->is_priority = true;
            $this->save();
        } elseif (!$shouldBeHighPriority && $this->isAutoHighPriority()) {
            $this->is_priority = false;
            $this->save();
        }
    }

    public function daysUntilDeadline(): ?int
    {
        return $this->deadline?->diffInDays(now());
    }

    // ==========================================
    // Override helpers
    // ==========================================

    public function isOverridden(): bool
    {
        return $this->is_overridden && $this->overridden_by && $this->overridden_at;
    }

    public function markAsOverridden(User $overriddenBy, string $reason): void
    {
        $this->is_overridden = true;
        $this->overridden_by = $overriddenBy->id;
        $this->override_reason = $reason;
        $this->overridden_at = now();
        $this->save();
    }

    public function clearOverride(): void
    {
        $this->is_overridden = false;
        $this->overridden_by = null;
        $this->override_reason = null;
        $this->overridden_at = null;
        $this->save();
    }

    // ==========================================
    // Dean approval helpers
    // ==========================================

    public function approveByDean(User $dean, ?string $notes = null): void
    {
        $this->dean_approved_by = $dean->id;
        $this->dean_approved_at = now();
        $this->dean_notes = $notes;
        $this->dean_rejection_reason = null;
        $this->status_id = RequestStatus::APPROVED->value;
        $this->save();
    }

    public function rejectByDean(User $dean, string $reason): void
    {
        $this->dean_approved_by = $dean->id;
        $this->dean_approved_at = now();
        $this->dean_rejection_reason = $reason;
        $this->dean_notes = null;
        $this->status_id = RequestStatus::DECLINED->value;
        $this->save();
    }

    public function returnToStaff1(User $dean, string $reason): void
    {
        $this->dean_approved_by = $dean->id;
        $this->dean_approved_at = now();
        $this->dean_notes = $reason;
        $this->dean_rejection_reason = null;
        $this->status_id = RequestStatus::RETURNED_TO_STAFF_1->value;
        $this->save();
    }

    public function returnToStaff2(User $dean, string $reason): void
    {
        $this->dean_approved_by = $dean->id;
        $this->dean_approved_at = now();
        $this->dean_notes = $reason;
        $this->dean_rejection_reason = null;
        $this->status_id = RequestStatus::RETURNED_TO_STAFF_2->value;
        $this->save();
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeByPriority($query, $isPriority) { return $query->where('is_priority', $isPriority); }
    public function scopeUrgent($query)
    {
        return $query->whereBetween('deadline', [now(), now()->addDays(3)])
            ->whereNotIn('status_id', [RequestStatus::APPROVED->value, RequestStatus::DECLINED->value])
            ->orderBy('deadline', 'asc');
    }
    public function scopeByStatus($query, RequestStatus $status) { return $query->where('status_id', $status->value); }
}