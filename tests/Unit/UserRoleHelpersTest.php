<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleHelpersTest extends TestCase
{
    public function test_admission_role_helpers(): void
    {
        $user = new User(['role' => 'admission']);

        $this->assertTrue($user->isAdmission());
        $this->assertTrue($user->isAdmissions()); // alias
        $this->assertFalse($user->isStaff1());
        $this->assertFalse($user->isStaff2());
    }

    public function test_staff1_role_helpers(): void
    {
        $user = new User(['role' => 'staff1']);

        $this->assertTrue($user->isStaff1());
        $this->assertFalse($user->isAdmission());
        $this->assertFalse($user->isStaff2());
    }

    public function test_staff2_role_helpers(): void
    {
        $user = new User(['role' => 'staff2']);

        $this->assertTrue($user->isStaff2());
        $this->assertFalse($user->isStaff1());
        $this->assertFalse($user->isAdmission());
    }

    public function test_unknown_role_returns_false_for_all_helpers(): void
    {
        $user = new User(['role' => 'admin']);

        $this->assertFalse($user->isAdmission());
        $this->assertFalse($user->isStaff1());
        $this->assertFalse($user->isStaff2());
    }
}
