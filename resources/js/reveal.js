/**
 * Scroll arrival and counting figures.
 *
 * Two effects, one observer:
 *
 *   .reveal     — the element starts displaced (see storefront.css) and settles into place
 *                 the first time it is scrolled to. It is never re-hidden: a section that
 *                 has arrived stays arrived, so scrolling back up does not replay the page.
 *
 *   .count-up   — a figure counts from zero to its printed value on arrival. The target is
 *                 read out of the rendered text rather than an attribute, so the server
 *                 stays the single source of every number and a formatted value
 *                 ("1,240", "৳ 950") survives with its punctuation intact.
 *
 * Both are progressive: without JavaScript the .no-js rule shows everything, and anyone
 * who asks for reduced motion gets the final state immediately, because the CSS neutralises
 * the transition and this module skips the count entirely.
 */

const REDUCED = window.matchMedia("(prefers-reduced-motion: reduce)");

/** Splits "৳ 1,240+" into its numeric value and the text either side of it. */
function parseFigure(text) {
    const match = text.match(/-?[\d][\d,.\s]*/);

    if (!match) return null;

    const raw = match[0];
    const value = Number(raw.replace(/[,\s]/g, ""));

    if (!Number.isFinite(value)) return null;

    return {
        value,
        prefix: text.slice(0, match.index),
        suffix: text.slice(match.index + raw.length),
        decimals: (raw.split(".")[1] || "").length,
        grouped: raw.includes(","),
    };
}

function countUp(el) {
    const figure = parseFigure(el.textContent.trim());

    if (figure === null || figure.value === 0) return;

    const format = (n) => {
        const fixed = n.toFixed(figure.decimals);

        return figure.grouped
            ? Number(fixed).toLocaleString("en-US", { minimumFractionDigits: figure.decimals })
            : fixed;
    };

    // A fixed duration regardless of magnitude: 40 and 12,000 should finish together.
    const duration = 1100;
    const start = performance.now();

    const step = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        // Ease-out cubic: fast enough to feel instant, slow enough to be read.
        const eased = 1 - Math.pow(1 - progress, 3);

        el.textContent = figure.prefix + format(figure.value * eased) + figure.suffix;

        if (progress < 1) requestAnimationFrame(step);
    };

    el.textContent = figure.prefix + format(0) + figure.suffix;
    requestAnimationFrame(step);
}

function arrive(el) {
    el.classList.add("is-in");

    el.querySelectorAll(".count-up").forEach(countUp);

    if (el.classList.contains("count-up")) countUp(el);
}

let observer = null;

function watch(root = document) {
    const targets = root.querySelectorAll(".reveal:not(.is-in), .count-up:not(.is-in)");

    if (targets.length === 0) return;

    // No observer support, or motion turned down: show everything as it is.
    if (!("IntersectionObserver" in window) || REDUCED.matches) {
        targets.forEach((el) => el.classList.add("is-in"));

        return;
    }

    observer ??= new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                arrive(entry.target);
                observer.unobserve(entry.target);
            });
        },
        // Fire a little before the element is fully on screen, so the movement is over by
        // the time it is properly in view.
        { rootMargin: "0px 0px -8% 0px", threshold: 0.08 },
    );

    targets.forEach((el) => observer.observe(el));

    // Stagger delays are declared in CSS but indexed here, so a Blade loop does not have to
    // print a style attribute on every child.
    root.querySelectorAll(".stagger").forEach((group) => {
        Array.from(group.children).forEach((child, index) => {
            child.style.setProperty("--i", String(index));
        });
    });
}

document.documentElement.classList.remove("no-js");

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => watch());
} else {
    watch();
}

// Livewire swaps chunks of DOM in place — a re-rendered cart or checkout summary brings new
// .reveal elements with it, and they have never been observed.
document.addEventListener("livewire:navigated", () => watch());
document.addEventListener("livewire:update", () => watch());

export { watch };
