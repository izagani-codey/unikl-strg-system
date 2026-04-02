# UniKL STRG System Full Technical Audit (A→Z)

Date: 2026-04-02 (UTC)
Scope: End-to-end architecture, workflows, last-week change review, operational risks, and prioritized action plan.

---

## 1) Executive Summary

The platform is a Laravel 13 request workflow system with role-oriented processing (`admission`, `staff1`, `staff2`, plus a partially exposed `dean` path). It has evolved quickly in the last week, adding VOT-based financial payloads, signature/profile snapshots, auto-priority logic, Staff 2 override controls, dean-verification status flow, PDF generation, and expanded admin/reporting capabilities.

The implementation is functional, but there are important consistency gaps caused by rapid iteration:

- There are **duplicate workflow definitions** (controller + service) that can drift.
- Some code still uses **legacy hardcoded status IDs** (e.g., `5,6`) that are now incorrect after enum remapping.
- The dashboard/statistics cache invalidation assumes Redis wildcard support and may fail in non-Redis cache drivers.
- Workflow tests are partially out-of-sync with current transition rules.

Overall maturity: **working but stabilization phase**. Highest priority should be transition consistency, status-ID cleanup, and regression test hardening.

---

## 2) System Purpose and Business Flow

### 2.1 Main actors

- **Admission**: creates and revises requests.
- **Staff 1**: first-level verification.
- **Staff 2**: recommendation/final actions + override tools.
- **Dean**: routes/controllers still exist, but UI exposure is intentionally limited in recent workflow tweak.

### 2.2 Core domain object

`requests` is the central aggregate, carrying:

- Lifecycle status (`status_id` via enum),
- submitter snapshot fields,
- VOT line items + total,
- optional file attachment / generated PDF path,
- deadline and priority flags,
- audit/comment relationships,
- dean and override metadata.

---

## 3) Runtime Architecture (How it works A→Z)

### A) Entry, auth, and role routing

1. User authenticates via Laravel auth stack.
2. `/dashboard` routes to role-specific dashboard view (`dashboard.{role}`), except dean users are redirected to dean dashboard route.
3. Route groups enforce role middleware for action permissions (admission-only create/update; staff-only status/comment ops; staff2-only override/admin endpoints).

### B) Request creation path

1. Admission opens `/requests/create`.
2. Submission validates via `StoreRequestRequest`.
3. Controller computes totals from VOT items and creates a request record with status `PENDING_VERIFICATION`.
4. User profile fields are snapshotted into request fields at submission time.
5. Signature data can be saved inline.
6. Optional supporting file is stored in `public` disk.
7. PDF generation is attempted; failure is non-blocking.

### C) Workflow transitions

1. Staff transitions are submitted to `updateStatus`.
2. Authorization checks policy (`changeStatus`).
3. Transition execution delegates to `WorkflowTransitionService`:
   - checks role-to-status transition matrix,
   - writes audit log,
   - updates request fields,
   - updates actor tracking fields,
   - dispatches role-targeted notifications.

### D) Revision/resubmission path

1. Admission can edit only returned requests.
2. On update, revision counter increments.
3. If request is `RETURNED_TO_ADMISSION`, system auto-transitions back to `PENDING_VERIFICATION`.
4. PDF regeneration logic preserves uploaded attachment paths where applicable.

### E) Priority and deadline management

- Request model has helpers for urgent/high/normal labels.
- Priority auto-updates can run:
  - when viewing request detail via `AutoPriorityMiddleware`,
  - through command `requests:update-priorities`.
- Current implementation has status constant drift risk in command/console route version (details in Risk section).

### F) Notifications and auditability

- Notifications are database-backed with unread badge and mark-all-read support.
- Workflow service sends notifications for major transitions.
- Audit logs capture transition actor/from/to/note.
- Staff comments are internal and tied to requests.

### G) Staff2 override subsystem

- Staff2 may enable personal override mode (`override_enabled` on user).
- When enabled, allowed override actions include direct approve, rejection reverse, bypass verification, and priority toggle.
- Each override records before/after snapshots in `override_logs` and sends notifications.

### H) Templates, export, and output

- Form templates can be uploaded/managed by staff2.
- Admission can consume available blank forms.
- Template auto-fill service exists but currently copies files as placeholder behavior (not true field fill yet).
- Request data can be exported via Excel service.
- Printable summary and downloadable PDF endpoints are available.

---

## 4) Data Model and Schema Evolution (recent)

Notable schema additions over the recent update window:

