# Vendora — Tailwind HTML template pack

Static HTML/Tailwind v3 + Alpine.js front end for a digital-products store (bKash/Nagad/Rocket/wallet checkout), ready to slice into Laravel Blade + Livewire.
"Vendora" is a placeholder brand. Search-replace it, along with the phone and email.

Open `index.html` in a browser. Everything is local except Google Fonts.

## Pages

| File | Laravel route | Notes |
|---|---|---|
| index.html | `/` | hero slider → category tiles → `show_on_home` category sections (gradient bar + 4-col grid) → testimonials → FAQ → CTA |
| category.html | `/category/{slug}` | chips, sort, in-stock filter, pagination |
| search.html | `/search?q=` | client-side filter in demo; swap for server query |
| product.html | `/product/{slug}` | gallery, tabs (description / delivery / FAQs), related, **JSON-LD Product** in `<head>` |
| cart.html | `/cart` | qty, remove, coupon, totals |
| checkout.html | `/checkout` | wallet (shows balance, disabled if insufficient), manual methods (number + copy + instructions + sender no. + TrxID + screenshot), gateway |
| order-success.html | `/order/{number}/pay` | amount, order no., instructions, manual-payment form, link to portal |
| page.html | `/page/{slug}` | CMS page (How to Buy?) |
| blog.html / blog-detail.html | `/blog`, `/blog/{slug}` | |
| team.html | `/our-story` | stats, story, team grid |
| contact.html | `/contact` | form + contact cards + map |
| login / register / forgot-password / reset-password / verify-email | Breeze/Fortify routes | Google button, referral field |

## Brand colour

Edit the `--brand-*` RGB triplets at the top of `assets/app.css` (default crimson `#dc2626`). You don't need to rebuild anything. In Laravel you can print them from settings:

```blade
<style>:root{ --brand-600: {{ setting('brand_rgb', '220 38 38') }}; }</style>
```

## Rebuilding Tailwind

```bash
npm i -D tailwindcss@3 @tailwindcss/forms @tailwindcss/typography
npx tailwindcss -i ./src/input.css -o ./assets/tailwind.css --minify
```
In Laravel, copy `tailwind.config.js` and point `content` at `resources/views/**/*.blade.php`.
Keep the gradient classes that come from the DB, like `from-rose-500 to-red-700`, in a `safelist`.

## Alpine → Livewire mapping (`assets/app.js`)

- `$store.buy` → `<livewire:buy-now-modal />`. It covers `open(slug)`, the email (prefilled with `auth()->user()->email`), quantity limited by min/max/stock, dynamic `fields`, the coupon, the payment method + instructions, a live total, `checkout()` and `addToCart()`.
- `$store.cart` → a session cart component. `checkoutPage()` → `CheckoutPage` component. `payPage()` → the pay page.
- `$store.user` → `@auth` / `@guest`. For the demo, add `?guest=1` or `?auth=1` to any URL.
- Demo coupons: `SAVE10` (10%, min ৳500) and `FLAT100` (৳100, min ৳1000).

## Server-side notes

- **Rate limits:**
  - `Route::middleware('throttle:login')` for login (a 5/min RateLimiter keyed on email+IP)
  - `throttle:6,1` for register and email verification
  - `throttle:3,1` for forgot-password
  - `throttle:5,1` for the contact form
- **Referral:** a middleware saves `?ref=` into `Cookie::queue('ref', $code, 60*24*30)`, and register prefills `request()->cookie('ref')`. The JS demo does the same thing.
- **Google login:** Socialite. Wrap the button in `@if(setting('google_login_enabled'))`.
- **Always re-validate on the server:** wallet balance, stock, min/max and the coupon.
- Comments like `<!-- @foreach(...) -->` mark the dynamic menus, categories, footer columns and loops.
