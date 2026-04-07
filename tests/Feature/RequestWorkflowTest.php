<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\VotCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_submission_and_staff1_staff2_workflow(): void
    {
        Storage::fake('public');

        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        VotCode::create(['code' => 'VOT11000', 'description' => 'Salary and wages', 'is_active' => true, 'sort_order' => 1]);

        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $response = $this->actingAs($admission)->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'description' => 'Test request workflow',
            'vot_items' => [
                ['vot_code' => 'VOT11000', 'description' => 'Salary and wages', 'amount' => 150.00],
            ],
            'signature_data' => 'data:image/png;base64,AAAA',
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'deadline' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('requests', [
            'request_type_id' => $requestType->id,
            'status_id' => RequestStatus::SUBMITTED->value,
            'user_id' => $admission->id,
        ]);

        $grantRequest = GrantRequest::first();
        $this->assertNotNull($grantRequest->file_path);

        $response = $this->actingAs($staff1)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
            'notes' => 'Verified and forwarded to staff 2',
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(RequestStatus::STAFF1_APPROVED->value, $grantRequest->status_id);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Staff 2 direct approval',
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(RequestStatus::PENDING_DEAN_APPROVAL->value, $grantRequest->status_id);
    }

    public function test_invalid_status_transition_is_blocked(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $admission = User::factory()->create(['role' => 'admission']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $grantRequest = GrantRequest::create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'ref_number' => 'REQ-INVALID',
            'status_id' => RequestStatus::SUBMITTED->value,
            'payload' => ['amount' => 0, 'description' => 'Test invalid transition', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::RETURNED->value,
            'notes' => 'Invalid return',
            'rejection_reason' => 'Not ready yet',
        ]);

        $response->assertForbidden();
        $grantRequest->refresh();
        $this->assertSame(RequestStatus::SUBMITTED->value, $grantRequest->status_id);
    }

    public function test_staff2_can_override_approved_request_to_declined(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $admission = User::factory()->create(['role' => 'admission']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $grantRequest = GrantRequest::create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'ref_number' => 'REQ-APPROVED',
            'status_id' => RequestStatus::DEAN_APPROVED->value,
            'payload' => ['amount' => 0, 'description' => 'Approved request', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $response = $this->actingAs($staff2)->get(route('requests.override', $grantRequest->id));

        $response->assertForbidden();
    }

    public function test_staff2_can_override_declined_request_to_approved(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $admission = User::factory()->create(['role' => 'admission']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $grantRequest = GrantRequest::create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'ref_number' => 'REQ-DECLINED',
            'status_id' => RequestStatus::REJECTED->value,
            'payload' => ['amount' => 0, 'description' => 'Declined request', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $staff2->enableOverride();
        $response = $this->actingAs($staff2)->post(route('requests.override', $grantRequest->id), [
            'action_type' => 'reject_reverse',
            'reason' => 'Staff 2 override because Staff 1 is unavailable',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(RequestStatus::SUBMITTED->value, $grantRequest->status_id);
    }

    public function test_file_upload_validation_rejects_invalid_document_type(): void
    {
        Storage::fake('public');

        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        VotCode::create(['code' => 'VOT11000', 'description' => 'Salary and wages', 'is_active' => true, 'sort_order' => 1]);
        $admission = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($admission)->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'description' => 'Disallowed file test',
            'vot_items' => [
                ['vot_code' => 'VOT11000', 'description' => 'Salary and wages', 'amount' => 200],
            ],
            'signature_data' => 'data:image/png;base64,AAAA',
            'document' => UploadedFile::fake()->create('document.txt', 50, 'text/plain'),
            'deadline' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertSessionHasErrors('document');
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_request_status_enum_methods(): void
    {
        // Test enum methods
        $pending = RequestStatus::SUBMITTED;
        $approved = RequestStatus::DEAN_APPROVED;
        $rejected = RequestStatus::REJECTED;

        $this->assertEquals('Submitted', $pending->getLabel());
        $this->assertEquals('Dean Approved', $approved->getLabel());
        $this->assertEquals('Rejected', $rejected->getLabel());

        $this->assertTrue($approved->isFinal());
        $this->assertTrue($rejected->isFinal());
        $this->assertFalse($pending->isFinal());

        $this->assertFalse($pending->canBeEditedByAdmission());
        $this->assertFalse($approved->canBeEditedByAdmission());

        $this->assertTrue($pending->canBeActionedByStaff1());
        $this->assertFalse($pending->canBeActionedByStaff2());
    }
}
