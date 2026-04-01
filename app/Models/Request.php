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
        'revision_count', 'deadline', 'is_priority',
        'is_overridden', 'overridden_by', 'override_reason',
    ];

    protected $casts = [
        'payload'     => 'array',
        'vot_items'   => 'array',
        'is_priority' => 'boolean',
        'is_overridden' => 'boolean',
        'deadline'    => 'date',
        'signed_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function user()         { return $this->belongsTo(User::class); }
    public function requestType()  { return $this->belongsTo(RequestType::class); }
    public function verifiedBy()   { return $this->belongsTo(User::class, 'verified_by'); }
    public function recommendedBy(){ return $this->belongsTo(User::class, 'recommended_by'); }
    public function comments()     { return $this->hasMany(Comment::class); }
    public function auditLogs()    { return $this->hasMany(AuditLog::class); }
    public function documents()    { return $this->hasMany(Document::class); }

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
        return 'NORMAL';
    }

    public function priorityBadgeClass(): string
    {
        if ($this->isUrgent())  return 'bg-red-500 text-white';
        if ($this->is_priority) return 'bg-orange-500 text-white';
        return 'bg-green-500 text-white';
    }

    public function daysUntilDeadline(): ?int
    {
        return $this->deadline?->diffInDays(now());
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