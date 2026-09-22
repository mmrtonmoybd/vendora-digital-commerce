@php
    $seo = app(\App\Settings\SeoSettings::class);
@endphp

{{-- Google Tag Manager. When it is in use it usually owns GA4 as well, so GA4's own
     snippet below is only emitted when GTM is not configured. --}}
@if (filled($seo->gtm_id))
    <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', @json($seo->gtm_id));
    </script>
@elseif (filled($seo->ga4_id) || filled($seo->google_ads_id))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo->ga4_id ?: $seo->google_ads_id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('js', new Date());
        @if (filled($seo->ga4_id))
            gtag('config', @json($seo->ga4_id));
        @endif
        @if (filled($seo->google_ads_id))
            gtag('config', @json($seo->google_ads_id));
        @endif
    </script>
@endif

@if (filled($seo->meta_pixel_id))
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
            t = b.createElement(e); t.async = !0; t.src = v;
            s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
        }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($seo->meta_pixel_id));
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $seo->meta_pixel_id }}&ev=PageView&noscript=1"></noscript>
@endif

@if (filled($seo->tiktok_pixel_id))
    <script>
        !function (w, d, t) {
            w.TiktokAnalyticsObject = t; var ttq = w[t] = w[t] || [];
            ttq.methods = ['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];
            ttq.setAndDefer = function (t, e) { t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))) } };
            for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
            ttq.instance = function (t) { for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]); return e };
            ttq.load = function (e, n) {
                var r = 'https://analytics.tiktok.com/i18n/pixel/events.js';
                ttq._i = ttq._i || {}; ttq._i[e] = []; ttq._i[e]._u = r; ttq._t = ttq._t || {}; ttq._t[e] = +new Date;
                ttq._o = ttq._o || {}; ttq._o[e] = n || {};
                var o = d.createElement('script'); o.type = 'text/javascript'; o.async = !0; o.src = r + '?sdkid=' + e + '&lib=' + t;
                var a = d.getElementsByTagName('script')[0]; a.parentNode.insertBefore(o, a)
            };
            ttq.load(@json($seo->tiktok_pixel_id));
            ttq.page();
        }(window, document, 'ttq');
    </script>
@endif
