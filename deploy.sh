#!/usr/bin/env bash
#
# Deploy this release.
#
# Safe to re-run: every step is idempotent, and the site is put into maintenance mode only
# for the window where the code and the schema could disagree.
#
# Usage:  ./deploy.sh [--skip-build]
#
set -euo pipefail

cd "$(dirname "$0")"

SKIP_BUILD=0
[[ "${1:-}" == "--skip-build" ]] && SKIP_BUILD=1

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
fail() { printf '\n\033[1;31m!!  %s\033[0m\n' "$1" >&2; exit 1; }

[[ -f .env ]] || fail ".env is missing. Copy .env.example and fill it in before deploying."

php -r 'exit(version_compare(PHP_VERSION, "8.4", ">=") ? 0 : 1);' \
    || fail "PHP 8.4 or newer is required; this is $(php -r 'echo PHP_VERSION;')."

say "Putting the site into maintenance mode"
# The secret lets whoever is deploying browse the site while it is down for everyone else.
php artisan down --render="errors::503" --secret="deploy-$(date +%s)" || true
trap 'php artisan up || true' EXIT

say "Installing PHP dependencies"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

if [[ $SKIP_BUILD -eq 0 ]]; then
    say "Building front-end assets"
    npm ci
    npm run build
fi

say "Checking the PDF fonts are present"
# An invoice without these renders Bengali as mis-ordered glyphs and loses the ৳ sign.
php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php";
    $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    exit(App\Services\PdfService::fontsAvailable() ? 0 : 1);' \
    || fail "resources/fonts is missing the Hind Siliguri faces; invoices would be unreadable."

say "Running migrations"
php artisan migrate --force

say "Syncing roles, permissions and email templates"
php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder --force
php artisan db:seed --class=Database\\Seeders\\EmailTemplateSeeder --force
php artisan db:seed --class=Database\\Seeders\\OrderSourceSeeder --force
php artisan shield:generate --all --panel=admin --no-interaction || true

say "Linking storage"
php artisan storage:link || true

say "Caching configuration, routes, views and events"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache || true
php artisan filament:optimize || true

say "Refreshing the sitemap"
php artisan sitemap:refresh || true

say "Restarting workers"
# Workers hold the old code in memory until they are told to finish.
php artisan queue:restart

say "Reconciling the wallet ledger"
# Exits non-zero on drift. A deploy that leaves the ledger disagreeing with the balances
# is something you want to hear about now, not in a support ticket.
php artisan wallet:reconcile || fail "Wallet drift detected after deploy — investigate before opening the site."

say "Bringing the site back up"
php artisan up
trap - EXIT

say "Purging the edge cache"
# LiteSpeed holds whole pages. Without this the old markup is served until the TTL expires.
curl -s -o /dev/null -X PURGE "$(php artisan tinker --execute='echo config("app.url");' 2>/dev/null | tail -1)/*" || true

printf '\n\033[1;32mDeployed.\033[0m Run `php artisan schedule:list` to confirm the cron is wired.\n'
