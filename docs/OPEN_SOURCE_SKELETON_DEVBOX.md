# Open-Source Skeleton / DevBox Plan

Yes — your idea is fully possible.

You can publish this as a polished **workflow skeleton** so anyone can clone, set their own purpose, and adapt labels/fields without receiving your sensitive operational data.

## 1) About `.env` and `.gitignore`

You should always keep local env files out of git.

This repo now ignores:

- `.env`
- `.env.*` (such as `.env.local`, `.env.staging`, `.env.production`)
- while keeping `.env.example` tracked.

Use `.env.example` as the public template. Users fill their own real values locally.

## 2) DevBox bootstrap for adopters

Use:

```bash
./scripts/devbox_skeleton.sh
```

It will:

1. Create `.env` from `.env.example` (if missing),
2. Prompt for app name and URL,
3. Configure SQLite local DB by default,
4. Print the next setup commands.

## 3) How to ship a safe open-source skeleton

1. Replace real organization names with neutral branding.
2. Seed demo-only users/data.
3. Include sanitized sample templates only.
4. Keep mail in non-production mode for sample setup.
5. Keep uploads and generated PDFs private/non-committed.

## 4) Suggested extensibility model

For your “purpose input” idea, expose configuration in one place:

- App name and org labels in env/config,
- Request type labels in admin UI,
- Template maps in DB,
- Role names and workflow statuses in enum/config.

This lets adopters rebrand and repurpose without touching core workflow logic.
