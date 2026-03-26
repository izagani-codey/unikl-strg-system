# Debugging "1 failed, 24 passed" test run

If your local run shows one failing test:

1. Run a focused failure-first test run:

```bash
php artisan test --stop-on-failure
```

2. If the failure is auth/dashboard related, run:

```bash
php artisan test --filter "AuthenticationTest|RegistrationTest|ProfileTest"
```

3. If the failure appears after recent workflow changes, run:

```bash
php artisan test --filter "ProfileTest|AuthenticationTest|ExampleTest"
```

4. Run repository QA script:

```bash
./scripts/qa_check.sh
```

PowerShell users:

```powershell
./scripts/qa_check.ps1
```

## Common causes in this project

- Missing dependencies (`vendor/autoload.php`) if `composer install` was not run.
- Environment/config cache stale: run `php artisan optimize:clear`.
- Local DB mismatch: rerun with fresh database if needed (`RefreshDatabase` tests rely on migrations).

## Recommended local recovery flow

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan optimize:clear
php artisan test --stop-on-failure
```
