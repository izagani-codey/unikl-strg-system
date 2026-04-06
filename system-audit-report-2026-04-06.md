# UniKL STRG System Audit (April 6, 2026)

## Scope
Comprehensive static audit of controller logic, route registration, PDF form services, and developer workflow readiness.

## Audit Method
The following checks were run:

1. Repository-wide PHP lint on `app`, `routes`, `config`, `database`, and `tests`.
2. Targeted inspection of workflow-critical files:
   - `app/Http/Controllers/RequestController.php`
   - `app/Http/Controllers/TestController.php`
   - `app/Services/PdfFormFillerService.php`
   - `routes/web.php`
   - `database/seeders/*.php`
3. Dependency/tooling validation (`composer install`, `php artisan test`, `./vendor/bin/pint --test`) to validate runtime readiness.

## Confirmed Critical Issues Found

### 1) Parse-breaking PDF service implementation (**Critical**) — Fixed
- **What was happening:** `PdfFormFillerService` contained invalid PHP syntax in a `match` arm (`nullsafe + ternary` mix), causing parse failure.
- **Impact:** Any runtime autoloading or static analysis touching this class would fail.
- **Fix implemented:** Replaced invalid expression with nullsafe + coalesce and refactored FPDI flow to a valid `setSourceFile`/`importPage`/`useTemplate` pipeline.

### 2) Dev diagnostic controller class import collision (**High**) — Fixed
- **What was happening:** `TestController` imported both `App\Models\Request` and `Illuminate\Http\Request` under the same short name `Request`.
- **Impact:** Fatal parse error (`name is already in use`) in local/dev diagnostics route.
- **Fix implemented:** Aliased the model import to `GrantRequest` and updated references.

### 3) Missing controller import in routes (**High**) — Fixed
- **What was happening:** `routes/web.php` referenced `RequestTypeController::class` without a corresponding `use` statement.
- **Impact:** Route file can fail class resolution when loading the route definition.
- **Fix implemented:** Added explicit `use App\Http\Controllers\RequestTypeController;`.

### 4) Admission priority escalation on revision (**Medium**) — Fixed
- **What was happening:** Admission users (who should not set priority) could set `is_priority` when revising returned requests.
- **Impact:** Workflow policy drift and unauthorized priority elevation.
- **Fix implemented:** Forced `is_priority` to `false` during admission-side request update path.

### 5) Seeder identity inconsistency (**Low**) — Fixed
- **What was happening:** `AdminUserSeeder` used `admission@...` while primary seeding/docs use `admissions@...`.
- **Impact:** Confusing login expectations in local/dev setups.
- **Fix implemented:** Standardized `AdminUserSeeder` admission email to `admissions@unikl.edu.my`.

## Remaining Risks / Constraints

1. **Dependency installation blocked in this environment**
   - `composer install` failed due outbound GitHub access restrictions (`CONNECT tunnel failed, response 403`).
   - As a result, runtime tests (`php artisan test`) and Pint checks could not be executed here.
2. **Dynamic behavior not runtime-verified**
   - PDF generation flow, workflow transitions, and authorization matrices were statically audited and syntax-validated, but not executed end-to-end in this environment.

## Refinement Outcomes (This Iteration)

- Eliminated parse/runtime blockers in PDF filler and test diagnostic controller.
- Restored route class completeness for request type template retrieval.
- Hardened admission workflow by removing unauthorized priority mutation path.
- Reduced environment confusion by aligning seeded admission account naming.

## Recommended Next Steps (when network-enabled CI/dev host is available)

1. Run `composer install` successfully with package download access.
2. Run `php artisan test` and `./vendor/bin/pint --test`.
3. Add/extend feature tests for:
   - Admission revision behavior (`is_priority` always false).
   - Request type template route resolution.
   - PDF filler service smoke-generation path.
