<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class OrderPayController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $this->authorizeView($request, $order);

        $order->load(['items.product.platform', 'paymentMethod', 'payments', 'histories']);

        return view('pages.order-pay', ['order' => $order]);
    }

    public function invoice(Request $request, Order $order): Response
    {
        $this->authorizeView($request, $order);

        abort_unless($order->isPaid(), 404);

        return PdfService::download(
            'pdf.invoice',
            ['order' => $this->invoiceData($order), 'print' => false],
            "invoice-{$order->order_number}.pdf",
        );
    }

    /**
     * The same document as a normal page, for people who would rather hit Ctrl+P.
     *
     * Kept as its own route rather than a query flag on the download so it can be linked,
     * bookmarked and — unlike the PDF — opened on a phone that has no PDF viewer.
     */
    public function invoicePrint(Request $request, Order $order): View
    {
        $this->authorizeView($request, $order);

        abort_unless($order->isPaid(), 404);

        return view('pdf.invoice', ['order' => $this->invoiceData($order), 'print' => true]);
    }

    /** Everything the invoice prints, loaded in one place so the two copies cannot diverge. */
    private function invoiceData(Order $order): Order
    {
        return $order->load(['items.product.platform', 'paymentMethod', 'user', 'coupon']);
    }

    /**
     * Who may look at an order.
     *
     * The owner always can. A guest gets in through the signed link that was emailed to
     * them, and that grant is remembered in their session so the page survives a refresh.
     */
    private function authorizeView(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user !== null && $order->user_id === $user->getKey()) {
            return;
        }

        $granted = (array) session('order_access', []);

        if (in_array($order->getKey(), $granted, true)) {
            return;
        }

        if ($request->hasValidSignature()) {
            session()->push('order_access', $order->getKey());

            return;
        }

        abort(404);
    }
}
