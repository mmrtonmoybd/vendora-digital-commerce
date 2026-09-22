/**
 * Vendora storefront behaviour.
 *
 * This is design/vendora/assets/app.js with the demo removed: no localStorage cart, no fake
 * auth toggle, no client-side order numbers. Those moved to the server — the cart is a
 * database row, auth is a session, and order numbers are issued by CheckoutService.
 *
 * What stays is the purely visual part: the toast store, the clipboard helper and the money
 * formatter the template's markup already calls.
 *
 * Alpine is not imported here. Livewire ships its own copy, and loading a second one makes
 * every x-data initialise twice.
 */
import './bootstrap'
import './tracking'
import './hydrate'

const currency = document.documentElement.dataset.currency || '৳'

/** Matches the template's money(): whole taka, grouped, no decimals. */
const money = (paisa) => currency + ' ' + Math.round(paisa / 100).toLocaleString('en-US')
window.money = money

window.readCookie = (name) =>
    (document.cookie.match('(^|;)\\s*' + name + '=([^;]*)') || [])[2] || ''

window.copyText = (text) => {
    try {
        navigator.clipboard.writeText(text)
        window.Alpine?.store('toast')?.show('Copied: ' + text)
    } catch (e) {
        /* Clipboard is unavailable over plain HTTP; the number is on screen anyway. */
    }
}

document.addEventListener('alpine:init', () => {
    window.Alpine.store('toast', {
        msg: '',
        on: false,
        t: null,
        show(m) {
            this.msg = m
            this.on = true
            clearTimeout(this.t)
            this.t = setTimeout(() => (this.on = false), 2600)
        },
    })
})

// Livewire components raise this instead of touching the store directly.
document.addEventListener('livewire:init', () => {
    window.Livewire.on('toast', (event) => {
        const message = Array.isArray(event) ? event[0]?.message : event?.message
        window.Alpine?.store('toast')?.show(message ?? '')
    })
})
