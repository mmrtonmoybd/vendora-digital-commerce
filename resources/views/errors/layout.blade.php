{{--
    The shared error page.

    Deliberately self-contained: no Vite bundle, no layout component, no database, no
    settings lookup that is allowed to throw. An error page's whole job is to render when
    something else has failed, and a 500 page that itself depends on the database is a
    white screen at the worst possible moment. That is also why the design system is
    repeated inline here instead of imported — the tokens are copied, not linked.

    The brand colour is read defensively and falls back to the shop's own red, so the page
    still looks like the shop even when the settings table is unreachable.

    $code     HTTP status
    $title    short headline
    $message  one sentence of explanation
    $actions  optional extra links
--}}
@php
  $brand = '#dc2626';
  $siteName = config('app.name');

  try {
      $brand = app(\App\Settings\ThemeSettings::class)->brand_hex ?: $brand;
      $siteName = app(\App\Settings\GeneralSettings::class)->site_name ?: $siteName;
  } catch (\Throwable) {
      // Settings unavailable — that is very possibly why we are on this page.
  }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ $code }} — {{ $title }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,400..800&family=Hind+Siliguri:wght@400;500;600&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --brand: {{ $brand }};
      --ink-900: #101a2e;
      --ink-950: #0a1120;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 22px;
      padding: 24px;
      background:
        radial-gradient(760px 420px at 78% -20%, color-mix(in srgb, var(--brand) 26%, transparent), transparent 62%),
        linear-gradient(165deg, var(--ink-900), var(--ink-950));
      color: #fff;
      font-family: 'Hind Siliguri', ui-sans-serif, system-ui, sans-serif;
      font-size: 16px;
      line-height: 1.65;
    }

    /* The same measured grid the storefront's dark bands carry. */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(255, 255, 255, .045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .045) 1px, transparent 1px);
      background-size: 64px 64px;
      mask-image: radial-gradient(700px 420px at 50% 0%, #000, transparent 78%);
    }

    main {
      position: relative;
      width: 100%;
      max-width: 540px;
    }

    .mark {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
      font-size: 19px;
      font-weight: 700;
      letter-spacing: -.02em;
      color: #fff;
      text-decoration: none;
    }

    .mark span {
      display: grid;
      place-items: center;
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: var(--brand);
    }

    .code {
      font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
      font-size: clamp(72px, 20vw, 128px);
      font-weight: 700;
      line-height: .95;
      letter-spacing: -.045em;
      margin: 28px 0 4px;
      font-variant-numeric: tabular-nums;
    }

    h1 {
      font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
      font-size: 24px;
      font-weight: 600;
      letter-spacing: -.02em;
      margin: 0 0 10px;
    }

    p {
      margin: 0;
      max-width: 46ch;
      color: rgba(255, 255, 255, .62);
    }

    .actions {
      margin-top: 30px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    a.btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      height: 46px;
      padding: 0 22px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 15px;
      text-decoration: none;
      transition: background-color .15s ease, border-color .15s ease;
    }

    a.primary {
      background: var(--brand);
      color: #fff;
    }

    a.primary:hover {
      background: color-mix(in srgb, var(--brand) 84%, black);
    }

    a.ghost {
      border: 1px solid rgba(255, 255, 255, .25);
      color: #fff;
    }

    a.ghost:hover {
      background: rgba(255, 255, 255, .1);
    }

    a:focus-visible {
      outline: 2px solid #fff;
      outline-offset: 2px;
    }

    footer {
      position: relative;
      font-size: 13px;
      color: rgba(255, 255, 255, .35);
    }
  </style>
</head>

<body>
  <main>
    <a class="mark" href="{{ url('/') }}">
      <span><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true">
          <path d="m12 2 9 5-9 5-9-5Z" />
          <path d="m3 12 9 5 9-5" />
          <path d="m3 17 9 5 9-5" />
        </svg></span>{{ $siteName }}
    </a>

    <div class="code">{{ $code }}</div>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>

    <div class="actions">
      {{ $actions ?? '' }}
      <a class="btn primary" href="{{ url('/') }}">{{ __('errors.back_home') }}</a>
    </div>
  </main>

  <footer>&copy; {{ date('Y') }} {{ $siteName }}</footer>
</body>

</html>
