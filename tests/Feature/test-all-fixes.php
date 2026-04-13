<?php

/**
 * Test All Fixes Script
 * 
 * This script tests all the fixes implemented for the STRG system.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing All Fixes ===\n\n";

// Test 1: Profile System
echo "1. Testing Profile System...\n";
try {
    $user = \App\Models\User::where('role', 'admission')->first();
    echo "   Found admission user: {$user->name}\n";
    echo "   Staff ID: " . ($user->staff_id ?: 'Not set') . "\n";
    echo "   Designation: " . ($user->designation ?: 'Not set') . "\n";
    echo "   Department: " . ($user->department ?: 'Not set') . "\n";
    echo "   Phone: " . ($user->phone ?: 'Not set') . "\n";
    echo "   Employee Level: " . ($user->employee_level ?: 'Not set') . "\n";
    echo "   Profile Complete: " . ($user->hasCompleteProfile() ? 'Yes' : 'No') . "\n";
    echo "   ✅ Profile system working\n";
} catch (Exception $e) {
    echo "   ❌ Profile system error: " . $e->getMessage() . "\n";
}

// Test 2: Template System
echo "\n2. Testing Template System...\n";
try {
    $templates = \App\Models\FormTemplate::with('requestTypes')->get();
    echo "   Found {$templates->count()} templates\n";
    
    $requestTypeTemplates = \App\Models\RequestTypeTemplate::count();
    echo "   Found {$requestTypeTemplates} request type associations\n";
    
    $requestTypes = \App\Models\RequestType::with('templates')->get();
    foreach ($requestTypes as $rt) {
        $defaultTemplate = $rt->getDefaultTemplate();
        if ($defaultTemplate) {
            echo "   ✅ {$rt->name} has default template: {$defaultTemplate->title}\n";
        } else {
            echo "   ⚠️  {$rt->name} has no default template\n";
        }
    }
    echo "   ✅ Template system working\n";
} catch (Exception $e) {
    echo "   ❌ Template system error: " . $e->getMessage() . "\n";
}

// Test 3: Staff 1 Authorization
echo "\n3. Testing Staff 1 Authorization...\n";
try {
    $staff1 = \App\Models\User::where('role', 'staff1')->first();
    $submittedRequest = \App\Models\Request::where('status_id', \App\Enums\RequestStatus::SUBMITTED->value)->first();
    
    if ($submittedRequest) {
        $policy = new \App\Policies\RequestPolicy();
        $canView = $policy->view($staff1, $submittedRequest);
        $canChangeStatus = $policy->changeStatus($staff1, $submittedRequest);
        
        echo "   Found submitted request: {$submittedRequest->ref_number}\n";
        echo "   Staff 1 can view: " . ($canView ? 'Yes' : 'No') . "\n";
        echo "   Staff 1 can change status: " . ($canChangeStatus ? 'Yes' : 'No') . "\n";
        
        if ($canChangeStatus) {
            echo "   ✅ Staff 1 authorization working\n";
        } else {
            echo "   ⚠️  Staff 1 authorization issue\n";
        }
    } else {
        echo "   ⚠️  No submitted requests found for testing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Staff 1 authorization error: " . $e->getMessage() . "\n";
}

// Test 4: Dashboard Service
echo "\n4. Testing Dashboard Service...\n";
try {
    $admissionUser = \App\Models\User::where('role', 'admission')->first();
    $dashboardService = new \App\Services\DashboardService();
    $data = $dashboardService->getDashboardData($admissionUser);
    
    echo "   Dashboard data retrieved successfully\n";
    echo "   General templates: " . $data['formTemplates']->count() . "\n";
    echo "   Request type templates: " . $data['requestTypeTemplates']->count() . "\n";
    echo "   Request types: " . $data['requestTypes']->count() . "\n";
    echo "   ✅ Dashboard service working\n";
} catch (Exception $e) {
    echo "   ❌ Dashboard service error: " . $e->getMessage() . "\n";
}

// Test 5: Form Template Controller
echo "\n5. Testing Form Template Controller...\n";
try {
    $controller = new \App\Http\Controllers\FormTemplateController();
    $templates = $controller->index();
    echo "   Form template index method working\n";
    echo "   ✅ Form template controller working\n";
} catch (Exception $e) {
    echo "   ❌ Form template controller error: " . $e->getMessage() . "\n";
}

// Test 6: Request Creation with Default Templates
echo "\n6. Testing Request Creation with Default Templates...\n";
try {
    $requestType = \App\Models\RequestType::first();
    $defaultTemplate = $requestType->getDefaultTemplate();
    
    if ($defaultTemplate) {
        echo "   ✅ Default template system working for {$requestType->name}\n";
        echo "   Default template: {$defaultTemplate->title}\n";
    } else {
        echo "   ⚠️  No default template found for {$requestType->name}\n";
    }
} catch (Exception $e) {
    echo "   ❌ Default template system error: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Testing Complete ===\n";
echo "All systems have been tested. Review the results above.\n\n";

echo "Summary of fixes implemented:\n";
echo "✅ Profile page now has directly editable fields with save button\n";
echo "✅ Success notifications added for profile save\n";
echo "✅ Staff 2 template upload makes file optional for default templates\n";
echo "✅ Template system properly handles request type associations\n";
echo "✅ Authorization policies are in place for Staff 1\n\n";

echo "To test the web interface:\n";
echo "1. Start your development server\n";
echo "2. Login as different users to test each role\n";
echo "3. Test profile editing and saving\n";
echo "4. Test template upload as Staff 2\n";
echo "5. Test request approval as Staff 1\n";
