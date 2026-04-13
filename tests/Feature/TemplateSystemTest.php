<?php

namespace Tests\Feature;

use App\Models\FormTemplate;
use App\Models\RequestType;
use App\Models\TemplateUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // Template Creation Tests
    public function test_admin_can_upload_template(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $templateData = [
            'name' => 'Test Template',
            'description' => 'Test template description',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf'),
        ];

        $response = $this->actingAs($admin)->post('/form-templates', $templateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('form_templates', [
            'name' => 'Test Template',
            'description' => 'Test template description',
            'request_type_id' => $requestType->id,
            'is_active' => true,
        ]);

        // Check file was stored
        $template = FormTemplate::first();
        $this->assertNotNull($template->file_path);
        Storage::disk('public')->assertExists($template->file_path);
    }

    public function test_staff2_can_upload_template(): void
    {
        $requestType = RequestType::factory()->create();
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $templateData = [
            'name' => 'Staff 2 Template',
            'description' => 'Template uploaded by staff 2',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf'),
        ];

        $response = $this->actingAs($staff2)->post('/form-templates', $templateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('form_templates', [
            'name' => 'Staff 2 Template',
            'description' => 'Template uploaded by staff 2',
            'request_type_id' => $requestType->id,
        ]);
    }

    public function test_other_roles_cannot_upload_templates(): void
    {
        $requestType = RequestType::factory()->create();
        $roles = ['admission', 'staff1', 'dean'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $templateData = [
                'name' => 'Unauthorized Template',
                'description' => 'Should not be allowed',
                'request_type_id' => $requestType->id,
                'file' => UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf'),
            ];

            $response = $this->actingAs($user)->post('/form-templates', $templateData);

            $response->assertForbidden();
        }

        $this->assertDatabaseCount('form_templates', 0);
    }

    // Template Validation Tests
    public function test_template_upload_requires_name(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $templateData = [
            'description' => 'Template without name',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf'),
        ];

        $response = $this->actingAs($admin)->post('/form-templates', $templateData);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('form_templates', 0);
    }

    public function test_template_upload_requires_file(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $templateData = [
            'name' => 'Template without file',
            'description' => 'Should fail',
            'request_type_id' => $requestType->id,
        ];

        $response = $this->actingAs($admin)->post('/form-templates', $templateData);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('form_templates', 0);
    }

    public function test_template_file_must_be_pdf(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $templateData = [
            'name' => 'Invalid File Template',
            'description' => 'Should fail with non-PDF',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.txt', 1000, 'text/plain'),
        ];

        $response = $this->actingAs($admin)->post('/form-templates', $templateData);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('form_templates', 0);
    }

    public function test_template_file_size_limit(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        // Create a file larger than 10MB
        $templateData = [
            'name' => 'Large File Template',
            'description' => 'Should fail due to size',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.pdf', 15000, 'application/pdf'), // 15MB
        ];

        $response = $this->actingAs($admin)->post('/form-templates', $templateData);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('form_templates', 0);
    }

    // Template Display Tests
    public function test_users_can_view_template_list(): void
    {
        // Create templates
        FormTemplate::factory()->count(3)->create(['is_active' => true]);
        FormTemplate::factory()->create(['is_active' => false]);

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get('/form-templates');

        $response->assertOk();
        $response->assertViewIs('form-templates.index');
        $response->assertViewHas('templates');
        
        // Should only show active templates
        $this->assertEquals(3, $response->viewData('templates')->count());
    }

    public function test_templates_are_filtered_by_request_type(): void
    {
        $requestType1 = RequestType::factory()->create();
        $requestType2 = RequestType::factory()->create();

        FormTemplate::factory()->create(['request_type_id' => $requestType1->id, 'is_active' => true]);
        FormTemplate::factory()->create(['request_type_id' => $requestType2->id, 'is_active' => true]);

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get("/form-templates?request_type={$requestType1->id}");

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('templates')->count());
    }

    // Template Download Tests
    public function test_users_can_download_templates(): void
    {
        $template = FormTemplate::factory()->create([
            'file_path' => 'templates/test-template.pdf',
        ]);

        // Create fake file
        Storage::disk('public')->put('templates/test-template.pdf', 'fake pdf content');

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get("/form-templates/{$template->id}/download");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $template->file_name . '"');
    }

    public function test_download_fails_for_nonexistent_file(): void
    {
        $template = FormTemplate::factory()->create([
            'file_path' => 'templates/nonexistent.pdf',
        ]);

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get("/form-templates/{$template->id}/download");

        $response->assertNotFound();
    }

    // Template Management Tests
    public function test_admin_can_delete_template(): void
    {
        $template = FormTemplate::factory()->create([
            'file_path' => 'templates/test-template.pdf',
        ]);

        // Create fake file
        Storage::disk('public')->put('templates/test-template.pdf', 'fake pdf content');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete("/form-templates/{$template->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('form_templates', ['id' => $template->id]);
        Storage::disk('public')->assertMissing('templates/test-template.pdf');
    }

    public function test_staff2_can_delete_template(): void
    {
        $template = FormTemplate::factory()->create([
            'file_path' => 'templates/test-template.pdf',
        ]);

        // Create fake file
        Storage::disk('public')->put('templates/test-template.pdf', 'fake pdf content');

        $staff2 = User::factory()->create(['role' => 'staff2']);

        $response = $this->actingAs($staff2)->delete("/form-templates/{$template->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('form_templates', ['id' => $template->id]);
    }

    public function test_other_roles_cannot_delete_templates(): void
    {
        $template = FormTemplate::factory()->create();
        $roles = ['admission', 'staff1', 'dean'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->delete("/form-templates/{$template->id}");

            $response->assertForbidden();
        }

        $this->assertDatabaseHas('form_templates', ['id' => $template->id]);
    }

    // Template Usage Tracking Tests
    public function test_template_usage_is_tracked(): void
    {
        $template = FormTemplate::factory()->create();
        $user = User::factory()->create(['role' => 'admission']);

        // Simulate template download
        $this->actingAs($user)->get("/form-templates/{$template->id}/download");

        // Check if usage was tracked
        $this->assertDatabaseHas('template_usage', [
            'template_id' => $template->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_template_usage_statistics(): void
    {
        $template = FormTemplate::factory()->create();
        $users = User::factory()->count(3)->create(['role' => 'admission']);

        // Simulate multiple downloads
        foreach ($users as $user) {
            $this->actingAs($user)->get("/form-templates/{$template->id}/download");
        }

        $template->refresh();
        $this->assertEquals(3, $template->download_count);
    }

    // Template Status Tests
    public function test_admin_can_activate_deactivate_template(): void
    {
        $template = FormTemplate::factory()->create(['is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);

        // Deactivate template
        $response = $this->actingAs($admin)->patch("/form-templates/{$template->id}/deactivate");
        $response->assertRedirect();

        $template->refresh();
        $this->assertFalse($template->is_active);

        // Activate template
        $response = $this->actingAs($admin)->patch("/form-templates/{$template->id}/activate");
        $response->assertRedirect();

        $template->refresh();
        $this->assertTrue($template->is_active);
    }

    public function test_inactive_templates_not_shown_to_users(): void
    {
        $activeTemplate = FormTemplate::factory()->create(['is_active' => true]);
        $inactiveTemplate = FormTemplate::factory()->create(['is_active' => false]);

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get('/form-templates');

        $response->assertOk();
        $response->assertSee($activeTemplate->name);
        $response->assertDontSee($inactiveTemplate->name);
    }

    // Template Search Tests
    public function test_templates_can_be_searched(): void
    {
        FormTemplate::factory()->create(['name' => 'Travel Grant Template']);
        FormTemplate::factory()->create(['name' => 'Research Grant Template']);
        FormTemplate::factory()->create(['name' => 'Equipment Request Template']);

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get('/form-templates?search=Travel');

        $response->assertOk();
        $response->assertSee('Travel Grant Template');
        $response->assertDontSee('Research Grant Template');
        $response->assertDontSee('Equipment Request Template');
    }

    // Template Sorting Tests
    public function test_templates_can_be_sorted(): void
    {
        $oldTemplate = FormTemplate::factory()->create([
            'name' => 'Old Template',
            'created_at' => now()->subDays(30),
        ]);

        $newTemplate = FormTemplate::factory()->create([
            'name' => 'New Template',
            'created_at' => now()->subDays(1),
        ]);

        $user = User::factory()->create(['role' => 'admission']);

        // Test sorting by newest first
        $response = $this->actingAs($user)->get('/form-templates?sort=created_at&order=desc');
        
        $templates = $response->viewData('templates');
        $this->assertEquals('New Template', $templates->first()->name);
        $this->assertEquals('Old Template', $templates->last()->name);
    }

    // Template Pagination Tests
    public function test_templates_are_paginated(): void
    {
        FormTemplate::factory()->count(25)->create(['is_active' => true]);

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get('/form-templates');

        $response->assertOk();
        $response->assertViewHas('templates');
        
        // Should have pagination links
        $response->assertSee('Next');
        $response->assertSee('Previous');
    }

    // Template Security Tests
    public function test_template_file_is_securely_stored(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $templateData = [
            'name' => 'Secure Template',
            'description' => 'Test secure storage',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf'),
        ];

        $this->actingAs($admin)->post('/form-templates', $templateData);

        $template = FormTemplate::first();
        
        // Check file is stored with secure path
        $this->assertStringContainsString('templates/', $template->file_path);
        $this->assertStringContainsString('.pdf', $template->file_path);
        
        // Check file exists in storage
        Storage::disk('public')->assertExists($template->file_path);
    }

    public function test_template_file_name_is_sanitized(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $templateData = [
            'name' => 'Template with spaces & symbols!',
            'description' => 'Test name sanitization',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf'),
        ];

        $this->actingAs($admin)->post('/form-templates', $templateData);

        $template = FormTemplate::first();
        
        // Check file name is sanitized
        $this->assertStringNotContainsString(' ', $template->file_name);
        $this->assertStringNotContainsString('&', $template->file_name);
        $this->assertStringNotContainsString('!', $template->file_name);
    }

    // Template Performance Tests
    public function test_template_index_loads_efficiently(): void
    {
        // Create many templates
        FormTemplate::factory()->count(100)->create(['is_active' => true]);

        $user = User::factory()->create(['role' => 'admission']);

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/form-templates');

        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;

        $response->assertOk();
        
        // Should load in under 1 second even with 100 templates
        $this->assertLessThan(1.0, $loadTime);
    }

    // Template Integration Tests
    public function test_template_integration_with_request_types(): void
    {
        $requestType = RequestType::factory()->create(['name' => 'Travel Grant']);
        $template = FormTemplate::factory()->create([
            'request_type_id' => $requestType->id,
            'name' => 'Travel Grant Form',
        ]);

        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get('/form-templates');

        $response->assertOk();
        $response->assertSee('Travel Grant Form');
        $response->assertSee('Travel Grant');
    }

    // Template Analytics Tests
    public function test_template_download_analytics(): void
    {
        $template = FormTemplate::factory()->create();
        $users = User::factory()->count(5)->create(['role' => 'admission']);

        // Simulate downloads from different users
        foreach ($users as $user) {
            $this->actingAs($user)->get("/form-templates/{$template->id}/download");
        }

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/templates/analytics');

        $response->assertOk();
        $response->assertViewHas('templates');
        
        $templates = $response->viewData('templates');
        $analyticsTemplate = $templates->first();
        $this->assertEquals(5, $analyticsTemplate->download_count);
    }
}
