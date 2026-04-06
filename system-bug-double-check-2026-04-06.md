# UniKL STRG Bug Double-Check (April 6, 2026)

## Objective
Second-pass bug verification focused on runtime reliability and schema/controller consistency.

## Checks Performed
1. Static inspection of workflow, status transitions, and dean routes/controllers.
2. Code/schema consistency cross-check (`app/**` against `database/migrations/**`).
3. Targeted grep for suspicious fields and API signatures.
4. PHP lint on modified files.

## Confirmed Bugs Found and Addressed

### 1) FPDI write calls missing required line-height argument (High)
- **Location:** `app/Services/PdfFormFillerService.php`
- **Issue:** Several `Write()` calls in VOT rendering passed only text, which can fail at runtime.
- **Fix:** Updated to `Write(5, $text)` for all affected calls.

### 2) Dean check endpoint referenced non-existent attribute (High)
- **Location:** `app/Http/Controllers/RequestController.php`
- **Issue:** `checkDeanApproval()` referenced `dean_confirmed_at`, which is not part of the current request schema.
- **Fix:** Replaced with `dean_approved_at`-based output (`dean_decision_at`) and removed invalid field usage.

### 3) Request service writing columns not present in schema (High)
- **Location:** `app/Services/RequestService.php`
- **Issue:** `updateStatus()` attempted to write `verified_at` and `recommended_at` fields that are not in migrations.
- **Fix:** Removed writes to undefined columns while preserving assignee tracking (`verified_by`, `recommended_by`).

### 4) Staff workload statistics depended on undefined columns (High)
- **Location:** `app/Repositories/StatisticsRepository.php`
- **Issue:** Query used `verified_at` / `recommended_at`, which can cause SQL errors.
- **Fix:** Reworked workload query to use existing timestamps (`created_at` to `updated_at`) and existing staff linkage fields.

## Remaining Verification Constraints
- Full runtime tests remain blocked in this environment because dependency installation cannot complete (network/proxy restriction to GitHub).

## Suggested Next Runtime Validation
1. Run migrations on a clean DB and verify dashboard/admin metrics pages load.
2. Exercise dean-check endpoint on requests in dean stages.
3. Execute PDF form filling for VOT template types.
4. Run full feature tests once dependencies are installable.
