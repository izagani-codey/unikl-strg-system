<?php

// Debug User Session and Request Access
echo "=== SESSION DEBUG ===\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate authentication
echo "1. Checking available users...\n";
$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "- {$user->name} ({$user->role}) - {$user->email}\n";
}

echo "\n2. Testing dean access to request...\n";
$dean = \App\Models\User::where('role', 'dean')->first();
$request = \App\Models\Request::find(1);

if (!$dean) {
    echo "❌ No dean user found!\n";
    exit;
}

if (!$request) {
    echo "❌ No request found!\n";
    exit;
}

echo "Dean: {$dean->name}\n";
echo "Request: {$request->reference_number} (Status: {$request->status_id})\n";

// Test all policy methods
$policy = new \App\Policies\RequestPolicy();

echo "\n3. Testing all dean permissions...\n";
echo "Can view: " . ($policy->view($dean, $request) ? 'YES' : 'NO') . "\n";
echo "Can viewAny: " . ($policy->viewAny($dean) ? 'YES' : 'NO') . "\n";
echo "Can create: " . ($policy->create($dean) ? 'YES' : 'NO') . "\n";
echo "Can update: " . ($policy->update($dean, $request) ? 'YES' : 'NO') . "\n";
echo "Can delete: " . ($policy->delete($dean, $request) ? 'YES' : 'NO') . "\n";

try {
    $canChangeStatus = $policy->changeStatus($dean, $request);
    echo "Can changeStatus: " . ($canChangeStatus ? 'YES' : 'NO') . "\n";
} catch (\Illuminate\Auth\Access\Response $response) {
    echo "Can changeStatus: NO - " . $response->message() . "\n";
}

echo "Can addComment: " . ($policy->addComment($dean, $request) ? 'YES' : 'NO') . "\n";
echo "Can print: " . ($policy->print($dean, $request) ? 'YES' : 'NO') . "\n";
echo "Can revise: " . ($policy->revise($dean, $request) ? 'YES' : 'NO') . "\n";
echo "Can override: " . ($policy->override($dean, $request) ? 'YES' : 'NO') . "\n";

echo "\n4. Testing view condition...\n";
$showDeanButtons = ($request->status_id === \App\Enums\RequestStatus::PENDING_DEAN_APPROVAL->value);
echo "Request status: {$request->status_id}\n";
echo "Expected status: " . \App\Enums\RequestStatus::PENDING_DEAN_APPROVAL->value . "\n";
echo "Status matches: " . ($showDeanButtons ? 'YES' : 'NO') . "\n";

echo "\n=== SESSION DEBUG COMPLETE ===\n";