1. **User staff/profile signature fields** (staff_id, designation, department, phone, employee_level, signature_data).
2. **Request financial/signature fields** (vot_items JSON, total_amount, submitted_at, etc.).
3. **Override capability fields** on users and dedicated `override_logs` table.
4. **Dean metadata fields** on requests.
5. **VOT code master table** + seeder (11 standard codes).
6. **Status value remap migration** to align persisted data with expanded enum.

Implication: existing environments need strict migration order and data validation after deploy.

---

## 5) Last-Week Change Audit (what changed recently)

From commit history in the last ~7–10 days:

- Heavy modifications concentrated around Mar 31–Apr 1, 2026.
- Major waves included:
  - enum expansion (`PENDING_DEAN_VERIFICATION`),
  - staff-driven “confirm by dean” pathway,
  - dean-route retention but reduced UI exposure,
  - override controls and logs,
  - user/profile and request payload enrichment,
  - template usage tracking and admin tooling,
  - PDF/file path consistency fixes.

This confirms your observation that the project changed significantly last week.

---

## 6) Risk Register (important findings)

### Critical / High

1. **Transition logic duplicated in two places**
   - `RequestController::allowedTransitions()` and `WorkflowTransitionService::getAllowedTransitions()` both define workflow maps.
   - Drift risk is high; one source should be canonical.

2. **Legacy status IDs still hardcoded in command contexts**
   - Priority update command and `routes/console.php` exclude statuses `[5,6]` as “approved/declined”, but current enum uses `8,9`.
   - This can produce incorrect priority updates for finalized requests.

3. **Policy vs service behavior mismatch potential**
   - Policy allows staff2 broader action latitude; service matrix may still block certain transitions.
   - User-visible behavior may look inconsistent (“authorized but cannot transition”).

### Medium

4. **Cache clear implementation depends on Redis keys()**
   - `StatisticsRepository::clearCache()` uses Redis-specific wildcard deletion path; non-Redis stores may error or silently skip.

5. **Template autofill is a placeholder copy operation**
   - The feature name suggests field mapping fill, but implementation currently duplicates file and logs usage.

6. **Tests likely stale against latest status model**
   - Some tests expect transitions (e.g., staff2 → approved directly) that no longer align with current transition matrix.

### Low

7. **Inconsistent domain naming (`GrantRequest` aliasing)**
   - Functional but adds cognitive load.

---

## 7) “Tweaks and things to do” — prioritized action plan

## Phase 1 (Stabilization, immediate)

1. **Make transition matrix single-source-of-truth**
   - Remove duplicate transition map from controller.
   - Keep only `WorkflowTransitionService` (or a dedicated workflow config class).

2. **Replace all hardcoded status numbers with enum references**
   - Especially `routes/console.php` and `UpdatePrioritiesCommand`.

3. **Add transition contract tests**
   - One feature test per role+status transition edge.
   - Include negative tests for forbidden transitions.

4. **Reconcile policy + service rules**
   - Ensure policy acceptance cannot lead to service denial except for data race edge cases.

## Phase 2 (Correctness + quality)

5. **Fix cache invalidation portability**
   - Make wildcard invalidation driver-safe or switch to cache tags where supported.

6. **Harden template autofill scope wording**
   - Either implement true PDF field injection or relabel feature explicitly as template copy/generation stub.

7. **Improve migration safety checks**
   - Add post-migrate sanity command validating status distribution and nullability expectations.

## Phase 3 (Operational maturity)

8. **Publish runbook**
   - Include: migration order, seed expectations, role test accounts, smoke tests, rollback instructions.

9. **Observability**
   - Add structured logs/events for all overrides and critical transition failures.

10. **KPI dashboard finalization**
   - Monthly SLA trends and queue aging by role.

---

## 8) How to operate this system safely (recommended)

1. `composer install && npm install`
2. `php artisan migrate --seed`
3. `php artisan optimize:clear`
4. `php artisan test --stop-on-failure`
5. Run workflow smoke tests by role:
   - admission submit,
   - staff1 verify/return/confirm-dean,
   - staff2 recommend/return/confirm-dean,
   - override enable + action + log verification.
6. Run `php artisan requests:update-priorities` and verify finalized requests are excluded using enum constants.

---

## 9) Bottom Line

The system is feature-rich and has moved from prototype toward production-ish workflow tooling quickly. Your latest updates are substantial and directionally strong, but the codebase now needs a focused stabilization sprint to avoid logic drift and regression. If you execute the Phase 1 items first, reliability will improve immediately with minimal product disruption.
