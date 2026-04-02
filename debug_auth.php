<?php

// Simple test to check what's happening
echo "Testing basic authorization...\n";

// Check if policy exists
if (class_exists('App\\Policies\\RequestPolicy')) {
    echo "✅ RequestPolicy class exists\n";
} else {
    echo "❌ RequestPolicy class NOT found\n";
}

// Check if model exists
if (class_exists('App\\Models\\Request')) {
    echo "✅ Request model exists\n";
} else {
    echo "❌ Request model NOT found\n";
}

// Check users
$users = \App\Models\User::count();
echo "✅ Users in database: $users\n";

// Check requests
$requests = \App\Models\Request::count();
echo "✅ Requests in database: $requests\n";
