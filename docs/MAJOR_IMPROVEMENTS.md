# Major Improvements for UniKL STRG Grant Management System

This roadmap is tailored to your current setup:
- Laravel 13 + Breeze + Tailwind
- SQLite (local dev)
- Laravel Herd on Windows
- Roles: `admission`, `staff1`, `staff2`
- Workflow status codes:
  - `1` Pending Verification
  - `2` With Staff 2
  - `3` Returned to Admission
  - `4` Returned to Staff 1
  - `5` Approved
  - `6` Declined

---

## 1) Enforce workflow transitions in one place (High Impact)

### Why
Right now, status updates are vulnerable to inconsistent transitions if validation lives only in controllers.

### Improve
- Create a centralized transition map (service or enum helper).
- Validate all status changes against role + current status.
- Reject impossible transitions with clear error messages.

### Example transition policy
- Admission: can submit/resubmit only to `1`.
- Staff1: `1 -> 2`, `1 -> 3`.
- Staff2: `2 -> 5`, `2 -> 6`, `2 -> 4`.
- Staff1 (after return): `4 -> 2`.

---

## 2) Add Laravel Policies for per-record authorization (High Impact)

### Why
Route-level role middleware is good, but each request record still needs ownership/access checks.

### Improve
- Add `RequestPolicy` methods: `view`, `update`, `changeStatus`, `comment`.
- Admission can only see own requests.
- Staff roles can only act on records in their stage.
- Use `authorize()` in controllers.

---

## 3) Finish dashboard filters with query scopes + pagination (High Impact)

### Why
You identified this as the next task; it directly affects usability and staff productivity.

### Improve
- Move filter logic into model scopes:
  - `scopeStatus($query, $status)`
  - `scopeType($query, $typeId)`
  - `scopeDateRange($query, $from, $to)`
  - `scopeSearch($query, $term)`
- Preserve filter state in query string.
- Add pagination (`->paginate(15)->withQueryString()`).
- Add role-aware default filters:
  - Staff1 defaults to status `1` and `4`
  - Staff2 defaults to status `2`

---

## 4) Implement notifications with database channel first (High Impact)

### Why
This is your stated next major task; it’s critical for workflow turnaround time.

### Improve
- Use Laravel Notifications + `database` channel first.
- Trigger notifications on key events:
  - New submission (`1`) -> notify Staff1
  - Staff1 forwards to Staff2 (`2`) -> notify Staff2
  - Returned to Admission (`3`) -> notify owner
  - Returned to Staff1 (`4`) -> notify Staff1
  - Approved/Declined (`5/6`) -> notify Admission
- Add unread badge in nav + notifications dropdown page.
- Add “mark all read” action.

---

## 5) Introduce feature tests around workflow rules (High Impact)

### Why
Without tests, refactoring status logic and notifications becomes risky.

### Improve
- Add feature tests for:
  - Role access restrictions
  - Valid vs invalid transitions
  - Dashboard filtering behavior
  - Notification dispatch on transitions
- Use factories + seed minimal roles/types.

---

## 6) Replace magic status integers with enum/constants (Medium-High)

### Why
`1..6` in controllers/views is hard to maintain.

### Improve
- Add `RequestStatus` enum (PHP 8.3 backed enum).
- Store integer in DB but map via enum.
- Use enum labels/badges in UI.

---

## 7) Improve data model constraints and indexing (Medium-High)

### Why
Even with SQLite, constraints prevent data corruption.

### Improve
- Add foreign key constraints where missing.
- Add indexes:
  - `requests.status_id`
  - `requests.request_type_id`
  - `requests.user_id`
  - `requests.created_at`
- Make `ref_number` unique.

---

## 8) Add activity timeline UX in request detail (Medium)

### Why
Staff need quick context without reading raw logs.

### Improve
- Build a chronological timeline:
  - Created, status transitions, comments, returns, decisions.
- Use color-coded status chips.
- Highlight latest action + responsible actor.

---

## 9) Harden file upload handling (Medium)

### Why
Uploads are security-sensitive.

### Improve
- Validate MIME + extension + max size.
- Store outside public path and serve via signed/authorized download route.
- Virus-scan hook (optional later).

---

## 10) CI + code quality baseline (Medium)

### Why
Keeps quality consistent as you pass 55% completion and start faster iteration.

### Improve
- Add GitHub Actions for:
  - `php artisan test`
  - `./vendor/bin/pint --test`
- Add pull request template with checklist (tests run, auth checked, transitions validated).

---

## Suggested 2-Week Execution Order

1. Workflow transition guard + policy layer
2. Dashboard filter refactor + pagination + query persistence
3. Notifications (database channel) + unread UI
4. Feature tests for all above

This order gives you immediate user-visible value while reducing regression risk.
