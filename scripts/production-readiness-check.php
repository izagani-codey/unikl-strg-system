<?php

declare(strict_types=1);

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$vendorAutoloadPath = $basePath . '/vendor/autoload.php';
$databaseFile = $basePath . '/database/database.sqlite';
$storagePath = $basePath . '/storage';

if (file_exists($vendorAutoloadPath)) {
    require $vendorAutoloadPath;
    require_once $basePath . '/bootstrap/app.php';
}

echo "🔍 UNIKL STRG PRODUCTION READINESS CHECK\n";
echo "==========================================\n\n";

$checks = [];
$errors = [];
$warnings = [];

// 1. Database Connection Check
try {
    $pdo = new PDO('sqlite:' . $databaseFile);
    $checks['database'] = '✅ PASS';
} catch (Exception $e) {
    $checks['database'] = '❌ FAIL';
    $errors[] = 'Database connection failed: ' . $e->getMessage();
}

// 2. Environment Check
$envFile = file_exists($basePath . '/.env');
if ($envFile) {
    $envContent = file_get_contents($basePath . '/.env');
    if (strpos($envContent, 'APP_ENV=production') !== false) {
        $checks['environment'] = '✅ PRODUCTION';
    } elseif (strpos($envContent, 'APP_ENV=local') !== false) {
        $checks['environment'] = '⚠️  DEVELOPMENT';
        $warnings[] = 'Running in development environment';
    } else {
        $checks['environment'] = '❌ UNKNOWN';
        $warnings[] = 'Unknown environment configuration';
    }
} else {
    $checks['environment'] = '❌ FAIL';
    $errors[] = '.env file not found';
}

if (!file_exists($vendorAutoloadPath)) {
    $warnings[] = 'vendor/autoload.php missing; dependency-powered checks were skipped.';
}

// 3. Storage Check
try {
    $testFile = $storagePath . '/app/test-readiness.txt';
    file_put_contents($testFile, 'production check');
    $content = file_get_contents($testFile);
    if ($content === 'production check') {
        unlink($testFile);
        $checks['storage'] = '✅ PASS';
    } else {
        $checks['storage'] = '❌ FAIL';
        $errors[] = 'Storage system not working properly';
    }
} catch (Exception $e) {
    $checks['storage'] = '❌ FAIL';
    $errors[] = 'Storage system error: ' . $e->getMessage();
}

// 4. Security Check - Debug Files
$rootFiles = ['debug_auth.php', 'test_auth.php', 'debug_request.php', 'debug_dean_auth.php', 'debug_session.php'];
$foundDebugFiles = 0;

foreach ($rootFiles as $file) {
    if (file_exists(__DIR__ . '/../' . $file)) {
        $foundDebugFiles++;
    }
}

if ($foundDebugFiles === 0) {
    $checks['debug_files'] = '✅ PASS';
} else {
    $checks['debug_files'] = '❌ FAIL';
    $errors[] = "Found {$foundDebugFiles} debug files in project root";
}

// 5. File Permissions Check
if (is_writable($storagePath)) {
    $checks['file_permissions'] = '✅ PASS';
} else {
    $checks['file_permissions'] = '⚠️  WARNING';
    $warnings[] = 'Storage directory may not be writable';
}

// Display Results
foreach ($checks as $check => $status) {
    echo "{$check}: {$status}\n";
}

if (!empty($warnings)) {
    echo "\n⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\n📊 SUMMARY:\n";
$totalChecks = count($checks);
$passedChecks = count(array_filter($checks, fn($check) => str_contains($check, '✅')));
$failedChecks = count(array_filter($checks, fn($check) => str_contains($check, '❌')));

echo "Total Checks: {$totalChecks}\n";
echo "Passed: {$passedChecks}\n";
echo "Failed: {$failedChecks}\n";

if (empty($errors) && empty($warnings)) {
    echo "\n🎉 SYSTEM IS PRODUCTION READY!\n";
    exit(0);
} elseif (!empty($errors)) {
    echo "\n🚨 SYSTEM HAS CRITICAL ISSUES - FIX BEFORE DEPLOYMENT\n";
    exit(1);
} else {
    echo "\n⚠️  SYSTEM HAS WARNINGS - REVIEW BEFORE DEPLOYMENT\n";
    exit(2);
}
