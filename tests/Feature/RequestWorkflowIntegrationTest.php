<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\Signature;
use App\Models\User;
use App\Models\VotCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected RequestType $requestType;
    protected User $admission;
    protected User $staff1;
    protected User $staff2;
    protected User $dean;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $this->requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        VotCode::create(['code' => 'VOT11000', 'description' => 'Salary and wages', 'is_active' => true, 'sort_order' => 1]);

        $this->admission = User::factory()->create(['role' => 'admission']);
        $this->staff1 = User::factory()->create(['role' => 'staff1']);
        $this->staff2 = User::factory()->create(['role' => 'staff2']);
        $this->dean = User::factory()->create(['role' => 'dean']);
    }

    public function test_complete_workflow_from_submission_to_dean_approval(): void
    {
        $request = $this->createWorkflowRequest();

        $this->actingAs($this->staff1)->patch(route('requests.updateStatus', $request->id), [
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
            'notes' => 'Verified by Staff 1',
        ])->assertRedirect(route('requests.show', $request->id));

        $request->refresh();
        $this->assertEquals(RequestStatus::STAFF1_APPROVED->value, $request->status_id);
        $this->assertEquals($this->staff1->id, $request->verified_by);

        $staff2Signature = 'data:image/png;base64,AAAA';
        $this->actingAs($this->staff2)->patch(route('requests.updateStatus', $request->id), [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Recommended by Staff 2',
            'staff2_signature_data' => $staff2Signature,
        ])->assertRedirect(route('requests.show', $request->id));

        $request->refresh();
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->status_id);
        $this->assertEquals($this->staff2->id, $request->recommended_by);
        $this->assertNotEmpty($request->staff2_signature_data);
        $this->assertDatabaseHas('signatures', [
            'request_id' => $request->id,
            'role' => 'staff2',
            'user_id' => $this->staff2->id,
        ]);

        $deanSignature = 'data:image/png;base64,BBBB';
        $this->actingAs($this->dean)->patch(route('requests.updateStatus', $request->id), [
            'status_id' => RequestStatus::DEAN_APPROVED->value,
            'notes' => 'Approved by Dean',
            'dean_signature_data' => $deanSignature,
        ])->assertRedirect(route('requests.show', $request->id));

        $request->refresh();
        $this->assertEquals(RequestStatus::DEAN_APPROVED->value, $request->status_id);
        $this->assertEquals($this->dean->id, $request->dean_approved_by);
        $this->assertNotEmpty($request->dean_signature_data);
        $this->assertDatabaseHas('signatures', [
            'request_id' => $request->id,
            'role' => 'dean',
            'user_id' => $this->dean->id,
        ]);
    }

    public function test_staff2_can_override_staff1_and_mark_override(): void
    {
        $request = $this->createWorkflowRequest();

        $this->actingAs($this->staff2)->patch(route('requests.updateStatus', $request->id), [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override due to urgency',
            'staff2_signature_data' => 'data:image/png;base64,CCCC',
        ])->assertRedirect(route('requests.show', $request->id));

        $request->refresh();
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->status_id);
        $this->assertTrue((bool) $request->is_override);
        $this->assertEquals($this->staff2->id, $request->recommended_by);
    }

    public function test_role_based_access_control(): void
    {
        $request = $this->createWorkflowRequest();

        $this->actingAs($this->admission)
            ->get(route('requests.index'))
            ->assertSee($request->ref_number);

        $otherAdmission = User::factory()->create(['role' => 'admission']);
        $otherRequest = $this->createWorkflowRequestForUser($otherAdmission);

        $this->actingAs($this->admission)
            ->get(route('requests.index'))
            ->assertDontSee($otherRequest->ref_number);

        $this->actingAs($this->staff1)
            ->get(route('requests.index'))
            ->assertSee($request->ref_number);

        $this->actingAs($this->staff2)
            ->get(route('requests.index'))
            ->assertSee($request->ref_number);
    }

    public function test_applicant_signature_written_to_normalized_signatures_table(): void
    {
        $this->actingAs($this->admission)->post(route('requests.store'), [
            'request_type_id' => $this->requestType->id,
            'description' => 'Applicant signature persistence',
            'vot_items' => [
                ['vot_code' => 'VOT11000', 'description' => 'Salary and wages', 'amount' => 150.00],
            ],
            'signature_data' => 'data:image/png;base64,AAAA',
            'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect(route('dashboard'));

        $request = GrantRequest::query()->latest('id')->firstOrFail();

        $signature = Signature::query()
            ->where('request_id', $request->id)
            ->where('role', 'applicant')
            ->first();

        $this->assertNotNull($signature);
        $this->assertEquals($this->admission->id, $signature->user_id);
        $this->assertNotEmpty($signature->signature_path);
        $this->assertNotNull($signature->signed_at);
    }

    public function test_staff1_can_open_main_uploaded_document(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('documents/test-file.pdf', 'dummy-pdf-content');

        $request = $this->createWorkflowRequest();
        $request->update(['file_path' => 'documents/test-file.pdf']);

        $this->actingAs($this->staff1)
            ->get(route('requests.document.main', $request->id))
            ->assertOk();
    }

    protected function createWorkflowRequest(): GrantRequest
    {
        return $this->createWorkflowRequestForUser($this->admission);
    }

    protected function createWorkflowRequestForUser(User $user): GrantRequest
    {
        return GrantRequest::create([
            'user_id' => $user->id,
            'request_type_id' => $this->requestType->id,
            'ref_number' => 'REQ-' . now()->format('YmdHis') . '-' . $user->id,
            'status_id' => RequestStatus::SUBMITTED->value,
            'payload' => [
                'description' => 'Test Description',
                'email' => $user->email,
                'dynamic_fields' => [],
            ],
            'vot_items' => [
                [
                    'vot_code' => 'VOT11000',
                    'description' => 'Test Item',
                    'amount' => 1000.00,
                ],
            ],
            'total_amount' => 1000.00,
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'signed_at' => now(),
            'submitted_at' => now(),
            'submitter_staff_id' => $user->staff_id,
            'submitter_designation' => $user->designation,
            'submitter_department' => $user->department,
            'submitter_phone' => $user->phone,
            'submitter_employee_level' => $user->employee_level,
        ]);
    }
}
