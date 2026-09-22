{{--
    The invoice document. Rendered twice from this one file: through mPDF for the download,
    and as a plain page for the "Print" link.

    Every rule is inline here because the PDF engine does not run Vite — a print view that
    depended on the app bundle would start printing blank the first time the bundle moved.

    The markup leans on tables rather than bordered <div>s on purpose: mPDF draws borders and
    backgrounds on table cells reliably and on floated block elements only sometimes.

    $order   the order being invoiced
    $print   true when this is the browser-printable copy
--}}
@php
    use App\Support\Money;

    $print ??= false;

    $general = $store->general();
    $contact = $store->contact();
    $commerce = $store->commerce();

    $logoSrc = null;
    if ($general->logo && Storage::disk('public')->exists($general->logo)) {
        // The PDF engine runs with remote access off, so it needs a filesystem path.
        $logoSrc = $print
            ? Storage::disk('public')->url($general->logo)
            : Storage::disk('public')->path($general->logo);
    }

    $customerName = $order->user?->name;
    $customerEmail = $order->user?->email ?? $order->guest_email;

    $fullyRefunded = $order->refunded_amount >= $order->total && $order->refunded_amount > 0;
    $partlyRefunded = $order->refunded_amount > 0 && ! $fullyRefunded;

    $statusLabel = $fullyRefunded
        ? __('invoice.status_refunded')
        : ($partlyRefunded ? __('invoice.status_part_refunded') : __('invoice.status_paid'));

    $statusBg = $fullyRefunded ? '#fee2e2' : ($partlyRefunded ? '#fef3c7' : '#dcfce7');
    $statusFg = $fullyRefunded ? '#991b1b' : ($partlyRefunded ? '#92400e' : '#166534');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<title>{{ __('invoice.title') }} {{ $order->order_number }}</title>
