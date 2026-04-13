<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\VotCode;
use App\Models\AuditLog;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Event::fake();
    }

    // Request Creation Tests
    public function test_admission_user_can_create_request_with_complete_data(): void
    {
        $requestType = RequestType::factory()->create();
        $votCode = VotCode::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);

        $requestData = [
            'request_type_id' => $requestType->id,
            'description' => 'Test request description',
            'vot_items' => [
                [
                    'vot_code' => $votCode->code,
                    'description' => 'Test VOT item',
                    'amount' => 1000.00,
                ],
            ],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'deadline' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($admission)->post('/requests', $requestData);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('requests', [
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'status_id' => RequestStatus::SUBMITTED->value,
            'total_amount' => 1000.00,
        ]);

        $request = GrantRequest::first();
        $this->assertNotNull($request->ref_number);
        $this->assertNotNull($request->signature_data);
        $this->assertNotNull($request->file_path);
        $this->assertNotNull($request->submitted_at);
    }

    public function test_request_creation_requires_signature(): void
    {
        $requestType = RequestType::factory()->create();
        $votCode = VotCode::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);

        $requestData = [
            'request_type_id' => $requestType->id,
            'description' => 'Test request description',
            'vot_items' => [
                [
                    'vot_code' => $votCode->code,
                    'description' => 'Test VOT item',
                    'amount' => 1000.00,
                ],
            ],
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($admission)->post('/requests', $requestData);

        $response->assertSessionHasErrors('signature_data');
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_request_creation_requires_vot_items(): void
    {
        $requestType = RequestType::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);

        $requestData = [
            'request_type_id' => $requestType->id,
            'description' => 'Test request description',
            'vot_items' => [],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($admission)->post('/requests', $requestData);

        $response->assertSessionHasErrors('vot_items');
        $this->assertDatabaseCount('requests', 0);
    }

    // Workflow State Tests
    public function test_complete_workflow_from_submission_to_approval(): void
    {
        // Setup
        $requestType = RequestType::factory()->create();
        $votCode = VotCode::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $dean = User::factory()->create(['role' => 'dean']);

        // Step 1: Admission creates request
        $request = GrantRequest::factory()->create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'status_id' => RequestStatus::SUBMITTED->value,
            'vot_items' => [['vot_code' => $votCode->code, 'description' => 'Test item', 'amount' => 1000.00]],
            'total_amount' => 1000.00,
            'signature_data' => 'data:image/png;base64,AAAA',
            'submitted_at' => now(),
        ]);

        // Step 2: Staff 1 approves
        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
            'notes' => 'Verified and approved',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $request->refresh();
        $this->assertEquals(RequestStatus::STAFF1_APPROVED->value, $request->status_id);
        $this->assertEquals($staff1->id, $request->verified_by);

        // Step 3: Staff 2 recommends
        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Recommended for approval',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $request->refresh();
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->status_id);
        $this->assertEquals($staff2->id, $request->recommended_by);

        // Step 4: Dean approves
        $response = $this->actingAs($dean)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::DEAN_APPROVED->value,
            'notes' => 'Final approval',
            'dean_signature_data' => 'data:image/png;base64,CCCC',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $request->refresh();
        $this->assertEquals(RequestStatus::DEAN_APPROVED->value, $request->status_id);
        $this->assertEquals($dean->id, $request->dean_approved_by);
    }

    // Role-based Action Tests
    public function test_only_admission_can_create_requests(): void
    {
        $requestType = RequestType::factory()->create();
        $votCode = VotCode::factory()->create();

        $roles = ['staff1', 'staff2', 'dean', 'admin'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get('/requests/create');

            $response->assertForbidden();
        }

        // Admission user should be able to access
        $admission = User::factory()->create(['role' => 'admission']);
        $response = $this->actingAs($admission)->get('/requests/create');
        $response->assertOk();
    }

    public function test_only_staff1_can_verify_requests(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        $roles = ['admission', 'staff2', 'dean', 'admin'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->patch("/requests/{$request->id}/status", [
                'status_id' => RequestStatus::STAFF1_APPROVED->value,
                'notes' => 'Test approval',
            ]);

            $response->assertForbidden();
        }

        // Staff 1 should be able to verify
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
            'notes' => 'Test approval',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
    }

    public function test_only_staff2_can_recommend_requests(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
        ]);

        $roles = ['admission', 'staff1', 'dean', 'admin'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->patch("/requests/{$request->id}/status", [
                'status_id' => RequestStatus::STAFF2_APPROVED->value,
                'notes' => 'Test recommendation',
                'staff2_signature_data' => 'data:image/png;base64,AAAA',
            ]);

            $response->assertForbidden();
        }

        // Staff 2 should be able to recommend
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Test recommendation',
            'staff2_signature_data' => 'data:image/png;base64,AAAA',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
    }

    public function test_only_dean_can_approve_requests(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
        ]);

        $roles = ['admission', 'staff1', 'staff2', 'admin'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->patch("/requests/{$request->id}/status", [
                'status_id' => RequestStatus::DEAN_APPROVED->value,
                'notes' => 'Test approval',
                'dean_signature_data' => 'data:image/png;base64,AAAA',
            ]);

            $response->assertForbidden();
        }

        // Dean should be able to approve
        $dean = User::factory()->create(['role' => 'dean']);
        $response = $this->actingAs($dean)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::DEAN_APPROVED->value,
            'notes' => 'Test approval',
            'dean_signature_data' => 'data:image/png;base64,AAAA',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
    }

    // Workflow Transition Tests
    public function test_workflow_transitions_follow_correct_order(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        // Cannot skip Staff 1 approval
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Test recommendation',
            'staff2_signature_data' => 'data:image/png;base64,AAAA',
        ]);

        $response->assertForbidden();

        // Cannot skip Staff 2 approval
        $dean = User::factory()->create(['role' => 'dean']);
        $response = $this->actingAs($dean)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::DEAN_APPROVED->value,
            'notes' => 'Test approval',
            'dean_signature_data' => 'data:image/png;base64,AAAA',
        ]);

        $response->assertForbidden();
    }

    // Request Rejection Tests
    public function test_request_can_be_rejected_at_any_stage(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        $staff1 = User::factory()->create(['role' => 'staff1']);

        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::REJECTED->value,
            'notes' => 'Insufficient documentation',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $request->refresh();
        $this->assertEquals(RequestStatus::REJECTED->value, $request->status_id);
    }

    // Request Return Tests
    public function test_request_can_be_returned_to_previous_stage(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
        ]);

        $staff2 = User::factory()->create(['role' => 'staff2']);

        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
            'notes' => 'Needs additional information',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $request->refresh();
        $this->assertEquals(RequestStatus::STAFF1_APPROVED->value, $request->status_id);
    }

    // Comment System Tests
    public function test_comments_can_be_added_to_requests(): void
    {
        $request = GrantRequest::factory()->create();
        $staff1 = User::factory()->create(['role' => 'staff1']);

        $response = $this->actingAs($staff1)->post("/requests/{$request->id}/comments", [
            'content' => 'This is a test comment',
            'type' => 'staff1',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $this->assertDatabaseHas('comments', [
            'request_id' => $request->id,
            'user_id' => $staff1->id,
            'content' => 'This is a test comment',
            'type' => 'staff1',
        ]);
    }

    // Audit Log Tests
    public function test_audit_logs_are_created_for_status_changes(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        $staff1 = User::factory()->create(['role' => 'staff1']);

        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
            'notes' => 'Test approval',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'request_id' => $request->id,
            'user_id' => $staff1->id,
            'action' => 'status_changed',
            'old_value' => RequestStatus::SUBMITTED->value,
            'new_value' => RequestStatus::STAFF1_APPROVED->value,
        ]);
    }

    // Request Status Enum Tests
    public function test_request_status_enum_methods(): void
    {
        $submitted = RequestStatus::SUBMITTED;
        $staff1Approved = RequestStatus::STAFF1_APPROVED;
        $staff2Approved = RequestStatus::STAFF2_APPROVED;
        $deanApproved = RequestStatus::DEAN_APPROVED;
        $rejected = RequestStatus::REJECTED;
        $returned = RequestStatus::RETURNED;

        // Test getLabel()
        $this->assertEquals('Submitted', $submitted->getLabel());
        $this->assertEquals('Staff 1 Approved', $staff1Approved->getLabel());
        $this->assertEquals('Staff 2 Approved', $staff2Approved->getLabel());
        $this->assertEquals('Dean Approved', $deanApproved->getLabel());
        $this->assertEquals('Rejected', $rejected->getLabel());
        $this->assertEquals('Returned', $returned->getLabel());

        // Test isFinal()
        $this->assertFalse($submitted->isFinal());
        $this->assertFalse($staff1Approved->isFinal());
        $this->assertFalse($staff2Approved->isFinal());
        $this->assertTrue($deanApproved->isFinal());
        $this->assertTrue($rejected->isFinal());
        $this->assertFalse($returned->isFinal());

        // Test canBeEditedByAdmission()
        $this->assertFalse($submitted->canBeEditedByAdmission());
        $this->assertFalse($staff1Approved->canBeEditedByAdmission());
        $this->assertFalse($staff2Approved->canBeEditedByAdmission());
        $this->assertFalse($deanApproved->canBeEditedByAdmission());
        $this->assertFalse($rejected->canBeEditedByAdmission());
        $this->assertTrue($returned->canBeEditedByAdmission());

        // Test canBeActionedByStaff1()
        $this->assertTrue($submitted->canBeActionedByStaff1());
        $this->assertFalse($staff1Approved->canBeActionedByStaff1());
        $this->assertFalse($staff2Approved->canBeActionedByStaff1());
        $this->assertFalse($deanApproved->canBeActionedByStaff1());
        $this->assertFalse($rejected->canBeActionedByStaff1());
        $this->assertFalse($returned->canBeActionedByStaff1());

        // Test canBeActionedByStaff2()
        $this->assertFalse($submitted->canBeActionedByStaff2());
        $this->assertTrue($staff1Approved->canBeActionedByStaff2());
        $this->assertFalse($staff2Approved->canBeActionedByStaff2());
        $this->assertFalse($deanApproved->canBeActionedByStaff2());
        $this->assertFalse($rejected->canBeActionedByStaff2());
        $this->assertFalse($returned->canBeActionedByStaff2());

        // Test canBeActionedByDean()
        $this->assertFalse($submitted->canBeActionedByDean());
        $this->assertFalse($staff1Approved->canBeActionedByDean());
        $this->assertTrue($staff2Approved->canBeActionedByDean());
        $this->assertFalse($deanApproved->canBeActionedByDean());
        $this->assertFalse($rejected->canBeActionedByDean());
        $this->assertFalse($returned->canBeActionedByDean());
    }

    // Request Filtering Tests
    public function test_requests_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create(['role' => 'admission']);
        
        GrantRequest::factory()->create(['status_id' => RequestStatus::SUBMITTED->value]);
        GrantRequest::factory()->create(['status_id' => RequestStatus::STAFF1_APPROVED->value]);
        GrantRequest::factory()->create(['status_id' => RequestStatus::DEAN_APPROVED->value]);

        $response = $this->actingAs($user)->get('/requests?status=' . RequestStatus::SUBMITTED->value);

        $response->assertOk();
        $response->assertViewHas('requests');
        $this->assertEquals(1, $response->viewData('requests')->count());
    }

    public function test_requests_can_be_searched_by_ref_number(): void
    {
        $user = User::factory()->create(['role' => 'admission']);
        
        $request1 = GrantRequest::factory()->create(['ref_number' => 'REQ-001']);
        $request2 = GrantRequest::factory()->create(['ref_number' => 'REQ-002']);

        $response = $this->actingAs($user)->get('/requests?search=REQ-001');

        $response->assertOk();
        $response->assertViewHas('requests');
        $this->assertEquals(1, $response->viewData('requests')->count());
        $this->assertEquals('REQ-001', $response->viewData('requests')->first()->ref_number);
    }

    // Request Priority Tests
    public function test_request_priority_calculation(): void
    {
        $request = GrantRequest::factory()->create([
            'deadline' => now()->addDays(2),
            'is_priority' => false,
        ]);

        // Test priority calculation based on deadline
        $this->assertTrue($request->isHighPriority());
        $this->assertEquals('High', $request->priorityLabel());
        $this->assertStringContainsString('text-red', $request->priorityBadgeClass());
    }

    // Request Edit Tests
    public function test_admission_can_edit_returned_request(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::RETURNED->value,
        ]);
        $admission = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($admission)->get("/requests/{$request->id}/edit");

        $response->assertOk();
        $response->assertViewIs('requests.edit');
    }

    public function test_admission_cannot_edit_approved_request(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
        ]);
        $admission = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($admission)->get("/requests/{$request->id}/edit");

        $response->assertForbidden();
    }
}
