# Decisions

Choices that were not obvious, and why they were made.

## LiteSpeed page cache and Livewire

**Problem.** LiteSpeed caches a whole rendered page and replays it to the next visitor.
Livewire embeds a CSRF token and a component snapshot in that HTML. Cached naively, visitor
B receives visitor A's CSRF token (every Livewire action and every form POST then fails with
419) and visitor A's component state (a cart badge reading "3" shown to someone with an
empty cart).

**What was checked.** Two facts in the installed Livewire (4.4) decide the shape of the fix:

- `getCsrfToken()` is called from a lazy getter on the request payload and reads
  `meta[name="csrf-token"]` from the live DOM, not from a value captured at boot. Rewriting
  that tag after load is therefore enough to make Livewire work on a cached page.
- Snapshot checksums are `hash_hmac('sha256', …, app('encrypter')->getKey())` — signed with
  `APP_KEY`, not with the session. A snapshot is valid for every visitor, so a component
  that holds no per-visitor state caches safely.

**Decision.** Cached HTML contains nothing personal; the personal layer arrives over AJAX.

- `GET /storefront/state` returns the CSRF token, cart count, sign-in state and wallet
  balance, and is itself `no-cache`.
- `resources/js/hydrate.js` fetches it once after paint and writes the CSRF token into the
  meta tag *and* every `input[name="_token"]`, fills `[data-cart-count]`, reveals whichever
  of `[data-auth-only]` / `[data-guest-only]` applies, and fills the name, email and balance.
- The cart counter is no longer a Livewire component — it was the one piece of cached markup
  that carried state. It is a plain `<span data-cart-count>`.
- The header's signed-in and signed-out blocks are both rendered, both marked `x-cloak`, and
  revealed by the hydration script. `x-cloak` is the template's own mechanism for this; the
  static template hid the same two blocks the same way while Alpine decided.
- The buy-now modal stays a Livewire component. At rest its snapshot is
  `{show:false, productId:null, email:"", …}` — byte-identical for every visitor — and its
  first interaction is a POST that carries the refreshed token.
- `CachePublicStorefront` caches by a **route-name allowlist** (home, category, product,
  search, blog, CMS pages, sitemap, robots). Cart, checkout, order pages, the auth screens
  and both panels are `no-cache`. An allowlist means a new route is private until someone
  deliberately makes it public, which is the safer default for this mistake.

**Cost.** The cart badge and account pill appear one short round trip after paint rather
than in the first byte. That is the price of serving the same HTML to everyone, and it is
why those elements were already `x-cloak`ed in the template.

## shurjoPay driven over REST rather than through the vendor plugin

`shurjomukhi/shurjopay-plugin-php` calls `header('Location: …'); exit;` inside
`makePayment()`, and `exit`s again on an authentication failure. That cannot be called from
a controller, cannot return a `RedirectResponse`, and cannot be tested. The package was
removed and its three documented endpoints (`/api/get_token`, `/api/secret-pay`,
`/api/verification`) are called with Laravel's HTTP client instead, which gives a real
redirect, `Http::fake()` in tests, and structured logging with the password masked.

## Tailwind v4 for the storefront

The Vendora template ships a Tailwind v3 config. On request, the whole application now
builds with v4, so that config was ported to v4's CSS-first form in
`resources/css/storefront.css` with the same tokens. Two v4 default changes affect this
markup and are restored in a compatibility layer: the default border colour (v4 uses
`currentColor`; the template relies on v3's grey in 84 bare `border`/`border-t`/`border-b`
uses) and the default placeholder colour.

## Uploads are plain paths, not a media library

Product images, logos, payment screenshots and ticket attachments are stored as path
columns on the owning row. Screenshots and attachments live on the `local` (private) disk
and are served through a signed route that re-checks ownership. A media library would add a
conversions queue and an image driver for no gain here — there is one image per product and
a handful of private uploads.

## Cache deserialization is allowlisted, not disabled

Laravel 13 refuses to deserialize objects out of the cache unless they are named in
`cache.serializable_classes`, whose default is `false`. The storefront caches menus,
categories and products as Eloquent collections, so those classes are listed explicitly
rather than turning the protection off.

## Money is integer paisa

Every amount is an integer number of paisa. Rates and fees are basis points. No float
touches a price, a balance or a commission anywhere in the system.

## Ad Account Portal removed

Dropped at the client's request after the schema was written. The tables, models, enums and
settings were removed rather than left dormant.

## Access-grant delivery replaces downloads

See `docs/ADDENDUM_ACCESS_DELIVERY.md`. Nothing is downloaded: an admin grants access on the
platform to the email the customer typed at checkout. There are no code pools, product
files, download routes or tokens.
