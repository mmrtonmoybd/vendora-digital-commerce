<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Exceptions\DomainException;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\CouponService;
use App\Services\TrackingService;
use App\Settings\FulfillmentSettings;
use App\Support\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The buy-now modal, which is where most of this shop's orders are actually placed.
 *
 * It mirrors the template's Alpine store one behaviour at a time, but every figure it shows
 * — the clamped quantity, the coupon discount, the fee, the total — is computed on the
 * server, so what the customer sees is what CheckoutService will charge. The service
 * re-validates all of it again anyway; this is the friendly half, not the authoritative one.
 */
final class BuyNowModal extends Component
{
    public bool $show = false;

    public ?int $productId = null;

    public string $email = '';

    public int $qty = 1;

    /** @var array<string, string> */
    public array $fields = [];

    public string $coupon = '';

    public string $couponMsg = '';

    public bool $couponOk = false;

    public int $discount = 0;

    public string $paymentKey = '';

    public string $customerPhone = '';

    public string $error = '';

    /** Opened from any product card or the product page. */
    #[On('open-buy')]
    public function open(string $slug): void
    {
        $product = Product::active()->with(['fields', 'category'])->where('slug', $slug)->first();

        if ($product === null || ! $product->isInStock()) {
            return;
        }

        $this->reset(['fields', 'coupon', 'couponMsg', 'couponOk', 'discount', 'error']);

        $this->productId = $product->getKey();
        $this->qty = $product->min_qty;

        foreach ($product->fields as $field) {
            $this->fields[$field->key] = $field->options[0] ?? '';
        }

        $user = auth()->user();
        $this->email = $user?->email ?? '';
        $this->customerPhone = $user?->phone ?? '';
        $this->paymentKey = $this->defaultPaymentKey($product);
        $this->show = true;

        $this->dispatch('track', event: 'view_content', payload: app(TrackingService::class)->product($product));
    }

