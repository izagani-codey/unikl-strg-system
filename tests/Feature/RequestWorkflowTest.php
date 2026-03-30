<?php

namespace Tests\Feature;

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
            'status_id' => 1,
            'user_id' => $admission->id,
        ]);

        $grantRequest = GrantRequest::first();
        $this->assertNotNull($grantRequest->file_path);

        $response = $this->actingAs($staff1)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => 2,
            'notes' => 'Verified and forwarded to staff 2',
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(2, $grantRequest->status_id);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => 5,
            'notes' => 'Approved by staff 2',
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(5, $grantRequest->status_id);
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
            'status_id' => 1,
            'payload' => ['amount' => 0, 'description' => 'Test invalid transition', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => 4,
            'notes' => 'Invalid return',
            'rejection_reason' => 'Not ready yet',
        ]);

        $response->assertForbidden();
        $grantRequest->refresh();
        $this->assertSame(1, $grantRequest->status_id);
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
            'status_id' => 5,
            'payload' => ['amount' => 0, 'description' => 'Approved request', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => 6,
            'notes' => 'Reversing approval',
            'rejection_reason' => 'Override decline',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(6, $grantRequest->status_id);
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
            'status_id' => 6,
            'payload' => ['amount' => 0, 'description' => 'Declined request', 'email' => $admission->email],
            'file_path' => null,
        ]);

        $response = $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => 5,
            'notes' => 'Reversing decline',
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('requests.show', $grantRequest->id));
        $grantRequest->refresh();
        $this->assertSame(5, $grantRequest->status_id);
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
}
