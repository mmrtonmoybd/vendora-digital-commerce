/**
 * "Load more" for the catalogue lists.
 *
 * The category and search pages are paginated on the server and cached whole at the edge,
 * one URL per page — that is what keeps them fast, and it is worth keeping. So this does
 * not replace pagination, it drives it: the button is a real link to the next page, and
 * this module intercepts the click, fetches that page, and appends its cards to the grid
 * the visitor is already looking at.
 *
 * Because the anchor is a genuine ?page=2 link, everything still works with JavaScript
 * off or broken — the visitor simply gets page two as a page. Crawlers follow the same
 * link, so the whole catalogue stays reachable.
 */

import { watch } from "./reveal";

const GRID = "[data-product-grid]";
const BUTTON = "[data-load-more]";

/** Swaps the button between resting and working, without changing its width. */
function setBusy(button, busy) {
    button.classList.toggle("pointer-events-none", busy);
    button.querySelector("[data-load-more-rest]")?.toggleAttribute("hidden", busy);
    button.querySelector("[data-load-more-busy]")?.toggleAttribute("hidden", !busy);
}

async function loadNext(button) {
    const url = button.getAttribute("href");
    const grid = document.querySelector(GRID);

    if (!url || grid === null) return;

    setBusy(button, true);

    try {
        const response = await fetch(url, { headers: { "X-Requested-With": "fetch" } });

        if (!response.ok) throw new Error(String(response.status));

        const doc = new DOMParser().parseFromString(await response.text(), "text/html");
        const cards = doc.querySelector(GRID)?.children;

        if (!cards || cards.length === 0) {
            button.remove();

            return;
        }

        // The cards arrive marked .reveal; they are appended already displaced and the
        // observer settles them, so new rows animate in the same way the first ones did.
        grid.append(...Array.from(cards));
        watch(grid);

        // Where the fetched page's own button points is where this one points next.
        const next = doc.querySelector(BUTTON)?.getAttribute("href");

        if (next) {
            button.setAttribute("href", next);
            setBusy(button, false);
        } else {
            button.remove();
        }

        // The address bar follows what is on screen, so a reload or a shared link lands on
        // the last page the visitor actually pulled in.
        window.history.replaceState({}, "", url);
    } catch (e) {
        // Anything went wrong — let the browser do what the link says instead.
        setBusy(button, false);
        window.location.href = url;
    }
}

document.addEventListener("click", (event) => {
    const button = event.target.closest(BUTTON);

    if (button === null) return;

    event.preventDefault();
    loadNext(button);
});
