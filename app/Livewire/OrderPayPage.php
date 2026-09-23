<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\OrderPaymentStatus;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The order page: what to pay, how to pay it, and where the access stands.
 *
 * It is reachable by the owner, or by a guest through the signed link in their email; the
 * controller has already decided that before this component is mounted.
 */
final class OrderPayPage extends Component
{
    use WithFileUploads;

    public Order $order;

    public string $sender = '';

    public string $trx = '';

    /** The uploaded proof. A TemporaryUploadedFile once Livewire has taken it, null before. */
    public mixed $screenshot = null;

    public string $error = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function submitProof(OrderService $orders): void
    {
        if (! preg_match('/^01\d{9}$/', $this->sender) || mb_strlen(trim($this->trx)) < 6) {
            $this->error = 'Enter your sender number (01XXXXXXXXX) and transaction ID.';

            return;
        }

        $path = $this->screenshot?->store('orders', 'local');

        try {
            $orders->submitManualProof($this->order, $this->sender, mb_strtoupper(trim($this->trx)), $path);
        } catch (DomainException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->error = '';
        $this->order->refresh();
    }

    public function money(int $paisa): string
    {
        return Money::format($paisa);
    }

    /** What the big heading says, mirroring the template's three states. */
    public function headline(): string
    {
        return match (true) {
            $this->order->payment_status === OrderPaymentStatus::Paid => 'Order placed & paid!',
            $this->order->payment_status === OrderPaymentStatus::Submitted => 'Payment submitted!',
            default => 'Complete your payment',
        };
    }

    public function subheading(): string
    {
        return $this->isSettled()
            ? 'We are verifying your payment. You will get an email as soon as it is delivered.'
            : 'Your order is reserved. Pay the amount below to confirm it.';
    }

    public function isSettled(): bool
    {
        return in_array($this->order->payment_status, [
            OrderPaymentStatus::Paid,
            OrderPaymentStatus::Submitted,
        ], true);
    }

    public function render(): View
    {
        $this->order->loadMissing(['items.product.platform', 'paymentMethod', 'payments']);

        return view('livewire.order-pay-page', [
            'method' => $this->order->paymentMethod,
            'lastPayment' => $this->order->payments->first(),
        ]);
    }
}
