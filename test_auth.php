<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$user = \App\Models\User::where('role', 'admission')->first();
$request = \App\Models\Request::first();

echo "User: {$user->name} ({$user->role})\n";
echo "Request: {$request->reference_number}\n";

// Test policy directly
$policy = new \App\Policies\RequestPolicy();
echo "Policy view result: " . ($policy->view($user, $request) ? 'YES' : 'NO') . "\n";

// Test Gate
$gate = new \Illuminate\Auth\Access\Gate(app());
$gate->policy(\App\Models\Request::class, $policy);

echo "Gate can view: " . ($gate->forUser($user)->can('view', $request) ? 'YES' : 'NO') . "\n";
