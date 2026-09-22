/** Vendora — Tailwind v3 config.
 *  Rebuild CSS:  npx tailwindcss -i ./src/input.css -o ./assets/tailwind.css --minify
 *  In Laravel: point `content` at resources/views/**\/*.blade.php and resources/js/**\/*.js
 *  Brand colours are CSS variables (assets/app.css) so you can re-skin without rebuilding.
 */
const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];
module.exports = {
  content: ['./*.html', './assets/*.js'],
  theme: {
    extend: {
      colors: {
        brand: Object.fromEntries(shades.map((n) => [n, `rgb(var(--brand-${n}) / <alpha-value>)`])),
        surface: 'rgb(var(--surface) / <alpha-value>)',
        ink: 'rgb(var(--ink) / <alpha-value>)',
      },
      fontFamily: {
        sans: ['"Hind Siliguri"', 'system-ui', 'sans-serif'],
        display: ['Poppins', 'system-ui', 'sans-serif'],
        script: ['"Kaushan Script"', 'cursive'],
      },
    },
  },
  plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
