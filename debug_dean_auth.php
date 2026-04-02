<?php

// Debug Dean Authorization Issue
echo "=== DEAN AUTHORIZATION DEBUG ===\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "1. Checking Dean User...\n";
$dean = \App\Models\User::where('role', 'dean')->first();
if (!$dean) {
    echo "❌ No dean user found!\n";
    exit;
}
echo "✅ Dean user found: {$dean->name} ({$dean->email})\n";

echo "\n2. Checking Requests...\n";
$requests = \App\Models\Request::all();
if ($requests->isEmpty()) {
    echo "❌ No requests found!\n";
    exit;
}

foreach ($requests as $request) {
    echo "\n--- Request ID: {$request->id} ---\n";
    echo "Reference: {$request->reference_number}\n";
    echo "Current Status: {$request->status_id} - " . \App\Enums\RequestStatus::from($request->status_id)->getLabel() . "\n";
    
    // Test dean view permission
    $viewPolicy = new \App\Policies\RequestPolicy();
    $canView = $viewPolicy->view($dean, $request);
    echo "Dean can view: " . ($canView ? 'YES' : 'NO') . "\n";
    
    // Test dean change status permission
    try {
        $canChangeStatus = $viewPolicy->changeStatus($dean, $request);
        echo "Dean can change status: " . ($canChangeStatus ? 'YES' : 'NO') . "\n";
    } catch (\Illuminate\Auth\Access\Response $response) {
        echo "Dean can change status: NO - " . $response->message() . "\n";
    }
    
    // Check if dean can action this status
    $currentStatus = \App\Enums\RequestStatus::from($request->status_id);
    $deanCanAction = $currentStatus->canBeActionedByDean();
    echo "Dean can action this status: " . ($deanCanAction ? 'YES' : 'NO') . "\n";
    
    // Show what statuses dean can action
    if (!$deanCanAction) {
        echo "Dean can only action: PENDING_DEAN_APPROVAL (status 3)\n";
        echo "This request is in: " . $currentStatus->getLabel() . " (status {$request->status_id})\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
