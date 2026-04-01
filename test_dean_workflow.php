<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Request;
use App\Models\VotCode;
use App\Enums\RequestStatus;

echo "=== Testing Dean Workflow Implementation ===\n\n";

// 1. Check VOT Codes
echo "1. Checking VOT Codes:\n";
$votCodes = VotCode::active()->ordered()->get();
echo "   Found {$votCodes->count()} VOT codes\n";
foreach ($votCodes as $votCode) {
    echo "   - {$votCode->code}: {$votCode->description}\n";
}
echo "\n";

// 2. Check Users
echo "2. Checking Users:\n";
$users = User::all();
foreach ($users as $user) {
    echo "   - {$user->name} ({$user->role})\n";
}
echo "\n";

// 3. Check Dean User
echo "3. Checking Dean User:\n";
$dean = User::where('role', 'dean')->first();
if ($dean) {
    echo "   Dean found: {$dean->name}\n";
    echo "   Dean can approve: " . ($dean->isDean() ? 'YES' : 'NO') . "\n";
} else {
    echo "   No dean user found\n";
}
echo "\n";

// 4. Check Request Status Enum
echo "4. Checking Request Status Enum:\n";
foreach (RequestStatus::cases() as $status) {
    echo "   - {$status->value}: {$status->getLabel()}\n";
}
echo "\n";

// 5. Check if dean approval fields exist in requests table
echo "5. Checking Dean Approval Fields:\n";
try {
    $request = new Request();
    $fillable = $request->getFillable();
    $deanFields = ['dean_approved_by', 'dean_approved_at', 'dean_notes', 'dean_rejection_reason'];
    
    foreach ($deanFields as $field) {
        if (in_array($field, $fillable)) {
            echo "   ✓ $field exists in fillable\n";
        } else {
            echo "   ✗ $field missing from fillable\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Complete ===\n";
