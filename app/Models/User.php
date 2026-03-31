<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ==========================================
    // Role helpers — match the actual role strings
    // ==========================================

    public function isAdmission(): bool
    {
        return $this->role === 'admission';
    }

    public function isStaff1(): bool
    {
        return $this->role === 'staff1';
    }

    public function isStaff2(): bool
    {
        return $this->role === 'staff2';
    }

    /** Alias kept for any Blade templates still using this name. */
    public function isAdmissions(): bool
    {
        return $this->isAdmission();
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