    /**
     * The "Add to cart" button on a product card.
     *
     * When we already know everything the line needs — a signed-in customer's email and no
     * required product fields — the item goes straight into the cart. Otherwise the modal
     * opens so the customer can supply what is missing, because a cart line with no player
     * ID is a line nobody can fulfil.
     */
    #[On('quick-add')]
    public function quickAdd(string $slug, CartService $carts): void
    {
        $this->open($slug);

        $product = $this->product();

        if ($product === null || ! $this->show) {
            return;
        }

        $needsInput = ! filter_var($this->email, FILTER_VALIDATE_EMAIL)
            || $product->fields->contains(fn ($field): bool => $field->required);

        if ($needsInput) {
            return;
        }

        $this->addToCart($carts);
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function increment(): void
    {
        $this->qty = min($this->qty + 1, $this->maxQty());
        $this->recalculate();
    }

    public function decrement(): void
    {
        $this->qty = max($this->qty - 1, $this->product()?->min_qty ?? 1);
        $this->recalculate();
    }

    public function updatedQty(): void
    {
        $this->clampQty();
    }

    public function clampQty(): void
    {
        $product = $this->product();

        if ($product === null) {
            return;
        }

        $this->qty = max($product->min_qty, min($this->qty, $this->maxQty()));
        $this->recalculate();
    }

    public function applyCoupon(): void
    {
        $product = $this->product();

        if ($product === null) {
            return;
        }

        $result = app(CouponService::class)->validate(
            $this->coupon,
            $this->subtotal(),
            auth()->user(),
            $this->email ?: null,
            [['product_id' => $product->getKey(), 'quantity' => $this->qty, 'unit_price' => $product->price]],
        );

        $this->couponOk = $result->ok;
        $this->couponMsg = $result->message;
        $this->discount = $result->discount;
    }

    public function addToCart(CartService $carts): void
    {
        $product = $this->product();

        if ($product === null) {
            return;
        }

        $this->error = $this->validateInput(requirePayment: false);

        if ($this->error !== '') {
            return;
        }

        try {
            $carts->add($product, $this->qty, $this->fields, $this->email);
        } catch (DomainException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: $product->name.' added to cart');
        $this->dispatch('track', event: 'add_to_cart', payload: app(TrackingService::class)->product($product, $this->qty));

        $this->close();
    }

    public function checkout(CheckoutService $checkout)
    {
        $product = $this->product();
        $method = $this->method();

        if ($product === null || $method === null) {
            return null;
        }

        $this->error = $this->validateInput();

        if ($this->error !== '') {
            return null;
        }

        try {
            $order = $checkout->placeFromBuyNow(
                product: $product,
                quantity: $this->qty,
                fields: $this->fields,
                deliveryEmail: $this->email,
                method: $method,
                couponCode: $this->couponOk ? $this->coupon : null,
                user: auth()->user(),
                guestEmail: $this->email,
                customerPhone: $this->customerPhone ?: null,
            );
        } catch (DomainException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        $this->close();

        // A gateway order goes straight to shurjoPay; everything else lands on the order
        // page, which is also where a guest's signed link points.
        if ($method->isGateway()) {
            return redirect()->route('payments.shurjopay.start', ['type' => 'order', 'id' => $order->getKey()]);
        }

        return redirect()->route('order.pay', $order);
    }

    // --- Figures the view renders ---------------------------------------------------------

    public function subtotal(): int
    {
        $product = $this->product();

        return $product === null ? 0 : $product->price * $this->qty;
    }

    public function fee(): int
    {
        return $this->method()?->feeFor(max(0, $this->subtotal() - $this->discount)) ?? 0;
    }

    public function total(): int
    {
        return max(0, $this->subtotal() - $this->discount + $this->fee());
    }

    public function maxQty(): int
    {
        return max(1, $this->product()?->maxOrderableQty() ?? 1);
    }

    public function walletDisabled(): bool
    {
        $user = auth()->user();

        return $user === null || (int) $user->balance < $this->total();
    }

    public function product(): ?Product
    {
        return $this->productId === null
            ? null
            : Product::active()->with(['fields', 'category', 'platform'])->find($this->productId);
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

    public function render()
    {
        return view('livewire.buy-now-modal', [
            'product' => $this->product(),
            'methods' => $this->methods(),
            'deliveryHelp' => app(FulfillmentSettings::class)->delivery_email_help,
        ]);
    }

    /** Re-run the coupon after anything that changes the subtotal. */
    private function recalculate(): void
    {
        if ($this->couponOk) {
            $this->applyCoupon();
        }
    }

    /** The template's own validation messages, checked server-side. */
    private function validateInput(bool $requirePayment = true): string
    {
        $product = $this->product();

        if ($product === null) {
            return 'That product is no longer available.';
        }

        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid delivery email.';
        }

        foreach ($product->fields as $field) {
            if ($field->required && trim((string) ($this->fields[$field->key] ?? '')) === '') {
                return $field->label.' is required.';
            }
        }

        if (! $requirePayment) {
            return '';
        }

        $method = $this->method();

        if ($method === null) {
            return 'Choose a payment method.';
        }

        if ($method->isWallet() && $this->walletDisabled()) {
            return 'Wallet balance is not enough.';
        }

        // shurjoPay requires a customer phone number on every transaction.
        if ($method->isGateway() && ! preg_match('/^01\d{9}$/', $this->customerPhone)) {
            return 'Enter your phone number (01XXXXXXXXX) to pay online.';
        }

        return '';
    }

    /** Wallet when it can cover the order, otherwise the first method on the list. */
    private function defaultPaymentKey(Product $product): string
    {
        $methods = $this->methods();
        $user = auth()->user();
        $wallet = $methods->firstWhere('type', \App\Enums\PaymentMethodType::Wallet);

        if ($wallet !== null && $user !== null && (int) $user->balance >= $product->price * $product->min_qty) {
            return $wallet->key;
        }

        return (string) ($methods->first(fn (PaymentMethod $m): bool => ! $m->isWallet())?->key ?? $methods->first()?->key ?? '');
    }

    public function money(int $paisa): string
    {
        return Money::format($paisa);
    }
}
