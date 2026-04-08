<?php

/**
 * STRG System Test Script
 * 
 * This script helps verify that all the implemented features are working correctly.
 * Run this script to check the system status before manual testing.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== STRG System Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "   ✅ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 2: Request Types with Field Schema
echo "\n2. Testing Request Types with Field Schema...\n";
try {
    $requestTypes = \App\Models\RequestType::where('is_active', true)->get();
    echo "   Found {$requestTypes->count()} active request types\n";
    
    foreach ($requestTypes as $type) {
        $hasSchema = !empty($type->field_schema);
        echo "   - {$type->name}: " . ($hasSchema ? "✅ Has field schema" : "⚠️  No field schema") . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking request types: " . $e->getMessage() . "\n";
}

// Test 3: RequestTypeTemplate Relationship
echo "\n3. Testing Request Type Template System...\n";
try {
    $requestTypeTemplates = \App\Models\RequestTypeTemplate::count();
    echo "   Found {$requestTypeTemplates} request type template associations\n";
    
    $templatesWithTypes = \App\Models\FormTemplate::whereHas('requestTypes')->count();
    $generalTemplates = \App\Models\FormTemplate::whereDoesntHave('requestTypes')->count();
    
    echo "   - Templates with request types: {$templatesWithTypes}\n";
    echo "   - General templates: {$generalTemplates}\n";
} catch (Exception $e) {
    echo "   ❌ Error checking template system: " . $e->getMessage() . "\n";
}

// Test 4: User Profile Fields
echo "\n4. Testing User Profile Fields...\n";
try {
    $users = \App\Models\User::limit(5)->get();
    foreach ($users as $user) {
        $hasCompleteProfile = $user->hasCompleteProfile();
        echo "   - {$user->name} ({$user->role}): " . ($hasCompleteProfile ? "✅ Complete" : "⚠️  Incomplete") . "\n";
        echo "     Staff ID: " . ($user->staff_id ?: 'Not set') . "\n";
        echo "     Designation: " . ($user->designation ?: 'Not set') . "\n";
        echo "     Department: " . ($user->department ?: 'Not set') . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking user profiles: " . $e->getMessage() . "\n";
}

// Test 5: Form Template Storage
echo "\n5. Testing Form Template Storage...\n";
try {
    $templates = \App\Models\FormTemplate::where('is_active', true)->get();
    echo "   Found {$templates->count()} active templates\n";
    
    foreach ($templates as $template) {
        $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($template->file_path);
        echo "   - {$template->title}: " . ($fileExists ? "✅ File exists" : "❌ File missing") . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking template storage: " . $e->getMessage() . "\n";
}

// Test 6: Routes
echo "\n6. Testing Routes...\n";
$routes = [
    'profile.show' => '/profile',
    'profile.edit' => '/profile/edit',
    'form-templates.index' => '/form-templates',
    'dashboard' => '/dashboard',
];

foreach ($routes as $name => $path) {
    try {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($name);
        echo "   - {$name}: " . ($route ? "✅ Route exists" : "❌ Route missing") . "\n";
    } catch (Exception $e) {
        echo "   - {$name}: ❌ Error checking route\n";
    }
}

// Test 7: Request with Dynamic Fields
echo "\n7. Testing Requests with Dynamic Fields...\n";
try {
    $requests = \App\Models\Request::with(['requestType', 'user'])->limit(3)->get();
    foreach ($requests as $request) {
        $hasDynamicFields = !empty($request->payload['dynamic_fields']);
        echo "   - Request {$request->ref_number}: " . ($hasDynamicFields ? "✅ Has dynamic fields" : "⚠️  No dynamic fields") . "\n";
        echo "     Type: {$request->requestType->name}\n";
        echo "     User: {$request->user->name}\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking requests: " . $e->getMessage() . "\n";
}

// Test 8: Migration Status
echo "\n8. Testing Migration Status...\n";
try {
    $migrator = $app['migrator'];
    $ran = $migrator->getRepository()->getRan();
    
    $requiredMigrations = [
        '2026_04_08_020947_create_request_type_templates_table',
    ];
    
    foreach ($requiredMigrations as $migration) {
        $hasRun = in_array($migration, $ran);
        echo "   - {$migration}: " . ($hasRun ? "✅ Migrated" : "❌ Not migrated") . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking migrations: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests show ✅, the system is ready for manual testing.\n";
echo "If any tests show ❌ or ⚠️, address those issues before proceeding.\n\n";

echo "Next Steps:\n";
echo "1. Start the development server: php artisan serve\n";
echo "2. Follow the comprehensive testing guide\n";
echo "3. Test each feature systematically\n";
echo "4. Report any issues found\n\n";
