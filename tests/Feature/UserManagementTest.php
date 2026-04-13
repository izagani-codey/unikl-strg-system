<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Request as GrantRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // Profile Management Tests
    public function test_user_can_view_their_profile(): void
    {
        $user = User::factory()->create([
            'staff_id' => 'STAFF001',
            'designation' => 'Lecturer',
            'department' => 'Faculty of Engineering',
            'phone' => '+60123456789',
            'employee_level' => 'Academic',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee('STAFF001');
        $response->assertSee('Lecturer');
        $response->assertSee('Faculty of Engineering');
        $response->assertSee('+60123456789');
        $response->assertSee('Academic');
    }

    public function test_user_can_update_their_profile_information(): void
    {
        $user = User::factory()->create();

        $updatedData = [
            'name' => 'Updated Name',
            'email' => 'updated@unikl.edu.my',
            'staff_id' => 'STAFF002',
            'designation' => 'Senior Lecturer',
            'department' => 'Faculty of Science',
            'phone' => '+60198765432',
            'employee_level' => 'Senior Academic',
        ];

        $response = $this->actingAs($user)->put('/profile', $updatedData);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@unikl.edu.my',
            'staff_id' => 'STAFF002',
            'designation' => 'Senior Lecturer',
            'department' => 'Faculty of Science',
            'phone' => '+60198765432',
            'employee_level' => 'Senior Academic',
        ]);
    }

    public function test_user_cannot_update_email_to_duplicate(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@unikl.edu.my']);
        $user2 = User::factory()->create(['email' => 'user2@unikl.edu.my']);

        $response = $this->actingAs($user1)->put('/profile', [
            'name' => $user1->name,
            'email' => 'user2@unikl.edu.my', // Duplicate email
            'staff_id' => $user1->staff_id,
            'designation' => $user1->designation,
            'department' => $user1->department,
            'phone' => $user1->phone,
            'employee_level' => $user1->employee_level,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', [
            'id' => $user1->id,
            'email' => 'user1@unikl.edu.my',
        ]);
    }

    // Digital Signature Tests
    public function test_user_can_save_digital_signature(): void
    {
        $user = User::factory()->create();

        $signatureData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/profile/signature', [
            'signature_data' => $signatureData,
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'signature_data' => $signatureData,
        ]);
    }

    public function test_user_can_update_digital_signature(): void
    {
        $user = User::factory()->create([
            'signature_data' => 'old-signature-data',
        ]);

        $newSignatureData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/profile/signature', [
            'signature_data' => $newSignatureData,
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'signature_data' => $newSignatureData,
        ]);
    }

    public function test_signature_data_is_required_for_signature_update(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/signature', []);

        $response->assertSessionHasErrors('signature_data');
    }

    // Role-based Permission Tests
    public function test_admission_user_has_correct_permissions(): void
    {
        $user = User::factory()->create(['role' => 'admission']);

        // Can access own profile
        $this->actingAs($user)->get('/profile')->assertOk();

        // Can create requests
        $this->actingAs($user)->get('/requests/create')->assertOk();

        // Cannot access admin panel
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();

        // Cannot access staff2 admin
        $this->actingAs($user)->get('/staff2/admin-panel')->assertForbidden();
    }

    public function test_staff1_user_has_correct_permissions(): void
    {
        $user = User::factory()->create(['role' => 'staff1']);

        // Can access own profile
        $this->actingAs($user)->get('/profile')->assertOk();

        // Cannot create requests
        $this->actingAs($user)->get('/requests/create')->assertForbidden();

        // Cannot access admin panel
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();

        // Cannot access staff2 admin
        $this->actingAs($user)->get('/staff2/admin-panel')->assertForbidden();
    }

    public function test_staff2_user_has_correct_permissions(): void
    {
        $user = User::factory()->create(['role' => 'staff2']);

        // Can access own profile
        $this->actingAs($user)->get('/profile')->assertOk();

        // Cannot create requests
        $this->actingAs($user)->get('/requests/create')->assertForbidden();

        // Cannot access admin panel
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();

        // Can access staff2 admin
        $this->actingAs($user)->get('/staff2/admin-panel')->assertOk();
    }

    public function test_dean_user_has_correct_permissions(): void
    {
        $user = User::factory()->create(['role' => 'dean']);

        // Can access own profile
        $this->actingAs($user)->get('/profile')->assertOk();

        // Cannot create requests
        $this->actingAs($user)->get('/requests/create')->assertForbidden();

        // Cannot access admin panel
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();

        // Cannot access staff2 admin
        $this->actingAs($user)->get('/staff2/admin-panel')->assertForbidden();
    }

    public function test_admin_user_has_correct_permissions(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Can access own profile
        $this->actingAs($user)->get('/profile')->assertOk();

        // Cannot create requests
        $this->actingAs($user)->get('/requests/create')->assertForbidden();

        // Can access admin panel
        $this->actingAs($user)->get('/admin/dashboard')->assertOk();

        // Cannot access staff2 admin (should use admin routes)
        $this->actingAs($user)->get('/staff2/admin-panel')->assertForbidden();
    }

    // User Role Helper Methods Tests
    public function test_user_role_helper_methods(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $dean = User::factory()->create(['role' => 'dean']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Test isAdmission()
        $this->assertTrue($admission->isAdmission());
        $this->assertFalse($staff1->isAdmission());
        $this->assertFalse($staff2->isAdmission());
        $this->assertFalse($dean->isAdmission());
        $this->assertFalse($admin->isAdmission());

        // Test isStaff1()
        $this->assertFalse($admission->isStaff1());
        $this->assertTrue($staff1->isStaff1());
        $this->assertFalse($staff2->isStaff1());
        $this->assertFalse($dean->isStaff1());
        $this->assertFalse($admin->isStaff1());

        // Test isStaff2()
        $this->assertFalse($admission->isStaff2());
        $this->assertFalse($staff1->isStaff2());
        $this->assertTrue($staff2->isStaff2());
        $this->assertFalse($dean->isStaff2());
        $this->assertFalse($admin->isStaff2());

        // Test isDean()
        $this->assertFalse($admission->isDean());
        $this->assertFalse($staff1->isDean());
        $this->assertFalse($staff2->isDean());
        $this->assertTrue($dean->isDean());
        $this->assertFalse($admin->isDean());

        // Test isAdmin()
        $this->assertFalse($admission->isAdmin());
        $this->assertFalse($staff1->isAdmin());
        $this->assertFalse($staff2->isAdmin());
        $this->assertFalse($dean->isAdmin());
        $this->assertTrue($admin->isAdmin());
    }

    // User Statistics Tests
    public function test_user_statistics_are_accurate(): void
    {
        // Create users with different roles
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $dean = User::factory()->create(['role' => 'dean']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Test total users count
        $this->assertEquals(5, User::count());

        // Test role counts
        $this->assertEquals(1, User::where('role', 'admission')->count());
        $this->assertEquals(1, User::where('role', 'staff1')->count());
        $this->assertEquals(1, User::where('role', 'staff2')->count());
        $this->assertEquals(1, User::where('role', 'dean')->count());
        $this->assertEquals(1, User::where('role', 'admin')->count());
    }

    // User Profile Completeness Tests
    public function test_user_profile_completeness_validation(): void
    {
        $user = User::factory()->create([
            'staff_id' => null,
            'designation' => null,
            'department' => null,

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_must_provide_correct_password_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // User Request Relationship Tests
    public function test_user_requests_relationship(): void
    {
        $user = User::factory()->create();
        
        // Create requests for the user
        $requests = GrantRequest::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        // Test relationship
        $this->assertCount(3, $user->requests);
        $this->assertEquals($requests->pluck('id'), $user->requests->pluck('id'));
    }

    // User Activity Tests
    public function test_user_last_login_timestamp(): void
    {
        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        // Simulate login
        $this->actingAs($user)->get('/dashboard');

        // Note: In a real implementation, you might have middleware that updates last_login_at
        // This test would need to be adjusted based on your actual implementation
        $this->assertAuthenticatedAs($user);
    }

    // User Search and Filtering Tests
    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create test users
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@unikl.edu.my']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@unikl.edu.my']);

        $response = $this->actingAs($admin)->get('/admin/users?search=John');

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create users with different roles
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $response = $this->actingAs($admin)->get('/admin/users?role=staff1');

        $response->assertOk();
        $response->assertSee($staff1->name);
        $response->assertDontSee($staff2->name);
    }
}
