/**
 * Fills in the parts of a cached page that belong to one visitor.
 *
 * The storefront's public pages are cached whole by LiteSpeed, so the HTML that reaches a
 * browser was rendered for somebody else. Every personal value is therefore left out of
 * that HTML and written in here after the page paints:
 *
 *   - the CSRF token, both in the meta tag Livewire reads and in every form's hidden input
 *   - the cart count badge
 *   - which of the signed-in / signed-out header blocks to reveal
 *   - the customer's name, email and wallet balance
 *
 * Livewire calls getCsrfToken() lazily on each request and reads the meta tag from the live
 * DOM, so refreshing that tag is enough for its components to work on a cached page.
 */

const STATE_URL = "/storefront/state";

function applyCsrf(token) {
    if (!token) return;

    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) meta.setAttribute("content", token);

    // Plain forms carry their own hidden token, which is just as stale as the meta tag was.
    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
}

function applyCartCount(count) {
    document.querySelectorAll("[data-cart-count]").forEach((node) => {
        node.textContent = String(count);
    });

    // The header badge only exists when there is something in the cart, matching the
    // template, which renders it under x-show="$store.cart.count".
    document.querySelectorAll("[data-cart-badge]").forEach((node) => {
        node.toggleAttribute("x-cloak", count < 1);
    });
}

function applyIdentity(state) {
    const selector = state.authenticated ? "[data-auth-only]" : "[data-guest-only]";

    document.querySelectorAll(selector).forEach((node) => {
        node.removeAttribute("x-cloak");
    });

    if (!state.user) return;

    const fields = {
        "[data-user-name]": state.user.name,
        "[data-user-email]": state.user.email,
        "[data-user-balance]": state.user.balance,
    };

    Object.entries(fields).forEach(([selector, value]) => {
        document.querySelectorAll(selector).forEach((node) => {
            node.textContent = value;
        });
    });
}

export async function hydrateStorefront() {
    try {
        const response = await fetch(STATE_URL, {
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
            credentials: "same-origin",
        });

        if (!response.ok) return;

        const state = await response.json();

        applyCsrf(state.csrf);
        applyCartCount(state.cart_count ?? 0);
        applyIdentity(state);

        document.dispatchEvent(new CustomEvent("storefront:hydrated", { detail: state }));
    } catch (e) {
        // A failed hydration must never break the page. The visitor still sees the shop;
        // they just see it as a signed-out visitor with an empty cart until the next load.
    }
}

document.addEventListener("DOMContentLoaded", () => hydrateStorefront());

// Livewire actions that change the cart ask for a fresh count rather than re-rendering a
// component that would otherwise have to be excluded from the cache.
document.addEventListener("livewire:init", () => {
    window.Livewire.on("cart-updated", () => hydrateStorefront());
});

window.hydrateStorefront = hydrateStorefront;
