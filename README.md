# UniKL STRG Request System

A Laravel 13 web application for managing STRG-related requests across three roles:

- **Admission** submits and revises requests.
- **Staff 1** verifies requests.
- **Staff 2** recommends/finalizes requests.

The system tracks request status changes, internal comments, and audit history.

## Template-Friendly Customization

This project can be reused as a public skeleton while keeping the same workflow logic:

- Rename organization/product labels via environment variables:
  - `SYSTEM_ORGANIZATION`
  - `SYSTEM_PRODUCT_NAME`
  - `SYSTEM_REQUEST_LABEL`
- Toggle dean-facing routes/UI without code edits:
  - `FEATURE_DEAN_INTERFACE=true|false`

## Core Features

- Role-based dashboard for admission and staff workflows.
- Request submission with document upload.
- Revision flow for returned requests.
- Status updates with notes and rejection reasons.
- Internal comments and audit logging.

## Tech Stack

- PHP 8.3+
- Laravel 13
- Blade + Vite
- SQLite/MySQL (configurable via `.env`)

## Quick Start

1. Install dependencies:

   ```bash
   composer install
   npm install
   ```

2. Configure environment:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Run migrations and seeders:

   ```bash
   php artisan migrate --seed
   ```

4. Start development servers:

   ```bash
   composer run dev
   ```

## Demo Accounts (Seeded)

All demo users use password: `password`.

- `admissions@unikl.edu.my`
- `staff1@unikl.edu.my`
- `staff2@unikl.edu.my`

## Quality Checks

- Run test suite:

  ```bash
  php artisan test
  ```

- Format code with Pint:

  ```bash
  ./vendor/bin/pint
  ```

## System Diagnostics (Required)

Use this sequence when verifying the system before QA/UAT or deployment:

1. Run repository QA checks:

   ```bash
   bash scripts/qa_check.sh
   ```

2. Run production readiness diagnostics:

   ```bash
   php scripts/production-readiness-check.php
   ```

3. Run application tests (after dependencies are installed):

   ```bash
   php artisan test
   ```

### Interpreting Diagnostics

- `vendor/autoload.php missing`  
  Install dependencies first:

  ```bash
  composer install
  ```

- `.env file not found`  
  Create environment config:

  ```bash
  cp .env.example .env
  php artisan key:generate
  ```

- Database connection/readiness errors  
  Ensure DB file/connection is configured and run migrations:

  ```bash
  php artisan migrate --seed
  ```

## Request Form Health Checklist

If request form features appear broken (signature pad, VOT totals, dynamic request fields, template preview):

1. Confirm layout renders pushed scripts (`@stack('scripts')` in `resources/views/layouts/app.blade.php`).
2. Hard refresh browser cache (`Ctrl + Shift + R`) after pulling updates.
3. Verify JavaScript loads without syntax errors in browser console.
4. Verify request type has configured field schema/template in admin settings.
5. Re-test submission with:
   - at least one valid VOT row (code + amount > 0),
   - a drawn digital signature,
   - required dynamic fields completed.

## Improvement Roadmap

If you want to make this project better, prioritize these high-impact changes:

1. **Authorization hardening**
   - Add Laravel Policies so users can only view/update requests they are allowed to access.
   - Restrict status changes to valid workflow transitions.

2. **Validation and DTO/FormRequest cleanup**
   - Move controller validation rules into dedicated `FormRequest` classes for `store`, `update`, `updateStatus`, and comments.

3. **Automated tests**
   - Add feature tests for role-based access, status transitions, and revision flow.
   - Add upload validation tests.

4. **Observability**
   - Add activity/event logging around authentication, status updates, and failed authorization attempts.

5. **UX improvements**
   - Add pagination/filter persistence on dashboard.
   - Add clearer status badges and workflow timeline in request detail page.


## For Your Current Setup (Herd + SQLite)

If you are running this on **Laravel Herd** and focusing on finishing dashboard filters + notifications, see:

- [`docs/MAJOR_IMPROVEMENTS.md`](docs/MAJOR_IMPROVEMENTS.md)

It contains a prioritized, implementation-focused roadmap tailored to this project state.

## Security Note

A developer quick-switch login route exists for local development convenience. Keep it disabled in non-local environments and do not expose it in production.
