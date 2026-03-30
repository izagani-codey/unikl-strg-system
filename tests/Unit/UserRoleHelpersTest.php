<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleHelpersTest extends TestCase
{
    public function test_user_role_helpers_match_expected_roles(): void
    {
        $admission = new User(['role' => 'admission']);
        $this->assertTrue($admission->isAdmission());
        $this->assertTrue($admission->isAdmissions());
        $this->assertFalse($admission->isStaff1());
        $this->assertFalse($admission->isStaff2());

        $staff1 = new User(['role' => 'staff1']);
        $this->assertTrue($staff1->isStaff1());
        $this->assertFalse($staff1->isAdmission());
        $this->assertFalse($staff1->isStaff2());

        $staff2 = new User(['role' => 'staff2']);
        $this->assertTrue($staff2->isStaff2());
        $this->assertFalse($staff2->isStaff1());
        $this->assertFalse($staff2->isAdmission());
    }
}
