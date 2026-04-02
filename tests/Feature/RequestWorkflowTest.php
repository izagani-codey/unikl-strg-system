<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
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

        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $response = $this->actingAs($admission)->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'amount' => 150.00,
            'description' => 'Test request workflow',
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'deadline' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('requests', [
            'request_type_id' => $requestType->id,
            'status_id' => RequestStatus::PENDING_VERIFICATION->value,
            'user_id' => $admission->id,
        ]);

        $grantRequest = GrantRequest::first();
        $this->assertNotNull($grantRequest->file_path);

        $response = $this->actingAs($staff1)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::PENDING_RECOMMENDATION->value,
            'notes' => 'Verified and forwarded to staff 2',
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(RequestStatus::PENDING_RECOMMENDATION->value, $grantRequest->status_id);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::PENDING_DEAN_APPROVAL->value,
            'notes' => 'Recommended and sent to Dean',
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
            'status_id' => RequestStatus::PENDING_VERIFICATION->value,
            'payload' => ['amount' => 0, 'description' => 'Test invalid transition', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::RETURNED_TO_STAFF_1->value,
            'notes' => 'Invalid return',
            'rejection_reason' => 'Not ready yet',
        ]);

        $response->assertForbidden();
        $grantRequest->refresh();
        $this->assertSame(RequestStatus::PENDING_VERIFICATION->value, $grantRequest->status_id);
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
            'status_id' => RequestStatus::APPROVED->value,
            'payload' => ['amount' => 0, 'description' => 'Approved request', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $response = $this->actingAs($staff2)->post(route('requests.override', $grantRequest->id), [
            'action_type' => 'reject_reverse',
            'reason' => 'Override workflow recovery for approved record',
            'confirm_reinstate' => '1',
            'confirmation_phrase' => 'REINSTATE',
        ]);

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
            'status_id' => RequestStatus::DECLINED->value,
            'payload' => ['amount' => 0, 'description' => 'Declined request', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $staff2->enableOverride();
        $response = $this->actingAs($staff2)->post(route('requests.override', $grantRequest->id), [
            'action_type' => 'reject_reverse',
            'reason' => 'Staff 2 override because Staff 1 is unavailable',
            'confirm_reinstate' => '1',
            'confirmation_phrase' => 'REINSTATE',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(RequestStatus::PENDING_VERIFICATION->value, $grantRequest->status_id);
    }

    public function test_file_upload_validation_rejects_invalid_document_type(): void
    {
        Storage::fake('public');

        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $admission = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($admission)->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'amount' => 200,
            'description' => 'Disallowed file test',
            'document' => UploadedFile::fake()->create('document.txt', 50, 'text/plain'),
            'deadline' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertSessionHasErrors('document');
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_request_status_enum_methods(): void
    {
        // Test enum methods
        $pending = RequestStatus::PENDING_VERIFICATION;
        $approved = RequestStatus::APPROVED;
        $declined = RequestStatus::DECLINED;

        $this->assertEquals('Pending Verification', $pending->getLabel());
        $this->assertEquals('Approved', $approved->getLabel());
        $this->assertEquals('Declined', $declined->getLabel());

        $this->assertTrue($approved->isFinal());
        $this->assertTrue($declined->isFinal());
        $this->assertFalse($pending->isFinal());

        $this->assertFalse($pending->canBeEditedByAdmission());
        $this->assertFalse($approved->canBeEditedByAdmission());

        $this->assertTrue($pending->canBeActionedByStaff1());
        $this->assertFalse($pending->canBeActionedByStaff2());
    }
}
