# Vendora — Digital Commerce

> **Proprietary software. All rights reserved.**
> Possessing a copy of this repository grants you no rights. You may not use, run, host,
> deploy, copy, modify or distribute this software — for any purpose, including internal
> or evaluation use — without the **prior written permission** of the author.
> Unauthorised use is unlawful. Requests: **rmedha037@gmail.com**. See [LICENSE](LICENSE).

A production storefront and back office for selling digital products that a human
fulfils by hand: streaming subscriptions, software licences, game top-ups, gift cards —
anything where the customer buys access to an account on someone else's platform rather
than a file to download.

Built for the Bangladeshi market: prices in taka, bKash/Nagad/Rocket manual transfers
alongside shurjoPay cards, and the whole storefront readable in Bengali or English.

**Author:** Moshiur Rahman &lt;rmedha037@gmail.com&gt;
**Stack:** Laravel 13 · PHP 8.4 · Filament 5 · Livewire 4 · MariaDB · Tailwind 4

---

## Contents

- [What makes it different](#what-makes-it-different)
- [The delivery model](#the-delivery-model)
- [Feature map](#feature-map)
- [Architecture decisions that shape the code](#architecture-decisions-that-shape-the-code)
- [Getting it running](#getting-it-running)
- [Configuration](#configuration)
- [Testing and quality gates](#testing-and-quality-gates)
- [Deployment](#deployment)
- [Scheduled work](#scheduled-work)
- [Project layout](#project-layout)
- [Known gaps](#known-gaps)
- [Licence](#licence)

---

## What makes it different

Four decisions run through everything and explain most of the code you'll read.

**Money is an integer number of paisa. Everywhere.**
`৳1,450.00` is `145000`. No float touches a price, a fee, a commission or an exchange
rate — `0.1 + 0.2` is not `0.3`, and a wallet that drifts by a paisa per thousand
transactions is a wallet nobody can reconcile. See `app/Support/Money.php`.

**The wallet is an append-only ledger.**
`WalletService` is the only code allowed to move a balance. Every movement takes a row
lock on the user for the duration of the transaction and carries an idempotency key, so
a retried job, a double-clicked button or a replayed gateway callback credits exactly
once. `WalletTransaction` throws if you try to update or delete a row. A nightly command
recomputes every balance from its ledger and exits non-zero on drift.

**Every payment is verified server-to-server.**
A redirect back from the gateway is treated as a hint, never as proof. `ShurjopayService`
locks the payment row, checks the status code, the amount to the paisa, the currency and
the merchant order id before anything settles. A mismatch marks the payment failed and
writes to a dedicated security log.

**The storefront is a slice, not a redesign.**
`design/vendora/*.html` is the source of truth for the markup. Blade may add expressions,
loops, conditionals and form attributes — it may not invent a Tailwind class.
`scripts/check-classes.php` enforces that on every CI run: 635 classes in the template,
eight documented exceptions, nothing else.

## The delivery model

There are no file downloads. When an order is paid:

1. The order item enters the fulfilment queue at `/admin/access-queue`, assigned
   round-robin to a staff member holding the `fulfill_orders` permission.
2. Staff open the platform's own business manager and invite the customer's
   **delivery email** to the account.
3. The customer gets an email, accepts the invite, and confirms receipt — or reports a
   problem, which opens a support ticket automatically.

An SLA timer runs from the moment of payment and turns amber then red; breaches are
flagged. Warranty windows are recorded per item, and access can be re-invited, replaced
or revoked with a full event trail.

The **access reference** — the internal identifier of the account the customer was added
to — is never shown in the customer panel and never appears in an email. Reading it is
its own permission.

## Feature map

### Storefront
Home with hero slider, categories, featured products, testimonials and FAQs · category
and product pages with per-product custom fields · search · blog · CMS pages · cart ·
guest or account checkout · buy-now modal · order tracking with payment submission ·
contact form · full SEO (meta, Open Graph, Twitter cards, JSON-LD, sitemap, robots) ·
Meta, TikTok and Google pixel events (`view_content`, `add_to_cart`, `initiate_checkout`,
`purchase`, `search`, `sign_up`, `contact`) · traffic-source attribution from click ids,
UTM parameters and referrer.

### Customer panel (`/customer`)
Orders and access status · wallet with ledger · deposits · invoices · support tickets ·
referral dashboard and withdrawals · profile with two-factor authentication and passkeys.

### Admin panel (`/admin`)
32 resources and 25 pages, including: orders with a full state machine, manual payment
approval, partial and full refunds · the access queue · customers, staff, roles and
permissions (Filament Shield) · catalogue, categories, platforms, coupons · wallet
ledger and manual deposits · invoices, commissions, withdrawals · tickets and canned
responses · CMS, blog, menus, hero slides, FAQs, team, testimonials · six report screens
· seventeen settings groups covering every credential, toggle and piece of copy.

### Money movement
Wallet · manual mobile-banking transfers with proof submission and staff verification ·
shurjoPay cards and net banking · coupons (percentage and fixed) · payment fees in basis
points · referral commissions with approval and reversal · withdrawals.

### Internationalisation
Bengali and English across the storefront, customer panel, validation messages and
invoice. Browser detection, a per-visitor switcher, and admin control over which
languages exist and which is default. Administrator-written content — product names, page
bodies, homepage copy, menu labels — gets its own translations through a side table, so
enabling a language never blanks a shop that has not been translated yet.

### Currency
Taka is the shop's money and cannot be turned off: everything is stored, charged and
refunded in paisa, and the gateway settles in BDT. US dollars can be enabled as a
**display** currency — a presentation of the same number, never a second set of prices.
The invoice is always in taka, and checkout says so before the customer commits.

## Architecture decisions that shape the code

Recorded in full in [`docs/DECISIONS.md`](docs/DECISIONS.md). The ones you will notice
first:

**LiteSpeed page cache and Livewire do not mix — so the cached HTML carries nothing
personal.** Public pages are rendered identically for every signed-out visitor: no CSRF
token, no cart count, no name, no balance. A `no-store` JSON endpoint at
`/storefront/state` supplies all of that after paint, and `resources/js/hydrate.js`
writes it into the DOM. Livewire reads the CSRF token from the live DOM on every request,
so it keeps working.

Once a second language or currency is enabled, the same URL renders differently, so both
join the LiteSpeed vary cookie and anonymous visitors split into one cached variant per
combination. That cookie is **excluded from Laravel's cookie encryption** — encryption
uses a random IV, so an encrypted vary cookie would hold a different value for every
visitor and the cache would never serve a hit.

**shurjoPay is driven over REST directly.** The vendor package calls `header()` and
`exit()` inside its flow, which makes it impossible to test and impossible to recover
from. `ShurjopayService` speaks to the API with Laravel's HTTP client instead, caches the
token under its validity window, and scrubs credentials before persisting any payload.

**mPDF, not dompdf, for invoices.** dompdf has no OpenType layout, so it draws Bengali in
logical order — "ইনভয়েস" comes out as "ইনভয়সে", with the pre-base vowel after the final
consonant and no conjuncts. Hind Siliguri is bundled in `resources/fonts` because mPDF's
OTL parser rejects Noto Sans Bengali's GPOS table.

**Tests run on MySQL, never SQLite.** The money guarantees rest on `SELECT … FOR UPDATE`,
which SQLite ignores. A concurrency test passing on SQLite would prove nothing.

**Two separate auth stacks.** `users`/`User`/guard `web` for customers,
`admins`/`Admin`/guard `admin` for staff — separate tables, models, providers, password
brokers and panels. A customer cannot become an administrator by changing a column.

## Getting it running

### Requirements

PHP 8.4 · Composer 2 · Node 22 · MySQL 8 or MariaDB 11 · a writable `storage/`.
Redis is optional — the defaults use the database for cache, queue and session.

### Install

```bash
git clone git@github.com:mmrtonmoybd/vendora-digital-commerce.git
cd vendora-digital-commerce

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Point `DB_*` at an empty database, then:

```bash
php artisan migrate --seed
npm run build
```

`migrate --seed` creates the roles and permissions, the email templates, the traffic
sources and the first administrator. Set `SEED_DEMO=true` before seeding to also load the
demo catalogue, which is parsed from `design/vendora/assets/data.js` so prices, stock and
badges match the template exactly.

The first administrator's credentials come from `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Leave
the password unset and one is generated and printed once — copy it.

```bash
php artisan storage:link
php artisan serve
```

Storefront at `/`, customer panel at `/customer`, admin at `/admin`.

### Demo images

```bash
php artisan demo:images
```

Draws an SVG thumbnail for every seeded record from its own gradient and icon. No network
calls, no dead placeholder URLs.

## Configuration

**Nothing is hard-coded.** Every credential, key, toggle, menu, price and piece of copy is
editable in the admin panel under Settings, stored through `spatie/laravel-settings` with
the sensitive fields encrypted at rest. `.env` holds only what is needed to reach the
database and boot the application.

Seventeen settings groups: General · Theme · Contact · Commerce · Currency · Locale ·
Homepage · SEO · Mail · SMS · Telegram · shurjoPay · Auth · Security · Fulfillment ·
Affiliate · Notifications.

That includes the SMTP credentials — mail is configured from the panel at runtime, not
from `.env` — and a raw-HTML field for head and body, so marketing can paste a tag
without a deploy.

## Testing and quality gates

```bash
composer test          # Pest
composer lint          # Pint
composer stan          # Larastan
composer parity        # template class-vocabulary guard
composer test:all      # all four, in that order
```

**158 tests, 390 assertions.** They cover the wallet under concurrent writes, stock races,
the order state machine, shurjoPay verification including the mismatch paths, checkout,
customer isolation, scheduled commands, localisation, currency, slug redirects, invoices,
and a render pass over every resource and page in both panels with real records loaded.

That last one exists because an index page draws a table and nothing else — three latent
fatals were sitting behind forms and infolists that no test had ever opened.

CI runs the same four gates against MySQL on every push and pull request.

## Deployment

```bash
./deploy.sh
```

Re-runnable. Puts the site in maintenance mode only for the window where the code and the
schema could disagree, installs production dependencies, builds assets, verifies the
bundled PDF fonts are present, migrates, syncs roles and email templates, caches
config/routes/views/events, restarts workers, **reconciles the wallet ledger and refuses
to finish if it has drifted**, then brings the site up and purges the edge cache.

`public/.htaccess` carries the LiteSpeed configuration: cache lookup, the vary cookie,
campaign-parameter stripping so `?fbclid=…` does not fragment the cache, a bypass for
every private path, asset expiry, `no-store` on PDFs, and a deny rule for dotfiles.

## Scheduled work

`routes/console.php`. Run `php artisan schedule:list` to confirm.

| Cadence | Command | Purpose |
|---|---|---|
| every 5 min | `shurjopay:reconcile` | Chase payments the gateway never confirmed |
| every 5 min | `orders:cancel-unpaid` | Release stock held by abandoned orders |
| every 5 min | `access:process` | Auto-confirm stale grants, flag SLA breaches |
| daily 03:00 | `wallet:reconcile` | Recompute every balance from its ledger; non-zero exit on drift |
| daily 04:00 | `sitemap:refresh` | Rebuild the sitemap cache |
| daily | `backup:run`, `backup:clean` | Database and file backups |
| weekly | `activitylog:clean`, `queue:prune-failed` | Housekeeping |

## Project layout

```
app/
  Filament/Admin/       32 resources, 25 pages, widgets, reports
  Filament/Customer/    the customer's own panel
  Services/             22 services — all business rules live here, not in controllers
  Support/              Money, BrandPalette, Locales, Currencies
  Http/Middleware/      locale, currency, source capture, LiteSpeed cache and vary
  Settings/             17 settings groups
design/vendora/         the HTML template the storefront is sliced from — do not edit
resources/views/        pages, store components, livewire views, the invoice
resources/fonts/        Hind Siliguri, bundled for PDF rendering
scripts/                check-classes.php, the template parity guard
docs/                   DECISIONS.md and the access-delivery addendum
```

70 tables, 22 migrations, 25 enums.

## Known gaps

Stated plainly rather than discovered later:

- **Larastan is not clean at level 6.** 195 errors remain, down from 479. Almost all are
  enum-cast inference — Larastan does not read casts declared in a `casts()` method on a
  model using `$guarded`. The fix is `@property` blocks on the models.
- **No visual-regression suite.** `check-classes.php` guards the class vocabulary; it does
  not compare rendered pixels. A Playwright pass against `design/vendora` is the next step.
- **The admin panel stays in English.** The toggle exists
  (Settings → Languages → *Also in the admin panel*) and is off by default; the staff-facing
  strings are not extracted yet.
- **Tags are managed but not rendered.** The Vendora template has no tag UI, and inventing
  one would break the parity rule, so they are an editorial tool only.
- **Docs are partial.** `docs/DECISIONS.md` and the access-delivery addendum exist;
  the payments walkthrough, admin guide and environment reference do not.

## Licence

**Proprietary. All rights reserved.** © 2026 Moshiur Rahman &lt;rmedha037@gmail.com&gt;

This is not open-source software and it is not free to use. No licence arises from having
a copy, from forking it, from cloning it, or from having worked on it. Every use — running
it, hosting it, deploying it, copying it, modifying it, reselling it, or disclosing its
source to anyone else — requires the author's **prior written permission**, obtained in
advance and for each intended use. Permission granted for one deployment or client does
not extend to another.

Unauthorised use, copying or distribution is unlawful and may give rise to civil liability
and criminal penalties. Full terms in [LICENSE](LICENSE).

**To request permission: rmedha037@gmail.com**

Two bundled third-party licences are worth knowing about: **mPDF** is GPL-2.0-only and
renders the invoices server-side, and **Hind Siliguri** is under the SIL Open Font
License 1.1. Neither grants any right in this software.
