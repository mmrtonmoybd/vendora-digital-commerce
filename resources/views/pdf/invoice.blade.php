{{--
    The invoice document. Rendered twice from this one file: through mPDF for the download,
    and as a plain page for the "Print" link.

    Every amount is Money::formatBase — the base currency, whatever the customer was
    browsing in. An invoice states what was charged, and the charge is taken in taka; a
    converted figure on a document like this would not reconcile against the bank.

    Every rule is inline here because the PDF engine does not run Vite — a print view that
    depended on the app bundle would start printing blank the first time the bundle moved.

    The markup leans on tables rather than bordered <div>s on purpose: mPDF draws borders
    and backgrounds on table cells reliably and on floated block elements only sometimes.
    Nested tables are avoided in the masthead because mPDF shrinks the whole table to fit
    when it meets one, and the result is unreadable.

    The accent colour is the shop's own brand colour, derived through BrandPalette, so the
    invoice looks like it came from the same company as the site.

    $order   the order being invoiced
    $print   true when this is the browser-printable copy
--}}
@php
  use App\Support\BrandPalette;
  use App\Support\Money;

  $print ??= false;

  $general = $store->general();
  $contact = $store->contact();
  $commerce = $store->commerce();

  $palette = BrandPalette::fromHex($store->theme()->brand_hex);
  $rgb = fn(int $shade): string => 'rgb(' . str_replace(' ', ',', $palette[$shade]) . ')';

  $brand = $rgb(600);
  $brandDark = $rgb(800);
  $brandDeep = $rgb(900);
  $brandTint = $rgb(50);
  $brandEdge = $rgb(200);

  $logoSrc = null;
  if ($general->logo && Storage::disk('public')->exists($general->logo)) {
      // The PDF engine runs with remote access off, so it needs a filesystem path.
      $logoSrc = $print ? Storage::disk('public')->url($general->logo) : Storage::disk('public')->path($general->logo);
  }

  $customerName = $order->user?->name;
  $customerEmail = $order->user?->email ?? $order->guest_email;

  $fullyRefunded = $order->refunded_amount >= $order->total && $order->refunded_amount > 0;
  $partlyRefunded = $order->refunded_amount > 0 && !$fullyRefunded;

  $statusLabel = $fullyRefunded ? __('invoice.status_refunded') : ($partlyRefunded ? __('invoice.status_part_refunded') : __('invoice.status_paid'));

  $statusBg = $fullyRefunded ? '#fee2e2' : ($partlyRefunded ? '#fef3c7' : '#dcfce7');
  $statusFg = $fullyRefunded ? '#991b1b' : ($partlyRefunded ? '#92400e' : '#166534');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <title>{{ __('invoice.title') }} {{ $order->order_number }}</title>
  <style>
    body {
      font-family: '{{ \App\Services\PdfService::FONT_FAMILY }}', sans-serif;
      font-size: 9.5pt;
      color: #263043;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    td,
    th {
      vertical-align: top;
    }

    .muted {
      color: #6b7385;
    }

    .right {
      text-align: right;
    }

    /* Masthead: a solid brand band, the one piece of colour that carries the document. */
    /* The accent rule is a border on the band, not an empty row: mPDF will not collapse an
       empty table cell and renders it as a tall block of colour. */
    .masthead td {
      background: {{ $brand }};
      color: #ffffff;
      padding: 16pt 16pt 14pt;
      border-bottom: 3pt solid {{ $brandDeep }};
    }

    /* Single-class selectors throughout: mPDF applies those reliably, and silently drops
       class-descendant ones like ".masthead .brand" — which is how the masthead ended up
       rendering at body size. */
    .brand {
      font-size: 19pt;
      font-weight: bold;
      color: #ffffff;
    }

    .tagline {
      color: #ffffff;
      font-size: 8.5pt;
    }

    .doc-title {
      font-size: 19pt;
      font-weight: bold;
      color: #ffffff;
      letter-spacing: 1.5pt;
    }

    .doc-meta {
      color: #ffffff;
      font-size: 8.5pt;
    }

    .label {
      font-size: 7pt;
      letter-spacing: .8pt;
      color: {{ $brandDark }};
      font-weight: bold;
      text-transform: uppercase;
    }

    .panel td {
      background: {{ $brandTint }};
      border: 0.6pt solid {{ $brandEdge }};
      padding: 8pt 10pt;
    }

    td.spacer {
      background: #ffffff;
      border: 0;
    }

    /* Line items: a brand header row, then hairlines. Zebra striping is handled per row
       because mPDF has no :nth-child. */
    .items th {
      background: {{ $brandDark }};
      color: #ffffff;
      text-align: left;
      font-size: 7.5pt;
      letter-spacing: .6pt;
      text-transform: uppercase;
      font-weight: bold;
      padding: 6pt 7pt;
    }

    .items td {
      padding: 7pt;
      border-bottom: 0.5pt solid #e6e9f0;
    }

    tr.alt td {
      background: #fafbfd;
    }

    .totals td {
      padding: 3.5pt 7pt;
    }

    tr.sep td {
      border-top: 0.6pt solid #e6e9f0;
    }

    .grand td {
      background: {{ $brand }};
      color: #ffffff;
      font-size: 12pt;
      font-weight: bold;
      padding: 8pt 7pt;
    }

    .stamp {
      font-weight: bold;
      font-size: 9pt;
      padding: 5pt 12pt;
    }

    .thanks td {
      background: {{ $brandTint }};
      border-left: 3pt solid {{ $brand }};
      padding: 9pt 11pt;
    }

    .foot {
      border-top: 0.6pt solid #e6e9f0;
      padding-top: 8pt;
      font-size: 7.5pt;
      color: #8a90a0;
    }

    .no-print {
      display: none;
    }

    @media screen {
      body {
        background: #eef1f6;
        margin: 0;
        padding: 26px 12px;
        font-size: 13px;
      }

      .sheet {
        max-width: 800px;
        margin: 0 auto;
        background: #fff;
        padding: 0 0 30px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(20, 28, 45, .12);
      }

      .gutter {
        padding-left: 34px;
        padding-right: 34px;
      }

      .no-print {
        display: block;
        max-width: 800px;
        margin: 0 auto 14px;
        text-align: right;
      }
    }

    @media print {
      .no-print {
        display: none;
      }

      body {
        background: #fff;
        padding: 0;
      }

      .sheet {
        max-width: none;
        padding: 0;
        box-shadow: none;
      }

      .gutter {
        padding-left: 0;
        padding-right: 0;
      }
    }
  </style>
</head>

<body>

  @if ($print)
    <div class="no-print">
      <button type="button" onclick="window.print()"
        style="font:inherit;font-weight:700;cursor:pointer;background:{{ $brand }};color:#fff;border:0;border-radius:6px;padding:10px 20px">
        {{ __('invoice.print') }}
      </button>
      <a href="{{ route('order.invoice', $order) }}"
        style="font:inherit;font-weight:700;margin-left:8px;display:inline-block;background:#fff;color:{{ $brandDark }};border:1px solid {{ $brandEdge }};border-radius:6px;padding:10px 20px;text-decoration:none">
        {{ __('invoice.download_pdf') }}
      </a>
    </div>
  @endif

  <div class="sheet">

    {{-- Masthead --}}
    <table class="masthead">
      <tr>
        <td width="56%">
          @if ($logoSrc)
            <img src="{{ $logoSrc }}" alt="{{ $general->site_name }}" style="max-height:34pt">
          @else
            <div class="brand">{{ $general->site_name }}</div>
          @endif
          <div class="tagline">{{ $store->st($general, 'tagline') }}</div>
        </td>
        <td width="44%" class="right">
          <div class="doc-title">{{ __('invoice.title') }}</div>
          <div class="doc-meta" style="margin-top:4pt"><strong>{{ $order->order_number }}</strong></div>
          <div class="doc-meta">{{ __('invoice.issued') }}: {{ ($order->paid_at ?? $order->created_at)->format('d M Y, g:i a') }}</div>
        </td>
      </tr>
    </table>

    <div class="gutter">

      {{-- Status stamp, on its own row so mPDF never shrinks the masthead to fit it --}}
      <table style="margin-top:12pt">
        <tr>
          <td width="70%" class="muted" style="font-size:8.5pt;padding-top:3pt">
            @if ($contact->email)
              {{ $contact->email }}
            @endif
            @if ($contact->phone)
              &nbsp;·&nbsp; {{ $contact->phone }}
            @endif
            @if ($store->st($contact, 'address'))
              <br>{{ $store->st($contact, 'address') }}
            @endif
          </td>
          <td width="30%" class="right">
            <span class="stamp" style="background:{{ $statusBg }};color:{{ $statusFg }}">{{ $statusLabel }}</span>
          </td>
        </tr>
      </table>

      {{-- Who and how --}}
      <table class="panel" style="margin-top:13pt">
        <tr>
          <td width="49%">
            <div class="label">{{ __('invoice.billed_to') }}</div>
            @if ($customerName)
              <strong>{{ $customerName }}</strong><br>
            @endif
            {{ $customerEmail }}<br>
            @if ($order->customer_phone)
              <span class="muted">{{ $order->customer_phone }}</span><br>
            @endif
            @if ($order->delivery_email && $order->delivery_email !== $customerEmail)
              <span class="muted">{{ __('invoice.delivery_email') }}: {{ $order->delivery_email }}</span>
            @endif
          </td>
          <td width="2%" class="spacer"></td>
          <td width="49%">
            <div class="label">{{ __('invoice.payment') }}</div>
            <strong>{{ $order->paymentMethod?->t('name') ?? __('invoice.not_recorded') }}</strong><br>
            @if ($order->manual_transaction_id)
              <span class="muted">{{ __('invoice.transaction_id') }}: {{ $order->manual_transaction_id }}</span><br>
            @endif
            @if ($order->paid_at)
              <span class="muted">{{ __('invoice.paid_on') }}: {{ $order->paid_at->format('d M Y, g:i a') }}</span><br>
            @endif
            <span class="muted">{{ __('invoice.currency') }}: {{ $commerce->currency_code }}</span>
          </td>
        </tr>
      </table>

      {{-- Lines --}}
      <table class="items" style="margin-top:15pt">
        <thead>
          <tr>
            <th width="52%">{{ __('invoice.item') }}</th>
            <th width="10%" class="right">{{ __('invoice.qty') }}</th>
            <th width="19%" class="right">{{ __('invoice.unit_price') }}</th>
            <th width="19%" class="right">{{ __('invoice.amount') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($order->items as $index => $item)
            @php
              $snapshot = $item->product_snapshot ?? [];
              $name = $snapshot['name'] ?? ($item->product?->name ?? __('invoice.deleted_product'));
            @endphp
            <tr @class(['alt' => $index % 2 === 1])>
              <td>
                <strong>{{ $name }}</strong>
                @if ($platform = $item->platformName())
                  <br><span class="muted">{{ $platform }}@if ($role = $item->accessRole())
                      · {{ $role }}
                    @endif
                  </span>
                @endif
                @if ($item->delivery_email)
                  <br><span class="muted">{{ __('invoice.access_for') }}: {{ $item->delivery_email }}</span>
                @endif
                @foreach ($item->field_values ?? [] as $fieldLabel => $value)
                  @if (filled($value) && !is_array($value))
                    <br><span class="muted">{{ $fieldLabel }}: {{ $value }}</span>
                  @endif
                @endforeach
              </td>
              <td class="right">{{ $item->quantity }}</td>
              <td class="right">{{ Money::formatBase($item->unit_price) }}</td>
              <td class="right"><strong>{{ Money::formatBase($item->line_total) }}</strong></td>
            </tr>
          @endforeach
        </tbody>
      </table>

      {{-- Money --}}
      <table style="margin-top:13pt">
        <tr>
          <td width="52%" style="padding-right:12pt">
            @if ($order->customer_note)
              <div class="label">{{ __('invoice.customer_note') }}</div>
              <div style="margin-top:2pt">{{ $order->customer_note }}</div>
            @endif
          </td>
          <td width="48%">
            <table class="totals">
              <tr>
                <td class="muted">{{ __('invoice.subtotal') }}</td>
                <td class="right">{{ Money::formatBase($order->subtotal) }}</td>
              </tr>
              @if ($order->discount > 0)
                <tr>
                  <td class="muted">{{ __('invoice.discount') }}@if ($order->coupon)
                      ({{ $order->coupon->code }})
                    @endif
                  </td>
                  <td class="right">− {{ Money::formatBase($order->discount) }}</td>
                </tr>
              @endif
              @if ($order->fee > 0)
                <tr>
                  <td class="muted">{{ __('invoice.fee') }}</td>
                  <td class="right">{{ Money::formatBase($order->fee) }}</td>
                </tr>
              @endif
            </table>
            <table class="totals grand">
              <tr>
                <td>{{ __('invoice.total') }}</td>
                <td class="right">{{ Money::formatBase($order->total) }}</td>
              </tr>
            </table>
            @if ($order->refunded_amount > 0)
              <table class="totals">
                <tr>
                  <td class="muted">{{ __('invoice.refunded') }}</td>
                  <td class="right">− {{ Money::formatBase($order->refunded_amount) }}</td>
                </tr>
                <tr class="sep">
                  <td><strong>{{ __('invoice.net_paid') }}</strong></td>
                  <td class="right"><strong>{{ Money::formatBase($order->total - $order->refunded_amount) }}</strong></td>
                </tr>
              </table>
            @endif
          </td>
        </tr>
      </table>

      <table class="thanks" style="margin-top:15pt">
        <tr>
          <td>
            <strong style="color:{{ $brandDark }}">{{ __('invoice.thanks_title', ['site' => $general->site_name]) }}</strong><br>
            <span class="muted">{{ __('invoice.footer_note', ['site' => $general->site_name]) }}</span>
          </td>
        </tr>
      </table>

      <div class="foot" style="margin-top:16pt">
        @if ($contact->email)
          {{ __('invoice.footer_support', ['email' => $contact->email]) }}<br>
        @endif
        {{ $store->st($general, 'copyright_text') }}
      </div>

    </div>{{-- /gutter --}}
  </div>
</body>

</html>