<style>
    body { font-family: '{{ \App\Services\PdfService::FONT_FAMILY }}', sans-serif; font-size: 9.5pt; color: #1f2937; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }
    .muted { color: #6b7280; }
    .right { text-align: right; }
    .brand { font-size: 17pt; font-weight: bold; color: #111827; }
    .doc-title { font-size: 17pt; font-weight: bold; color: #111827; }
    .label { font-size: 7.5pt; letter-spacing: .5pt; color: #6b7280; font-weight: bold; text-transform: uppercase; }
    .panel td { background: #f9fafb; border: 0.6pt solid #e5e7eb; padding: 7pt 9pt; }
    .items th { text-align: left; font-size: 7.5pt; letter-spacing: .5pt; color: #6b7280;
                text-transform: uppercase; font-weight: bold; border-bottom: 1pt solid #cbd1d9; padding: 5pt 4pt; }
    .items td { padding: 6pt 4pt; border-bottom: 0.5pt solid #eef0f3; }
    .totals td { padding: 3pt 4pt; }
    .grand td { border-top: 1pt solid #cbd1d9; padding-top: 6pt; font-size: 12pt; font-weight: bold; color: #111827; }
    .foot { border-top: 0.6pt solid #e5e7eb; padding-top: 7pt; font-size: 8pt; color: #6b7280; }
    .no-print { display: none; }
    @media screen {
        body { background: #f3f4f6; margin: 0; padding: 24px 12px; font-size: 13px; }
        .sheet { max-width: 780px; margin: 0 auto; background: #fff; padding: 36px 38px; border-radius: 6px; }
        .no-print { display: block; max-width: 780px; margin: 0 auto 14px; text-align: right; }
    }
    @media print {
        .no-print { display: none; }
        body { background: #fff; padding: 0; }
        .sheet { max-width: none; padding: 0; }
    }
</style>
</head>
<body>

@if ($print)
<div class="no-print">
    <button type="button" onclick="window.print()"
            style="font:inherit;font-weight:700;cursor:pointer;background:#111827;color:#fff;border:0;border-radius:4px;padding:9px 18px">
        {{ __('invoice.print') }}
    </button>
    <a href="{{ route('order.invoice', $order) }}"
       style="font:inherit;font-weight:700;margin-left:8px;display:inline-block;background:#fff;color:#111827;border:1px solid #d1d5db;border-radius:4px;padding:9px 18px;text-decoration:none">
        {{ __('invoice.download_pdf') }}
    </a>
</div>
@endif

<div class="sheet">

{{-- Masthead --}}
<table>
<tr>
    <td width="58%">
        @if ($logoSrc)
            <img src="{{ $logoSrc }}" alt="{{ $general->site_name }}" style="max-height:38pt">
        @else
            <div class="brand">{{ $general->site_name }}</div>
        @endif
        <div class="muted">{{ $general->tagline }}</div>
        <div class="muted" style="margin-top:5pt">
            @if ($contact->email){{ $contact->email }}<br>@endif
            @if ($contact->phone){{ $contact->phone }}<br>@endif
            @if ($contact->address){{ $contact->address }}@endif
        </div>
    </td>
    <td width="42%" class="right">
        <div class="doc-title">{{ __('invoice.title') }}</div>
        <div style="margin-top:3pt"><strong>{{ $order->order_number }}</strong></div>
        <div class="muted">{{ __('invoice.issued') }}: {{ ($order->paid_at ?? $order->created_at)->format('d M Y, g:i a') }}</div>
        {{-- A block, not an inline badge: mPDF shrinks the whole masthead to fit if a
             nested table turns up in here, and the result is unreadable. --}}
        <div style="margin-top:6pt;background:{{ $statusBg }};color:{{ $statusFg }};font-weight:bold;font-size:8pt;padding:4pt 7pt;text-align:right">{{ $statusLabel }}</div>
    </td>
</tr>
</table>

{{-- Who and how --}}
<table class="panel" style="margin-top:14pt">
<tr>
    <td width="49%">
        <div class="label">{{ __('invoice.billed_to') }}</div>
        @if ($customerName)<strong>{{ $customerName }}</strong><br>@endif
        {{ $customerEmail }}<br>
        @if ($order->customer_phone)<span class="muted">{{ $order->customer_phone }}</span><br>@endif
        @if ($order->delivery_email && $order->delivery_email !== $customerEmail)
            <span class="muted">{{ __('invoice.delivery_email') }}: {{ $order->delivery_email }}</span>
        @endif
    </td>
    <td width="2%" style="background:#fff;border:0"></td>
    <td width="49%">
        <div class="label">{{ __('invoice.payment') }}</div>
        <strong>{{ $order->paymentMethod?->name ?? __('invoice.not_recorded') }}</strong><br>
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
<table class="items" style="margin-top:16pt">
<thead>
<tr>
    <th width="52%">{{ __('invoice.item') }}</th>
    <th width="10%" class="right">{{ __('invoice.qty') }}</th>
    <th width="19%" class="right">{{ __('invoice.unit_price') }}</th>
    <th width="19%" class="right">{{ __('invoice.amount') }}</th>
</tr>
</thead>
<tbody>
@foreach ($order->items as $item)
    @php
        $snapshot = $item->product_snapshot ?? [];
        $name = $snapshot['name'] ?? $item->product?->name ?? __('invoice.deleted_product');
    @endphp
    <tr>
        <td>
            <strong>{{ $name }}</strong>
            @if ($platform = $item->platformName())
                <br><span class="muted">{{ $platform }}@if ($role = $item->accessRole()) · {{ $role }}@endif</span>
            @endif
            @if ($item->delivery_email)
                <br><span class="muted">{{ __('invoice.access_for') }}: {{ $item->delivery_email }}</span>
            @endif
            @foreach (($item->field_values ?? []) as $fieldLabel => $value)
                @if (filled($value) && ! is_array($value))
                    <br><span class="muted">{{ $fieldLabel }}: {{ $value }}</span>
                @endif
            @endforeach
        </td>
        <td class="right">{{ $item->quantity }}</td>
        <td class="right">{{ Money::format($item->unit_price) }}</td>
        <td class="right">{{ Money::format($item->line_total) }}</td>
    </tr>
@endforeach
</tbody>
</table>

{{-- Money --}}
<table style="margin-top:12pt">
<tr>
<td width="54%">
    @if ($order->customer_note)
        <div class="label">{{ __('invoice.customer_note') }}</div>
        <div>{{ $order->customer_note }}</div>
    @endif
</td>
<td width="46%">
    <table class="totals">
        <tr>
            <td class="muted">{{ __('invoice.subtotal') }}</td>
            <td class="right">{{ Money::format($order->subtotal) }}</td>
        </tr>
        @if ($order->discount > 0)
            <tr>
                <td class="muted">{{ __('invoice.discount') }}@if ($order->coupon) ({{ $order->coupon->code }})@endif</td>
                <td class="right">− {{ Money::format($order->discount) }}</td>
            </tr>
        @endif
        @if ($order->fee > 0)
            <tr>
                <td class="muted">{{ __('invoice.fee') }}</td>
                <td class="right">{{ Money::format($order->fee) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>{{ __('invoice.total') }}</td>
            <td class="right">{{ Money::format($order->total) }}</td>
        </tr>
        @if ($order->refunded_amount > 0)
            <tr>
                <td class="muted">{{ __('invoice.refunded') }}</td>
                <td class="right">− {{ Money::format($order->refunded_amount) }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('invoice.net_paid') }}</strong></td>
                <td class="right"><strong>{{ Money::format($order->total - $order->refunded_amount) }}</strong></td>
            </tr>
        @endif
    </table>
</td>
</tr>
</table>

<div class="foot" style="margin-top:22pt">
    {{ __('invoice.footer_note', ['site' => $general->site_name]) }}<br>
    @if ($contact->email){{ __('invoice.footer_support', ['email' => $contact->email]) }}<br>@endif
    <span style="color:#9ca3af">{{ $general->copyright_text }}</span>
</div>

</div>
</body>
</html>
