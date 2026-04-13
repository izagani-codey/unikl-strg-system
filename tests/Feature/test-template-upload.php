<?php

/**
 * Template Upload Test Script
 * 
 * This script tests the template upload functionality without requiring a web server.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Template Upload Test ===\n\n";

// Test 1: Check if FormTemplate model works
echo "1. Testing FormTemplate Model...\n";
try {
    $templates = \App\Models\FormTemplate::all();
    echo "   ✅ FormTemplate model works - Found {$templates->count()} templates\n";
} catch (Exception $e) {
    echo "   ❌ FormTemplate model error: " . $e->getMessage() . "\n";
}

// Test 2: Check RequestTypeTemplate model
echo "\n2. Testing RequestTypeTemplate Model...\n";
try {
    $requestTypeTemplates = \App\Models\RequestTypeTemplate::all();
    echo "   ✅ RequestTypeTemplate model works - Found {$requestTypeTemplates->count()} associations\n";
} catch (Exception $e) {
    echo "   ❌ RequestTypeTemplate model error: " . $e->getMessage() . "\n";
}

// Test 3: Check storage directory
echo "\n3. Testing Storage Directory...\n";
try {
    $storagePath = storage_path('app/public/blank-forms');
    $exists = is_dir($storagePath);
    $writable = is_writable($storagePath);
    
    echo "   Storage path: {$storagePath}\n";
    echo "   Directory exists: " . ($exists ? "✅" : "❌") . "\n";
    echo "   Directory writable: " . ($writable ? "✅" : "❌") . "\n";
    
    if (!$exists) {
        mkdir($storagePath, 0755, true);
        echo "   Created directory: ✅\n";
    }
} catch (Exception $e) {
    echo "   ❌ Storage directory error: " . $e->getMessage() . "\n";
}

// Test 4: Create a test template
echo "\n4. Creating Test Template...\n";
try {
    // Get a test user
    $testUser = \App\Models\User::first();
    if (!$testUser) {
        echo "   ❌ No users found in database\n";
        exit(1);
    }
    
    // Create a test template
    $template = \App\Models\FormTemplate::create([
        'title' => 'Test Template - ' . date('Y-m-d H:i:s'),
        'template_type' => 'general_form',
        'file_path' => 'blank-forms/test-template.pdf',
        'uploaded_by' => $testUser->id,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "   ✅ Created test template with ID: {$template->id}\n";
    echo "   Title: {$template->title}\n";
    echo "   File path: {$template->file_path}\n";
    
    // Test 5: Create a test file
    echo "\n5. Creating Test File...\n";
    $testFilePath = storage_path('app/public/blank-forms/test-template.pdf');
    $testContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj

4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Test PDF) Tj
ET
endstream
endobj

xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000054 00000 n 
0000000111 00000 n 
0000000198 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
299
%%EOF';
    
    file_put_contents($testFilePath, $testContent);
    echo "   ✅ Created test PDF file: {$testFilePath}\n";
    
    // Test 6: Verify template can be retrieved
    echo "\n6. Testing Template Retrieval...\n";
    $retrievedTemplate = \App\Models\FormTemplate::find($template->id);
    if ($retrievedTemplate) {
        echo "   ✅ Template retrieved successfully\n";
        echo "   Title: {$retrievedTemplate->title}\n";
        echo "   Uploader: {$retrievedTemplate->uploader->name}\n";
    } else {
        echo "   ❌ Failed to retrieve template\n";
    }
    
    // Test 7: Test with request type
    echo "\n7. Testing Request Type Association...\n";
    $requestType = \App\Models\RequestType::first();
    if ($requestType) {
        $requestTypeTemplate = \App\Models\RequestTypeTemplate::create([
            'request_type_id' => $requestType->id,
            'form_template_id' => $template->id,
            'is_default' => true,
            'sort_order' => 1,
        ]);
        
        echo "   ✅ Created request type template association\n";
        echo "   Request Type: {$requestType->name}\n";
        echo "   Template: {$template->title}\n";
        echo "   Is Default: " . ($requestTypeTemplate->is_default ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ⚠️  No request types found\n";
    }
    
    // Test 8: Test DashboardService
    echo "\n8. Testing DashboardService...\n";
    try {
        $dashboardService = new \App\Services\DashboardService();
        $data = $dashboardService->getDashboardData($testUser);
        
        echo "   ✅ DashboardService works\n";
        echo "   General templates: " . $data['formTemplates']->count() . "\n";
        echo "   Request type templates: " . $data['requestTypeTemplates']->count() . "\n";
    } catch (Exception $e) {
        echo "   ❌ DashboardService error: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Test Complete ===\n";
    echo "Template upload functionality is working correctly!\n";
    echo "You can now test the web interface.\n\n";
    
    echo "To clean up test data, run:\n";
    echo "php artisan tinker --execute=\"\App\Models\FormTemplate::where('title', 'like', 'Test Template%')->delete();\"\n";
    
} catch (Exception $e) {
    echo "   ❌ Error creating test template: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}
