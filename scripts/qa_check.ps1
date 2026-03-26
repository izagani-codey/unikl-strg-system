Write-Host "[1/5] PHP syntax lint (app, routes, database, tests)"
Get-ChildItem app,database,routes,tests -Recurse -Filter *.php |
    ForEach-Object { php -l $_.FullName | Out-Null }

Write-Host "[2/5] Search for merge conflict markers"
$conflicts = rg -n "<<<<<<<|>>>>>>>" app resources routes database tests
if ($LASTEXITCODE -eq 0 -and $conflicts) {
    Write-Host $conflicts
    throw "Merge conflict markers found"
}

Write-Host "[3/5] Check for TODO/FIXME hotspots"
rg -n "TODO|FIXME" app resources routes

Write-Host "[4/5] Composer autoload presence"
if (-not (Test-Path "vendor/autoload.php")) {
    Write-Warning "vendor/autoload.php missing. Run composer install before php artisan test"
} else {
    Write-Host "vendor/autoload.php present"
}

Write-Host "[5/5] Test command hint"
Write-Host "Run: php artisan test --stop-on-failure"
Write-Host "Run: php artisan test --filter AuthenticationTest"
Write-Host "QA check completed."
