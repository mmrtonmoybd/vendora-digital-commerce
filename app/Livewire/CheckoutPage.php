<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\PaymentMethodType;
use App\Exceptions\DomainException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PaymentMethod;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\TrackingService;
use App\Settings\FulfillmentSettings;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Checkout for a whole basket.
 *
 * Same contract as the buy-now modal: this screen is a convenience layer and
 * CheckoutService re-validates everything before an order exists.
 */
final class CheckoutPage extends Component
{
    use WithFileUploads;

    public string $email = '';

    public string $customerPhone = '';

    public string $paymentKey = '';

    public string $coupon = '';

    public string $couponMsg = '';

    public bool $couponOk = false;

    public int $discount = 0;

    public string $sender = '';

    public string $trx = '';

    /** The uploaded proof. A TemporaryUploadedFile once Livewire has taken it, null before. */
    public mixed $screenshot = null;

    public string $error = '';

    public function mount(CartService $carts): void
    {
        $user = auth()->user();

        $this->email = $user?->email ?? '';
        $this->customerPhone = $user?->phone ?? '';
        $this->coupon = (string) session('cart_coupon', '');
        $this->paymentKey = $this->defaultPaymentKey($carts);

        if ($this->coupon !== '') {
            $this->applyCoupon();
        }
    }

    public function applyCoupon(): void
    {
        $cart = app(CartService::class)->current()->load('items.product');

        $result = app(CouponService::class)->validate(
            $this->coupon,
            $cart->subtotal(),
            auth()->user(),
            $this->email ?: null,
            $cart->items->map(fn (CartItem $item): array => [
                'product_id' => (int) $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => (int) ($item->product?->price ?? 0),
            ])->all(),
        );

        $this->couponOk = $result->ok;
        $this->couponMsg = $result->message;
        $this->discount = $result->discount;
    }

    public function place(CheckoutService $checkout, CartService $carts): mixed
    {
        $cart = $carts->current()->load('items.product');
        $method = $this->method();

        $this->error = $this->validateInput($cart, $method);

        if ($this->error !== '') {
            return null;
        }

        $screenshotPath = null;

        if ($method !== null && $method->isManual() && $this->screenshot !== null) {
            // Proof of payment is not public: it carries a phone number and a receipt.
            $screenshotPath = $this->screenshot->store('orders', 'local');
        }

        try {
            $order = $checkout->placeFromCart(
                cart: $cart,
                deliveryEmail: $this->email,
                method: $method,
                couponCode: $this->couponOk ? $this->coupon : null,
                user: auth()->user(),
                guestEmail: $this->email,
                customerPhone: $this->customerPhone ?: null,
            );

            if ($method->isManual() && $this->sender !== '' && $this->trx !== '') {
                app(OrderService::class)
                    ->submitManualProof($order, $this->sender, mb_strtoupper($this->trx), $screenshotPath);
            }
        } catch (DomainException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        session()->forget('cart_coupon');
        $this->dispatch('cart-updated');

        if ($method->isGateway()) {
            return redirect()->route('payments.shurjopay.start', ['type' => 'order', 'id' => $order->getKey()]);
        }

        return redirect()->route('order.pay', $order);
    }

    public function method(): ?PaymentMethod
    {
        return $this->methods()->firstWhere('key', $this->paymentKey);
    }

    /** @return Collection<int, PaymentMethod> */
    public function methods(): Collection
    {
        return PaymentMethod::forOrders()->get();
    }

    public function walletDisabled(): bool
    {
        $user = auth()->user();

        return $user === null || (int) $user->balance < $this->total();
    }

    public function subtotal(): int
    {
        return app(CartService::class)->current()->load('items.product')->subtotal();
    }

    public function fee(): int
    {
        return $this->method()?->feeFor(max(0, $this->subtotal() - $this->discount)) ?? 0;
    }

    public function total(): int
    {
        return max(0, $this->subtotal() - $this->discount + $this->fee());
    }

    public function money(int $paisa): string
    {
        return Money::format($paisa);
    }

    public function render(CartService $carts): View
    {
        $cart = $carts->current()->load('items.product.category');

        return view('livewire.checkout-page', [
            'items' => $cart->items,
            'methods' => $this->methods(),
            'deliveryHelp' => app(FulfillmentSettings::class)->delivery_email_help,
            'trackPayload' => app(TrackingService::class)->basket(
                $cart->items->map(fn (CartItem $item): array => [
                    'slug' => $item->product?->slug,
                    'name' => (string) $item->product?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (int) ($item->product?->price ?? 0),
                ])->all(),
                $this->total(),
            ),
        ]);
    }

    private function validateInput(Cart $cart, ?PaymentMethod $method): string
    {
        if ($cart->items->isEmpty()) {
            return 'Your cart is empty.';
        }

        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid delivery email.';
        }

        if ($method === null) {
            return 'Choose a payment method.';
        }

        if ($method->isWallet() && $this->walletDisabled()) {
            return 'Wallet balance is not enough.';
        }

        if ($method->isManual() && (! preg_match('/^01\d{9}$/', $this->sender) || mb_strlen(trim($this->trx)) < 6)) {
            return 'Enter your sender number (01XXXXXXXXX) and transaction ID.';
        }

        if ($method->isGateway() && ! preg_match('/^01\d{9}$/', $this->customerPhone)) {
            return 'Enter your phone number (01XXXXXXXXX) to pay online.';
        }

        $problems = app(CartService::class)->validateLines($cart);

        return $problems[0] ?? '';
    }

    private function defaultPaymentKey(CartService $carts): string
    {
        $methods = $this->methods();
        $user = auth()->user();
        $wallet = $methods->firstWhere('type', PaymentMethodType::Wallet);

        if ($wallet !== null && $user !== null && (int) $user->balance >= $carts->current()->load('items.product')->subtotal()) {
            return $wallet->key;
        }

        return (string) ($methods->first(fn (PaymentMethod $m): bool => ! $m->isWallet())?->key ?? $methods->first()?->key ?? '');
    }
}
