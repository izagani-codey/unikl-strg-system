<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
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
        $this->withoutMiddleware();
    }

    public function test_admission_user_can_create_request(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $admission = User::factory()->create(['role' => 'admission']);
        VotCode::create(['code' => 'VOT11000', 'description' => 'Salary and wages', 'is_active' => true, 'sort_order' => 1]);

        $response = $this->actingAs($admission)->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'description' => 'Test Description',
            'vot_items' => [
                [
                    'vot_code' => 'VOT11000',
                    'description' => 'Test Item',
                    'amount' => 1000.00,
                ],
            ],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('requests', [
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);
    }

    public function test_staff1_cannot_create_request(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        VotCode::create(['code' => 'VOT11000', 'description' => 'Salary and wages', 'is_active' => true, 'sort_order' => 1]);

        $response = $this->actingAs($staff1)->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'description' => 'Test Description',
            'vot_items' => [
                [
                    'vot_code' => 'VOT11000',
                    'description' => 'Test Item',
                    'amount' => 1000.00,
                ],
            ],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_create_request(): void
    {
        $requestType = RequestType::create(['name' => 'General', 'slug' => 'general']);
        VotCode::create(['code' => 'VOT11000', 'description' => 'Salary and wages', 'is_active' => true, 'sort_order' => 1]);

        $response = $this->post(route('requests.store'), [
            'request_type_id' => $requestType->id,
            'description' => 'Test Description',
            'vot_items' => [
                [
                    'vot_code' => 'VOT11000',
                    'description' => 'Test Item',
                    'amount' => 1000.00,
                ],
            ],
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ]);

        $response->assertForbidden();
    }
}
