# Sensitive Data + PDF Autofill Guide

## Current system behavior

`TemplateService::generateAutoFilledPdf()` currently copies the selected template file and logs usage.
It does **not** yet inject dynamic values into PDF form fields.

## Safe testing approach

1. Use staging-only storage/database.
2. Use fake identities and non-production data.
3. Upload sanitized templates only.
4. Validate output manually against expected mapped values.
5. Purge test artifacts on schedule.

## Building templates now (1:1 layout)

Yes, you can prepare templates now with near-1:1 visual structure.

Recommended method:

- Keep static labels fixed on the base form.
- Reserve clean blank zones for dynamic values.
- Keep margins and field areas consistent across versions.
- Create a field map table for each template field (example: `user.name`, `request.ref_number`, `request.total_amount`).

## GitHub privacy baseline

- Keep repository private.
- Protect `main` with required review.
- Never commit `.env`, uploads, generated PDFs, exports, or DB dumps.
- Use secret manager / GitHub encrypted secrets for credentials.
- Enable dependency + secret scanning.

## Deployable skeleton strategy

Use a sanitized branch/profile for external collaboration:

- Demo users only.
- Fake request/sample template data only.
- Mail in log/sandbox mode.
- Private storage by default.
- One-command bootstrap steps in README.
