{{--
    The shared error page.

    Deliberately self-contained: no Vite bundle, no layout component, no database, no
    settings lookup that is allowed to throw. An error page's whole job is to render when
    something else has failed, and a 500 page that itself depends on the database is a
    white screen at the worst possible moment.

    The brand colour is read defensively and falls back to the template's own red, so the
    page still looks like the shop even when the settings table is unreachable.

    $code     HTTP status
    $title    short headline
    $message  one sentence of explanation
    $actions  optional extra links
--}}
@php
  $brand = '#dc2626';
  $siteName = 'Vendora';

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
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <style>
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
      padding: 24px;
      background: #faf9f7;
      color: #1c1917;
      font-family: 'Hind Siliguri', ui-sans-serif, system-ui, sans-serif;
      font-size: 16px;
      line-height: 1.6;
      text-align: center;
    }

    .card {
      width: 100%;
      max-width: 560px;
      background: #fff;
      border-radius: 24px;
      padding: 44px 32px 36px;
      box-shadow: 0 10px 40px rgba(28, 25, 23, .08);
      box-shadow: 0 10px 40px rgba(28, 25, 23, .08), 0 0 0 1px rgba(0, 0, 0, .04);
    }

    .badge {
      display: inline-block;
      font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 1.4px;
      text-transform: uppercase;
      color: {{ $brand }};
      background: color-mix(in srgb, {{ $brand }} 10%, white);
      border-radius: 999px;
      padding: 6px 16px;
    }

    .code {
      font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
      font-size: clamp(64px, 18vw, 104px);
      font-weight: 800;
      line-height: 1;
      letter-spacing: -2px;
      margin: 18px 0 6px;
      background: linear-gradient(135deg, {{ $brand }}, color-mix(in srgb, {{ $brand }} 55%, #1c1917));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    h1 {
      font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
      font-size: 24px;
      font-weight: 700;
      margin: 0 0 10px;
    }

    p {
      margin: 0 auto;
      max-width: 42ch;
      color: #57534e;
    }

    .actions {
      margin-top: 26px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    a.btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      height: 46px;
      padding: 0 22px;
      border-radius: 999px;
      font-weight: 600;
      font-size: 15px;
      text-decoration: none;
      transition: background-color .15s ease, color .15s ease;
    }

    a.primary {
      background: {{ $brand }};
      color: #fff;
    }

    a.primary:hover {
      background: color-mix(in srgb, {{ $brand }} 82%, black);
    }

    a.ghost {
      background: #f5f5f4;
      color: #1c1917;
    }

    a.ghost:hover {
      background: #e7e5e4;
    }

    footer {
      margin-top: 26px;
      font-size: 13px;
      color: #a8a29e;
    }

    footer a {
      color: inherit;
    }

    @media (prefers-color-scheme: dark) {
      body {
        background: #12100f;
        color: #f5f5f4;
      }

      .card {
        background: #1c1917;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .5), 0 0 0 1px rgba(255, 255, 255, .06);
      }

      p {
        color: #a8a29e;
      }

      a.ghost {
        background: #292524;
        color: #f5f5f4;
      }

      a.ghost:hover {
        background: #44403c;
      }
    }
  </style>
</head>

<body>
  <main class="card">
    <span class="badge">{{ $siteName }}</span>
    <div class="code">{{ $code }}</div>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>

    <div class="actions">
      {{ $actions ?? '' }}
      <a class="btn primary" href="{{ url('/') }}">{{ __('errors.back_home') }}</a>
    </div>
  </main>

  <footer>
    &copy; {{ date('Y') }} {{ $siteName }}
  </footer>
</body>

</html>
