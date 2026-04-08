# Full Code Review — UniKL STRG System (2026-04-08)

## Scope and context

This review is tailored to your current lifecycle:

- `admission -> staff1 -> staff2 -> dean`
- PDF generation with stage signatures

The audit focuses on:

1. **Scalability risks**
2. **Maintainability problems**
3. **Likely future bugs as features expand**

---

## Executive summary

Your system has a strong functional baseline for a linear approval workflow, especially because transition logic is centralized in `WorkflowTransitionService`. However, several design choices will become pressure points as load and feature complexity increase:

- **Scalability:** request listing, notifications fan-out, and file/PDF lifecycle are likely to degrade with growth.
- **Maintainability:** duplicate migrations and split transition entry points (`RequestController` + `DeanController`) increase drift risk.
- **Future bug risk:** status model and transition matrix are currently rigid; introducing parallel approvals/escalations/delegation is likely to create fragile branching behavior unless the domain model evolves.

---

## Findings by audit area

## A) Scalability

### A1) High — Reference number generation is non-atomic (collision under concurrency)

`generateReferenceNumber()` computes sequence via `count() + 1` per year, which is race-prone under concurrent submissions.

- **Why this matters at scale:** collisions become more likely with parallel submission bursts.
- **Impact:** duplicate human reference IDs, reconciliation issues, potential uniqueness violations if a unique index is added later.

**Recommendation**
- Add a unique index on `requests.ref_number`.
- Move generation to an atomic mechanism (transactional counter table / DB sequence / retry-on-conflict strategy).

---

### A2) Medium — Notification fan-out is synchronous and role-wide

`notifyRole()` loads all users in a role and inserts notifications in a loop during transition execution.

- **Why this matters at scale:** transition latency grows with user count in staff/dean roles.
- **Impact:** slower user actions, higher DB write spikes, increased timeout risk.

**Recommendation**
- Queue notification creation via jobs.
- Batch insert where possible.
- Add idempotency guard if retries are enabled.

---

### A3) Medium — Request index query likely to become expensive with richer filtering

`RequestController::index()` builds role-aware queries with joins and optional filters; this is fine now, but growth will require indexing and possibly read-model optimization.

- **Risk factors:** text search + relationship filters + date filters + sorting on `created_at`.
- **Impact:** dashboard latency for staff roles with large datasets.

**Recommendation**
- Add/verify DB indexes on `status_id`, `request_type_id`, `user_id`, `created_at`.
- Consider full-text strategy for `ref_number` and submitter fields if search expands.

---

### A4) Medium — PDF regeneration strategy can become costly

PDFs are generated on submission/update and regenerated on signature transitions.

- **Why this matters at scale:** rendering PDF synchronously in request path increases P95 latency.
- **Impact:** slow transition actions and variable UX under load.

**Recommendation**
- Offload PDF generation/regeneration to queued jobs.
- Store immutable versioned artifacts per stage (`submitted`, `staff2-signed`, `dean-signed`) for traceability and reduced recomputation.

---

## B) Maintainability

### B1) High — Duplicate migrations for identical schema changes

Two duplicate migration pairs exist:

1. `default_template_id` on `request_types`
2. stage signature columns on `requests`

They are guarded with `Schema::hasColumn`, which prevents immediate failure, but this is still migration debt.

- **Impact:** confusion during incident/debugging, harder schema provenance, noisy migration history.

**Recommendation**
- Keep one canonical migration per schema change.
- Add migration governance checks in CI to detect near-identical duplicates.

---

### B2) Medium — Transition orchestration has multiple controller entry points

Transitions are initiated from both `RequestController::updateStatus()` and separate dean actions in `DeanController`.

- **Impact:** inconsistent validation and authorization patterns over time.
- **Future drift risk:** new transition-related rules might be added in one path and missed in another.

**Recommendation**
- Consolidate transition entry to a single controller/service boundary per action type.
- Keep `WorkflowTransitionService` as single transition authority, but unify request validation and authorization wrappers.

---

### B3) Medium — Hard-coded role/status branching limits extensibility

Role and status checks are currently explicit string/enum branch chains.

- **Impact:** adding roles (delegate, acting dean) or alternate paths (parallel approval) will require edits across many points.

**Recommendation**
- Move to a declarative workflow config/state-machine model (transition table + guards + side-effects registry).
- Keep policy checks role-agnostic where possible (capability-based permissions).

---

## C) Future bug risk as features expand

### C1) High — Workflow model is linear; expansion to parallel/conditional paths will be fragile

Current matrix assumes mostly linear sequencing with explicit role ownership.

- **Bug vector:** introducing branch logic (e.g., dean optional, finance fork, rework loops by stage) may produce unreachable/invalid states unless model is restructured.

**Recommendation**
- Introduce explicit workflow definition primitives:
  - states,
  - transitions,
  - guards,
  - side effects,
  - terminal conditions.
- Add workflow invariants tests that auto-validate transition graph consistency.

---

### C2) Medium — Signature/PDF coupling may create version ambiguity

Signatures are stored by stage and PDFs may be regenerated.

- **Bug vector:** unclear artifact lineage when multiple revisions/signatures occur (which PDF corresponds to which signature set?).

**Recommendation**
- Persist versioned PDF records tied to request revision + status transition ID.
- Ensure audit log references exact artifact IDs.

---

### C3) Medium — README references missing roadmap document

README links to `docs/MAJOR_IMPROVEMENTS.md`, which is absent.

- **Impact:** contributor onboarding friction and stale documentation signal.

**Recommendation**
- Add the file or remove/update the link.

---

### C4) Low — Dean controller has unused variables and inconsistent explicit authorization style

`$dean = Auth::user();` is assigned but unused in dean actions.

- **Impact:** minor readability debt; easy source of confusion during refactors.

**Recommendation**
- Remove unused variables and standardize explicit policy invocation for consistency.

---

## Target architecture guidance (next 2–3 iterations)

1. **Iteration 1 (stability):**
   - Atomic reference generation + unique constraint.
   - Remove duplicate migrations.
   - Fix README broken doc reference.

2. **Iteration 2 (scalability):**
   - Queue notifications and PDF generation.
   - Add index review + query profiling on request dashboards.

3. **Iteration 3 (future-proofing):**
   - Introduce declarative workflow engine/state machine abstraction.
   - Version PDF artifacts and bind them to audit events.

---

## Positive notes

- Centralized transition execution is a solid foundation.
- Redirect safety checks for notifications are defensive and well considered.
- FormRequest usage is reasonably consistent for critical endpoints.

---

## Commands/checks run in this review cycle

- `bash scripts/qa_check.sh` (passed; warned that vendor autoload is missing)
- `composer install --no-interaction --prefer-dist` (failed in environment due GitHub/proxy `403`)
