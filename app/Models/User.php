<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'staff_id', 'designation', 'department', 'phone', 'employee_level', 'signature_data', 'override_enabled', 'override_enabled_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'override_enabled' => 'boolean',
            'override_enabled_at' => 'datetime',
        ];
    }

    // ==========================================
    // Role helpers
    // ==========================================

    public function isAdmission(): bool { return $this->role === 'admission'; }
    public function isStaff1(): bool    { return $this->role === 'staff1'; }
    public function isStaff2(): bool    { return $this->role === 'staff2'; }
    public function isDean(): bool      { return $this->role === 'dean'; }
    public function isAdmissions(): bool { return $this->isAdmission(); }

    // ==========================================
    // Profile helpers
    // ==========================================

    public function hasCompleteProfile(): bool
    {
        return !empty($this->staff_id)
            && !empty($this->designation)
            && !empty($this->department)
            && !empty($this->phone);
    }

    public function displayName(): string
    {
        return $this->name . ($this->staff_id ? ' (' . $this->staff_id . ')' : '');
    }

    // ==========================================
    // Override helpers
    // ==========================================

    public function canOverride(): bool
    {
        return $this->isStaff2() && $this->override_enabled;
    }

    public function enableOverride(): void
    {
        $this->override_enabled = true;
        $this->override_enabled_at = now();
        $this->save();
    }

    public function disableOverride(): void
    {
        $this->override_enabled = false;
        $this->override_enabled_at = null;
        $this->save();
    }

    public function toggleOverride(): void
    {
        if ($this->override_enabled) {
            $this->disableOverride();
        } else {
            $this->enableOverride();
        }
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest('created_at');
    }
}