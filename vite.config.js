import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

/**
 * One Tailwind v4 pipeline for the whole application: the storefront and the Filament
 * panel theme. The Vendora template's v3 config has been ported to CSS-first form in
 * resources/css/storefront.css, including a compatibility layer for the v3 defaults the
 * template's markup relies on.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/storefront.css",
                "resources/js/storefront.js",
                "resources/css/filament/admin/theme.css",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        /*
         * Bind to IPv4 explicitly.
         *
         * Node resolves "localhost" to ::1 on this platform, so the hot file ends up
         * holding http://[::1]:5173 — and a Content-Security-Policy source list cannot
         * express an IPv6 literal at all. The browser discards that entry as invalid and
         * blocks the dev server's stylesheet and entry script, which renders the storefront
         * unstyled with no Alpine stores registered.
         */
        host: "127.0.0.1",
        watch: { ignored: ["**/storage/framework/views/**"] },
    },
});
