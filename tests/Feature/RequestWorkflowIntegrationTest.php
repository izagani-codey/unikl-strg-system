<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\VotCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->requestType = RequestType::factory()->create();
        $this->votCode = VotCode::factory()->create(['code' => 'VOT11000', 'is_active' => true]);
        
        $this->admission = User::factory()->create(['role' => 'admission']);
        $this->staff1 = User::factory()->create(['role' => 'staff1']);
        $this->staff2 = User::factory()->create(['role' => 'staff2']);
        $this->dean = User::factory()->create(['role' => 'dean']);
    }

    public function test_complete_workflow_from_submission_to_approval(): void
    {
        // 1. Admission submits request
        $request = $this->createTestRequest();
        
        $this->assertEquals(RequestStatus::SUBMITTED->value, $request->status_id);
        $this->assertEquals($this->admission->id, $request->user_id);

        // 2. Staff 1 can view submitted request
        $response = $this->actingAs($this->staff1)
            ->get(route('requests.show', $request->id));
        $response->assertOk();

        // 3. Staff 1 verifies request
        $response = $this->actingAs($this->staff1)
            ->patch(route('requests.updateStatus', $request->id), [
                'status_id' => RequestStatus::VERIFIED->value,
                'staff_notes' => 'Verified by Staff 1'
            ]);

        $request->refresh();
        $this->assertEquals(RequestStatus::VERIFIED->value, $request->status_id);
        $this->assertEquals($this->staff1->id, $request->verified_by);

        // 4. Staff 2 can view verified request
        $response = $this->actingAs($this->staff2)
            ->get(route('requests.show', $request->id));
        $response->assertOk();

        // 5. Staff 2 recommends request
        $response = $this->actingAs($this->staff2)
            ->patch(route('requests.updateStatus', $request->id), [
                'status_id' => RequestStatus::RECOMMENDED->value,
                'staff_notes' => 'Recommended by Staff 2'
            ]);

        $request->refresh();
        $this->assertEquals(RequestStatus::RECOMMENDED->value, $request->status_id);
        $this->assertEquals($this->staff2->id, $request->recommended_by);

        // 6. Dean can view recommended request
        $response = $this->actingAs($this->dean)
            ->get(route('requests.show', $request->id));
        $response->assertOk();

        // 7. Dean approves request
        $response = $this->actingAs($this->dean)
            ->patch(route('requests.updateStatus', $request->id), [
                'status_id' => RequestStatus::APPROVED->value,
                'dean_notes' => 'Approved by Dean'
            ]);

        $request->refresh();
        $this->assertEquals(RequestStatus::APPROVED->value, $request->status_id);
        $this->assertEquals($this->dean->id, $request->dean_approved_by);
    }

    public function test_request_rejection_workflow(): void
    {
        // 1. Admission submits request
        $request = $this->createTestRequest();

        // 2. Staff 1 rejects request
        $response = $this->actingAs($this->staff1)
            ->patch(route('requests.updateStatus', $request->id), [
                'status_id' => RequestStatus::RETURNED->value,
                'rejection_reason' => 'Insufficient documentation'
            ]);

        $request->refresh();
        $this->assertEquals(RequestStatus::RETURNED->value, $request->status_id);
        $this->assertNotNull($request->rejection_reason);
    }

    public function test_staff2_override_functionality(): void
    {
        // 1. Create request and enable override for staff2
        $request = $this->createTestRequest();
        $this->staff2->update(['override_enabled' => true]);

        // 2. Staff 2 can override and directly approve
        $response = $this->actingAs($this->staff2)
            ->post(route('requests.performOverride', $request->id), [
                'override_reason' => 'Urgent approval needed'
            ]);

        $request->refresh();
        $this->assertEquals(RequestStatus::APPROVED->value, $request->status_id);
        $this->assertTrue($request->isOverridden());
        $this->assertEquals($this->staff2->id, $request->overridden_by);
    }

    public function test_role_based_access_control(): void
    {
        $request = $this->createTestRequest();

        // Admission can only see their own requests
        $response = $this->actingAs($this->admission)
            ->get(route('requests.index'));
        $response->assertSee($request->reference_number);

        $otherAdmission = User::factory()->create(['role' => 'admission']);
        $otherRequest = $this->createTestRequestForUser($otherAdmission);

        $response = $this->actingAs($this->admission)
            ->get(route('requests.index'));
        $response->assertDontSee($otherRequest->reference_number);

        // Staff 1 can see all requests needing verification
        $response = $this->actingAs($this->staff1)
            ->get(route('requests.index'));
        $response->assertSee($request->reference_number);

        // Staff 2 can see all requests
        $response = $this->actingAs($this->staff2)
            ->get(route('requests.index'));
        $response->assertSee($request->reference_number);
    }

    private function createTestRequest(): GrantRequest
    {
        return GrantRequest::create([
            'user_id' => $this->admission->id,
            'request_type_id' => $this->requestType->id,
            'ref_number' => 'REQ-' . time(),
            'status_id' => RequestStatus::SUBMITTED->value,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'vot_items' => [
                [
                    'vot_code' => 'VOT11000',
                    'description' => 'Test Item',
                    'amount' => 1000.00
                ]
            ],
            'total_amount' => 1000.00,
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'submitted_at' => now(),
        ]);
    }

    private function createTestRequestForUser(User $user): GrantRequest
    {
        return GrantRequest::create([
            'user_id' => $user->id,
            'request_type_id' => $this->requestType->id,
            'ref_number' => 'REQ-' . time() . '-' . $user->id,
            'status_id' => RequestStatus::SUBMITTED->value,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'vot_items' => [
                [
                    'vot_code' => 'VOT11000',
                    'description' => 'Test Item',
                    'amount' => 1000.00
                ]
            ],
            'total_amount' => 1000.00,
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'submitted_at' => now(),
        ]);
    }
}
