# Realistic Build Checklist (Have / Partial / Missing)

_Last updated: 2026-03-26_

Legend:
- ✅ Built and working
- 🟡 Partial / basic version exists
- ❌ Not built yet

---

## Confirmed — In Progress ⏳

### Filters
- ✅ DashboardController filter logic exists (search/status/type/date range).
- ✅ Filter bars exist in dashboard views (including clear/reset button).
- 🟡 Search scope is broad but can be refined further per-role.
- ❌ Pagination + preserved query state not fully implemented across all tables.

Fields status:
- ✅ Search (ref number + submitter data)
- ✅ Status dropdown
- ✅ Request type dropdown
- ✅ Date from / date to
- ✅ Clear filter button

---

## Confirmed — Planned 📋

### Notifications
- ✅ In-app alerts per user
- ✅ Core triggers implemented (returned/rejected/approved paths and key transitions)
- ✅ Mark-as-read support (including mark all)
- ✅ Bell icon with unread count in nav
- ❌ Email notification channel not implemented

### Audit Log Viewer Page
- ✅ Full history page available for staff roles
- ✅ Filter by request/reference, actor/user, date, status
- ❌ Export to PDF option not implemented

### Staff 2 Admin Panel
- ✅ System stats overview (basic KPIs)
- ✅ Manage blank form templates (upload/list/delete)
- ❌ Manage request types (add/edit/disable)
- ❌ View/manage all users + roles

### Printable Summary
- ✅ Print button on request show page
- ✅ Clean print layout with core details + verification data
- 🟡 Extra stamp metadata (formal ref/date signature style) can be improved

### Blank Forms Download
- ✅ Upload actual templates supported
- ✅ Shown on Admission dashboard
- 🟡 Download is available, but not yet mapped per request type

### Email Restriction Polish
- ✅ Domain restriction is enforced
- ✅ Clear error message exists on invalid domain
- 🟡 UX polish can still improve (inline hint text + pre-submit guidance)

---

## Possible Good Additions 💡

### High Value — Easy to build
- ❌ Global search page
- ❌ Request timeline visual view
- ❌ Deadline reminder/urgency widget
- 🟡 Password reset routes exist via Breeze, but full email flow depends on local mail config

### Medium Value — Worth building
- ❌ Export to Excel/CSV
- ❌ Request duplication
- 🟡 Staff notes exist, but full notes-history timeline view is not built
- ❌ Bulk actions for staff queues

### Nice to Have — Polish
- ❌ Dark mode toggle
- 🟡 Mobile responsiveness exists but needs dedicated table/card optimization
- ❌ Loading states/spinners and submit-lock UX
- ❌ Session timeout warning
- ❌ Activity feed

### Future / Advanced
- ❌ SMTP-based email notifications
- ❌ API layer
- ❌ 2FA for Staff 2/admin accounts
- ❌ File versioning across revisions
- ❌ Analytics dashboard (approval time, rejection rates, etc.)

---

## Recommended execution order (practical)
1. Pagination + test coverage (filters/policies/transitions/notifications)
2. Request timeline + deadline reminder
3. Export CSV + request duplication
4. Staff2 admin (request types + user role management)
5. UX polish pass (mobile/loading/session warnings)
