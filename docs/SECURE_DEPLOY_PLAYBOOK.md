# Secure Deployment & PDF Autofill Playbook

This markdown file replaces the old in-app "Secure Deploy" page.

## Current autofill behavior

`TemplateService::generateAutoFilledPdf()` currently copies template files and logs usage.
It does not inject dynamic text fields yet.

## Safe test approach (sensitive data)

1. Use staging-only DB + storage.
2. Use synthetic/fake identity data.
3. Upload sanitized templates only.
4. Purge generated files after tests.
5. Keep all secrets in `.env` and secret managers.

## Open-source skeleton mode

- Keep `.env` out of git (already ignored).
- Keep `.env.example` as public template.
- Use `./scripts/devbox_skeleton.sh` for local setup.
- Seed demo-only data.

## Recommended deploy refresh steps

If route/view issues appear after deploy:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```
