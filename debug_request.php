<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Checking request statuses...\n";

$requests = \App\Models\Request::all();

foreach ($requests as $request) {
    echo "Request ID: {$request->id}\n";
    echo "Reference: {$request->reference_number}\n";
    echo "Status ID: {$request->status_id}\n";
    echo "Status Label: " . \App\Enums\RequestStatus::from($request->status_id)->getLabel() . "\n";
    echo "Can Dean Action: " . (\App\Enums\RequestStatus::from($request->status_id)->canBeActionedByDean() ? 'YES' : 'NO') . "\n";
    echo "---\n";
}
