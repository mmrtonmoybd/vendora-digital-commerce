/**
 * Commerce events for Meta, TikTok and Google.
 *
 * The three platforms name the same events differently, so each call site raises one
 * neutral event and this file maps it. Everything is guarded: a shop with no TikTok pixel
 * simply skips that branch rather than throwing on an undefined `ttq`.
 *
 * Payloads are rendered into the page by TrackingService and read from data attributes, so
 * the numbers reported are exactly the numbers the server calculated.
 */

const EVENT_MAP = {
    view_content: { meta: 'ViewContent', tiktok: 'ViewContent', ga: 'view_item' },
    add_to_cart: { meta: 'AddToCart', tiktok: 'AddToCart', ga: 'add_to_cart' },
    initiate_checkout: { meta: 'InitiateCheckout', tiktok: 'InitiateCheckout', ga: 'begin_checkout' },
    add_payment_info: { meta: 'AddPaymentInfo', tiktok: 'AddPaymentInfo', ga: 'add_payment_info' },
    purchase: { meta: 'Purchase', tiktok: 'CompletePayment', ga: 'purchase' },
    search: { meta: 'Search', tiktok: 'Search', ga: 'search' },
    sign_up: { meta: 'CompleteRegistration', tiktok: 'CompleteRegistration', ga: 'sign_up' },
    contact: { meta: 'Contact', tiktok: 'Contact', ga: 'generate_lead' },
    view_cart: { meta: 'ViewContent', tiktok: 'ViewContent', ga: 'view_cart' },
}

/** Purchases are de-duplicated in localStorage so a reloaded success page reports once. */
function alreadySent(eventId) {
    if (!eventId) return false

    try {
        const key = 'vd_evt_' + eventId
        if (window.localStorage.getItem(key)) return true
        window.localStorage.setItem(key, '1')
        return false
    } catch (e) {
        return false
    }
}

function toMetaContents(contents = []) {
    return contents.map((c) => ({
        id: c.content_id,
        quantity: c.quantity,
        item_price: c.price,
    }))
}

function toGaItems(contents = []) {
    return contents.map((c) => ({
        item_id: c.content_id,
        item_name: c.content_name,
        price: c.price,
        quantity: c.quantity,
    }))
}

export function track(event, payload = {}) {
    const names = EVENT_MAP[event]
    if (!names) return

    const eventId = payload.event_id || null

    if (event === 'purchase' && alreadySent(eventId)) return

    const contents = payload.contents || (payload.content_id ? [payload] : [])
    const value = payload.value ?? 0
    const currency = payload.currency || 'BDT'

    if (typeof window.fbq === 'function') {
        window.fbq(
            'track',
            names.meta,
            {
                content_ids: contents.map((c) => c.content_id),
                content_name: payload.content_name,
                content_type: payload.content_type || 'product',
                contents: toMetaContents(contents),
                num_items: payload.num_items,
                value,
                currency,
                ...(payload.search_string ? { search_string: payload.search_string } : {}),
            },
            eventId ? { eventID: eventId } : {},
        )
    }

    if (window.ttq && typeof window.ttq.track === 'function') {
        window.ttq.track(
            names.tiktok,
            {
                contents: contents.map((c) => ({
                    content_id: c.content_id,
                    content_name: c.content_name,
                    content_type: 'product',
                    quantity: c.quantity,
                    price: c.price,
                })),
                value,
                currency,
                ...(payload.search_string ? { query: payload.search_string } : {}),
            },
            eventId ? { event_id: eventId } : {},
        )
    }

    if (typeof window.gtag === 'function') {
        window.gtag('event', names.ga, {
            currency,
            value,
            transaction_id: payload.order_id,
            items: toGaItems(contents),
            ...(payload.search_string ? { search_term: payload.search_string } : {}),
        })
    }

    if (Array.isArray(window.dataLayer)) {
        window.dataLayer.push({
            event: names.ga,
            ecommerce: { currency, value, transaction_id: payload.order_id, items: toGaItems(contents) },
        })
    }
}

window.vdTrack = track

/**
 * Fire whatever the page declared.
 *
 * A node carrying data-track="view_content" with a JSON payload fires as soon as the page
 * is ready; data-track-click fires on click. Re-running after a Livewire navigation is safe
 * because each node is marked once it has fired.
 */
function runPageEvents(root = document) {
    root.querySelectorAll('[data-track]:not([data-track-fired])').forEach((node) => {
        node.setAttribute('data-track-fired', '1')

        try {
            track(node.dataset.track, JSON.parse(node.dataset.trackPayload || '{}'))
        } catch (e) {
            /* A malformed payload must never break the page. */
        }
    })
}

document.addEventListener('DOMContentLoaded', () => runPageEvents())
document.addEventListener('livewire:navigated', () => runPageEvents())

document.addEventListener('click', (event) => {
    const node = event.target.closest('[data-track-click]')
    if (!node) return

    try {
        track(node.dataset.trackClick, JSON.parse(node.dataset.trackPayload || '{}'))
    } catch (e) {
        /* Ignore: the click itself must still work. */
    }
})

// Livewire components raise this when the server has just done something worth reporting.
document.addEventListener('livewire:init', () => {
    window.Livewire.on('track', (event) => {
        const detail = Array.isArray(event) ? event[0] : event
        if (detail?.event) track(detail.event, detail.payload || {})
    })
})
