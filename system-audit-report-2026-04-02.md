# UniKL STRG System Audit (April 2, 2026)

## Scope
Static code audit of routing, authorization, notification flow, PDF generation, and operational safety checks.

## Findings

### 1) Broken notification route for dean stage (**High**)
- **What happens:** When a request transitions to `PENDING_DEAN_APPROVAL`, the notification URL is generated with `route('dean.requests.show', ...)`.
- **Problem:** Dean routes are currently commented out, so this named route does not exist at runtime.
- **Risk:** Transition attempts can fail with `RouteNotFoundException` and block normal workflow progression.
- **Evidence:** `notifyDean()` uses `route('dean.requests.show', ...)` while dean route block is commented. 
- **Fix:** Point notifications to an always-available route (e.g., `requests.show`) or re-enable dean routes with role middleware.

### 2) Potential open redirect via notification URLs (**High**)
- **What happens:** `NotificationController::open()` redirects to `$notification->url` with no host/path validation.
- **Problem:** If a malicious/incorrect URL is stored in notifications, users can be redirected to external phishing domains.
- **Risk:** Security risk (phishing/session abuse) and trust erosion.
- **Fix:** Enforce internal URLs only (e.g., allow relative URLs or validate host against `config('app.url')`) before redirecting.

### 3) PDF view path mismatch in PDF service (**High**)
- **What happens:** `RequestPdfService` renders `requests.pdf-template`.
- **Problem:** Existing template file is `resources/views/pdf-template.blade.php` (not under `requests/`).
- **Risk:** PDF generation fails; current controller suppresses the exception and continues, so users may not notice broken PDF generation.
- **Fix:** Align view name and file location (`pdf-template` or move file to `resources/views/requests/pdf-template.blade.php`). Add test coverage for PDF generation.

### 4) Duplicate route definition for request PDF endpoint (**Medium**)
- **What happens:** `/requests/{id}/pdf` with name `requests.pdf` is defined twice.
- **Problem:** Route registration order can cause ambiguity and maintenance mistakes (one definition may shadow the other).
- **Risk:** Inconsistent middleware/authorization expectations over time.
- **Fix:** Keep a single route definition and apply one clear middleware policy.

### 5) Sensitive internal exception messages exposed to users (**Medium**)
- **What happens:** Staff2 admin methods catch exceptions and return `->with('error', 'Error ...: ' . $e->getMessage())`.
- **Problem:** Raw exception messages may leak internal implementation details.
- **Risk:** Information disclosure and poorer security posture.
- **Fix:** Log full exception internally, return generic user-safe message in UI.

### 6) Documentation and seeded account mismatch (**Low**)
- **What happens:** README lists `admission@unikl.edu.my` demo account, but current seeder provisions `admissions@unikl.edu.my` and deletes the singular form first.
- **Problem:** Developers/testers following README may fail login and report false defects.
- **Risk:** Operational confusion and onboarding friction.
- **Fix:** Update README account list to match seed data or seed both aliases intentionally.

## Recommended Remediation Plan (Priority Order)
1. **P0:** Fix dean notification route target (Finding #1).
2. **P0:** Add notification redirect URL allowlist/validation (Finding #2).
3. **P0:** Fix PDF template path and add automated test (Finding #3).
4. **P1:** Remove duplicate PDF route (Finding #4).
5. **P1:** Replace user-facing raw exception messages with generic text (Finding #5).
6. **P2:** Align README with seeder accounts (Finding #6).

## Validation Commands Run
- `php artisan test` (failed in current environment because dependencies are not installed: missing `vendor/autoload.php`).
- `rg -n "dean\.requests\.show|requests\.pdf|route\('dean|Route::get\('/requests/\{id\}/pdf'" app routes`
- `rg --files resources/views | rg "pdf-template\.blade\.php$" -n`
- `sed -n '1,260p' routes/web.php`
- `sed -n '1,260p' app/Services/WorkflowTransitionService.php`
- `sed -n '1,260p' app/Http/Controllers/NotificationController.php`
- `sed -n '1,220p' app/Services/RequestPdfService.php`
- `sed -n '1,220p' database/seeders/DatabaseSeeder.php`
- `sed -n '1,240p' README.md`
