# Features Left (Remaining Work)

_Last updated: 2026-03-26_

## Already delivered (baseline)
- Role-based workflow (admission/staff1/staff2)
- Dashboard filtering and stats
- Notifications center + unread badge
- Audit log viewer
- Staff2 admin panel (basic)
- Blank form template upload/download
- Printable summary page
- Local dev QA scripts and project docs

---

## Features left to complete

## 1) Testing & quality gate (highest priority)
- Add feature tests for:
  - status transition guards (valid + invalid)
  - request access policy (view/print/revise)
  - notification dispatch on transitions
  - template upload/delete authorization
- Add CI workflow (GitHub Actions) to run tests and lint on every PR.

## 2) Transition engine refactor
- Move transition map from `RequestController` into dedicated service/enum.
- Reuse that transition map in:
  - backend validation
  - dashboard action availability
  - audit/status label rendering

## 3) Authorization hardening expansion
- Extend policy checks consistently to all request-related endpoints and future modules.
- Add policy coverage for audit log access and form-template management.

## 4) Dashboard performance and consistency
- Replace large `get()` result sets with pagination + `withQueryString()`.
- Standardize filter UX/cards across all roles.
- Add status labels in audit log filter dropdowns instead of raw numeric statuses.

## 5) File security hardening
- Serve documents/templates through authorized download endpoints (instead of direct public URL where possible).
- Optional: virus scanning step on uploads.

## 6) Staff2 admin panel v2
- Add trend analytics (weekly/monthly), SLA/turnaround metrics.
- Add export (CSV/PDF) for operational reporting.

## 7) Notification polish
- Add read/unread filters and per-notification action shortcuts.
- Add optional email channel for critical transitions.

## 8) UX polish pass (final)
- Clean visual consistency (spacing/typography/components).
- Improve empty states, validation feedback, and loading states.
- Add role-aware onboarding hints/help cards.

## Suggested next implementation order
1. Tests + CI
2. Transition service/enum
3. Pagination + authorization expansion
4. File-security hardening
5. Admin analytics + UX final polish

## 