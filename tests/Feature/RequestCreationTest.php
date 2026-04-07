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

class RequestCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admission_user_can_create_request(): void
    {
        $requestType = RequestType::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);
        VotCode::factory()->create(['code' => 'VOT11000', 'is_active' => true]);

        $requestData = [
            'request_type_id' => $requestType->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'vot_items' => [
                [
                    'vot_code' => 'VOT11000',
                    'description' => 'Test Item',
                    'amount' => 1000.00
                ]
            ],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ];

        $response = $this->actingAs($admission)
            ->post(route('requests.store'), $requestData);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'title' => 'Test Request',
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);
    }

    public function test_request_creation_requires_signature(): void
    {
        $requestType = RequestType::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);

        $requestData = [
            'request_type_id' => $requestType->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'signature_data' => null
        ];

        $response = $this->actingAs($admission)
            ->post(route('requests.store'), $requestData);

        $response->assertSessionHasErrors('signature_data');
    }

    public function test_request_creation_validates_vot_items(): void
    {
        $requestType = RequestType::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);

        $requestData = [
            'request_type_id' => $requestType->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'vot_items' => [
                [
                    'vot_code' => '',
                    'amount' => 'invalid'
                ]
            ],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ];

        $response = $this->actingAs($admission)
            ->post(route('requests.store'), $requestData);

        $response->assertSessionHasErrors(['vot_items.0.vot_code', 'vot_items.0.amount']);
    }

    public function test_staff1_cannot_create_request(): void
    {
        $requestType = RequestType::factory()->create();
        $staff1 = User::factory()->create(['role' => 'staff1']);

        $requestData = [
            'request_type_id' => $requestType->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ];

        $response = $this->actingAs($staff1)
            ->post(route('requests.store'), $requestData);

        $response->assertForbidden();
    }

    public function test_guest_cannot_create_request(): void
    {
        $requestType = RequestType::factory()->create();

        $requestData = [
            'request_type_id' => $requestType->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ];

        $response = $this->post(route('requests.store'), $requestData);

        $response->assertRedirect('/login');
    }
}
