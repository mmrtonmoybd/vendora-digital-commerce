import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

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
                'resources/css/storefront.css',
                'resources/js/storefront.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
})
