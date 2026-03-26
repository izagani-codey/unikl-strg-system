#!/usr/bin/env bash
set -euo pipefail

echo "[1/5] PHP syntax lint (app, routes, database, tests)"
find app database routes tests -name '*.php' -print0 | xargs -0 -n1 php -l > /tmp/qa_php_lint.out

echo "[2/5] Search for merge conflict markers"
if rg -n "<<<<<<<|>>>>>>>" app resources routes database tests; then
  echo "Merge conflict markers found"
  exit 1
fi

echo "[3/5] Check for TODO/FIXME hotspots"
rg -n "TODO|FIXME" app resources routes || true

echo "[4/5] Composer autoload presence"
if [[ ! -f vendor/autoload.php ]]; then
  echo "WARN: vendor/autoload.php missing. Run composer install before php artisan test"
else
  echo "vendor/autoload.php present"
fi

echo "[5/5] Test command hint"
echo "Run: php artisan test --stop-on-failure"
echo "Run: php artisan test --filter AuthenticationTest"

echo "QA check completed."
