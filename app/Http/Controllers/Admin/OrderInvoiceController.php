<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PdfService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff access to a customer's invoice.
 *
 * This exists as its own controller because the storefront one authorises against the web
 * guard: it asks "is this the customer who placed the order, or someone holding the signed
 * link we emailed them?". An administrator is neither, so routing staff through it returned
 * 404 for every order in the system. Here the question is the right one — does this
 * administrator hold the permission to view orders.
 */
final class OrderInvoiceController extends Controller
{
    public function download(Order $order): Response
    {
        $this->authorizeStaff($order);

        return PdfService::download(
            'pdf.invoice',
            ['order' => $this->load($order), 'print' => false],
            "invoice-{$order->order_number}.pdf",
        );
    }

    public function print(Order $order): View
    {
        $this->authorizeStaff($order);

        return view('pdf.invoice', ['order' => $this->load($order), 'print' => true]);
    }

    private function authorizeStaff(Order $order): void
    {
        $admin = auth('admin')->user();

        abort_if($admin === null, 403);
        abort_unless($admin->can('View:Order'), 403);

        // An unpaid order has no invoice to issue. Printing one would create a document
        // claiming money changed hands when it has not.
        abort_unless($order->isPaid(), 404);
    }

    private function load(Order $order): Order
    {
        return $order->load(['items.product.platform', 'paymentMethod', 'user', 'coupon']);
    }
}
