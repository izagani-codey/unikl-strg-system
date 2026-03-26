# UniKL STRG System — Current Changes and Next Required Changes

_Last updated: 2026-03-26_

## 1) Current changes already implemented

### Core workflow and access
- Role-based routing and middleware for `admission`, `staff1`, and `staff2`.
- Request lifecycle handling with status updates and audit entries.
- Transition guard logic in `RequestController` (`isValidTransition`) for staff actions.
- Admission ownership restriction for viewing/printing request details.

### Dashboard and inbox
- Centralized dashboard filtering (`status`, `type`, `date_from`, `date_to`, `search`) in `DashboardController`.
- Dashboard status statistics (`dashboardStats`) generated server-side.
- Staff and admission dashboard filter UIs wired to query params.

### Notifications
- Database-backed notifications model.
- Notification center page with unread/read behavior.
- “Mark all as read” support.
- Workflow-triggered notifications on submission and major status transitions.
- Navigation unread badge.

### Audit logs
- Audit log viewer page for staff roles.
- Filters for actor, reference, status, and date range.

### Staff 2 operations
- Staff 2 admin panel with KPI cards and quick queues.
- Blank form template management (upload/list/delete).
- Admission dashboard now shows downloadable blank templates.

### Request output
- Printable summary route/page for request details.

### UX polish
- Refreshed guest/auth layout and login/register screens.
- Expanded top navigation with role-aware links.

---

## 2) Changes still needed (recommended backlog)

### High priority (stability + correctness)
1. **Move transition rules into a dedicated service/enum**
   - Current transition map lives inside `RequestController`.
   - Centralize into reusable domain logic and reuse in UI + tests.

2. **Add formal authorization policies**
   - Replace ad-hoc role checks with `RequestPolicy` (view/update/print/comment).
   - Enforce policy checks in controllers consistently.

3. **Automated test coverage**
   - Feature tests for:
     - Role access restrictions
     - Valid/invalid status transitions
     - Notification dispatch expectations
     - Template upload/delete permissions

4. **Fix/clean role helper methods in `User` model**
   - Existing helpers (`isPersonA`, `isPersonB`, `isAdmissions`) are inconsistent with actual role names.

### Medium priority (maintainability + performance)
5. **Refactor fat controllers**
   - Split request workflow actions into service classes.
   - Keep controllers thin (validation + orchestration).

6. **Paginate large lists**
   - Dashboard currently uses `get()` and can grow heavy.
   - Apply pagination and preserve filters with query string.

7. **Harden uploads and file access**
   - Move document/template serving behind authorized download endpoints.
   - Avoid direct public URL exposure where possible.

8. **Improve data consistency and indexing**
   - Add/verify indexes for request filtering columns.
   - Add DB constraints and unique guarantees (e.g., ref number).

### UX and admin enhancements
9. **Staff 2 admin panel upgrades**
   - Add monthly trend charts, SLA/turnaround metrics, and export actions.

10. **Audit log readability improvements**
    - Show status labels instead of raw numeric transitions.

11. **Dashboard consistency pass**
    - Standardize card/table/filter layout across all roles.

12. **Operational documentation**
    - Add runbook for seeding, role testing, and deployment checklist.

---

## 3) Suggested implementation order (next 2–3 PRs)

1. Policy layer + transition service + tests (most important).
2. Pagination + upload hardening + index updates.
3. Admin analytics and final UI consistency polish.
