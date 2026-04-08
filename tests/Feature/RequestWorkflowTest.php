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

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_admission_submission_and_staff1_staff2_workflow(): void
    {
        Storage::fake('public');

        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        VotCode::create(['code' => 'VOT11000', 'description' => 'Salary and wages', 'is_active' => true, 'sort_order' => 1]);

        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $this->actingAs($admission)->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'description' => 'Test request workflow',
            'vot_items' => [
                ['vot_code' => 'VOT11000', 'description' => 'Salary and wages', 'amount' => 150.00],
            ],
            'signature_data' => 'data:image/png;base64,AAAA',
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect(route('dashboard'));

        $grantRequest = GrantRequest::firstOrFail();
        $this->assertSame(RequestStatus::SUBMITTED->value, $grantRequest->status_id);
        $this->assertNotNull($grantRequest->file_path);

        $this->actingAs($staff1)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::STAFF1_APPROVED->value,
            'notes' => 'Verified and forwarded to staff 2',
        ])->assertRedirect(route('requests.show', $grantRequest->id));

        $grantRequest->refresh();
        $this->assertSame(RequestStatus::STAFF1_APPROVED->value, $grantRequest->status_id);

        $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Staff 2 direct approval',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ])->assertRedirect(route('requests.show', $grantRequest->id));

        $grantRequest->refresh();
        $this->assertSame(RequestStatus::STAFF2_APPROVED->value, $grantRequest->status_id);
    }

    public function test_dean_cannot_action_before_staff2_approval(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $admission = User::factory()->create(['role' => 'admission']);
        $dean = User::factory()->create(['role' => 'dean']);

        $grantRequest = GrantRequest::create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'ref_number' => 'REQ-DEAN-BLOCK',
            'status_id' => RequestStatus::SUBMITTED->value,
            'payload' => ['description' => 'Dean blocked test', 'email' => $admission->email],
            'vot_items' => [['vot_code' => 'VOT11000', 'description' => 'Item', 'amount' => 10]],
            'total_amount' => 10,
            'signature_data' => 'data:image/png;base64,AAAA',
            'signed_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($dean)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::DEAN_APPROVED->value,
            'dean_signature_data' => 'data:image/png;base64,CCCC',
        ])->assertForbidden();

        $grantRequest->refresh();
        $this->assertSame(RequestStatus::SUBMITTED->value, $grantRequest->status_id);
    }

    public function test_staff2_override_path_sets_override_flag(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $admission = User::factory()->create(['role' => 'admission']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $grantRequest = GrantRequest::create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'ref_number' => 'REQ-OVERRIDE',
            'status_id' => RequestStatus::SUBMITTED->value,
            'payload' => ['description' => 'Override test', 'email' => $admission->email],
            'vot_items' => [['vot_code' => 'VOT11000', 'description' => 'Item', 'amount' => 10]],
            'total_amount' => 10,
            'signature_data' => 'data:image/png;base64,AAAA',
            'signed_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($staff2)->patch(route('requests.updateStatus', $grantRequest->id), [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override because staff1 unavailable',
            'staff2_signature_data' => 'data:image/png;base64,DDDD',
        ])->assertRedirect(route('requests.show', $grantRequest->id));

        $grantRequest->refresh();
        $this->assertSame(RequestStatus::STAFF2_APPROVED->value, $grantRequest->status_id);
        $this->assertTrue((bool) $grantRequest->is_override);
    }

    public function test_request_status_enum_methods(): void
    {
        $submitted = RequestStatus::SUBMITTED;
        $staff1Approved = RequestStatus::STAFF1_APPROVED;
        $approved = RequestStatus::DEAN_APPROVED;
        $rejected = RequestStatus::REJECTED;

        $this->assertEquals('Submitted', $submitted->getLabel());
        $this->assertEquals('Staff 1 Approved', $staff1Approved->getLabel());
        $this->assertEquals('Dean Approved', $approved->getLabel());
        $this->assertEquals('Rejected', $rejected->getLabel());

        $this->assertTrue($approved->isFinal());
        $this->assertTrue($rejected->isFinal());
        $this->assertFalse($submitted->isFinal());

        $this->assertFalse($submitted->canBeEditedByAdmission());
        $this->assertFalse($approved->canBeEditedByAdmission());

        $this->assertTrue($submitted->canBeActionedByStaff1());
        $this->assertTrue($submitted->canBeActionedByStaff2());
        $this->assertFalse($submitted->canBeActionedByDean());
    }
}
