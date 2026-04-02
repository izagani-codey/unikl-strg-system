<?php

// Debug Current Request Status
echo "=== CURRENT REQUEST DEBUG ===\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "1. Checking all requests and their statuses...\n";
$requests = \App\Models\Request::all();

foreach ($requests as $request) {
    echo "\n--- Request ID: {$request->id} ---\n";
    echo "Reference: {$request->reference_number}\n";
    echo "Current Status: {$request->status_id} - " . \App\Enums\RequestStatus::from($request->status_id)->getLabel() . "\n";
    
    // Check if dean can see this request
    $dean = \App\Models\User::where('role', 'dean')->first();
    $policy = new \App\Policies\RequestPolicy();
    $canView = $policy->view($dean, $request);
    echo "Dean can view: " . ($canView ? 'YES' : 'NO') . "\n";
    
    // Check if dean buttons should show
    $showDeanButtons = ($request->status_id === \App\Enums\RequestStatus::PENDING_DEAN_APPROVAL->value);
    echo "Dean buttons should show: " . ($showDeanButtons ? 'YES' : 'NO') . "\n";
    
    if ($showDeanButtons) {
        echo "✅ This request should show dean buttons\n";
    } else {
        echo "❌ This request will NOT show dean buttons\n";
        echo "   Dean can only action PENDING_DEAN_APPROVAL (status 3)\n";
        echo "   This request is in status {$request->status_id}\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
