#!/usr/bin/env bash
set -euo pipefail

# Simple bootstrapper for open-source "skeleton/dev box" setup.
# Safe by default: creates local .env from .env.example and uses local sqlite unless changed.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "== UniKL STRG Skeleton DevBox Bootstrap =="

if [[ ! -f ".env" ]]; then
  cp .env.example .env
  echo "Created .env from .env.example"
else
  echo ".env already exists (keeping current file)"
fi

read -rp "APP_NAME [Workflow Skeleton]: " APP_NAME
APP_NAME=${APP_NAME:-Workflow Skeleton}

read -rp "APP_URL [http://localhost]: " APP_URL
APP_URL=${APP_URL:-http://localhost}

if [[ "$(uname -s)" == "Darwin" ]]; then
  sed -i '' "s|^APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|" .env
  sed -i '' "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
else
  sed -i "s|^APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|" .env
  sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
fi

echo "Configured APP_NAME and APP_URL in .env"

if ! grep -q '^DB_CONNECTION=' .env; then
  echo 'DB_CONNECTION=sqlite' >> .env
fi

if grep -q '^DB_CONNECTION=sqlite' .env; then
  mkdir -p database
  touch database/database.sqlite
  echo "Prepared SQLite database: database/database.sqlite"
fi

cat <<'MSG'

Next steps:
  1) composer install
  2) npm install
  3) php artisan key:generate
  4) php artisan migrate --seed
  5) composer run dev

Security note:
  - Keep .env local only (already gitignored).
  - Never commit real uploads, generated PDFs, or DB dumps.
MSG
