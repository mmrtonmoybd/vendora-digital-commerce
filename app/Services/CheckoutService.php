<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\WalletTxType;
use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Exceptions\DomainException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Settings\CommerceSettings;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * One line of an order being placed, already priced.
 */
final readonly class CheckoutLine
{
    /** @param array<string, string> $fields */
    public function __construct(
        public Product $product,
        public int $quantity,
        public array $fields,
        public ?string $deliveryEmail = null,
    ) {}

    public function lineTotal(): int
    {
        return $this->product->price * $this->quantity;
    }
}

/**
 * Turns a basket into an order.
 *
 * Everything the browser told us is re-checked here — stock, quantity bounds, coupon,
 * whether the product and the payment method are still active — because the buy modal and
 * the checkout page are both just hints about what the customer wants.
 */
final class CheckoutService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly WalletService $wallet,
        private readonly CouponService $coupons,
        private readonly CartService $carts,
        private readonly CommerceSettings $settings,
    ) {}

    /**
     * @param  array<string, string>  $fields
     */
    public function placeFromBuyNow(
        Product $product,
        int $quantity,
        array $fields,
        string $deliveryEmail,
        PaymentMethod $method,
        ?string $couponCode,
        ?User $user,
        ?string $guestEmail = null,
        ?string $customerPhone = null,
        ?string $customerNote = null,
    ): Order {
        $line = new CheckoutLine($product, $quantity, $fields, $deliveryEmail);

        return $this->place([$line], $deliveryEmail, $method, $couponCode, $user, $guestEmail, $customerPhone, $customerNote);
    }

    public function placeFromCart(
        Cart $cart,
        string $deliveryEmail,
        PaymentMethod $method,
        ?string $couponCode,
        ?User $user,
        ?string $guestEmail = null,
        ?string $customerPhone = null,
        ?string $customerNote = null,
    ): Order {
        $items = $cart->items()->with('product')->get();

        if ($items->isEmpty()) {
            throw new DomainException('Your cart is empty.');
        }

        $lines = $items->map(function ($item) use ($deliveryEmail): CheckoutLine {
            if ($item->product === null) {
                throw new DomainException('One of the products in your cart is no longer available.');
            }

            return new CheckoutLine(
                $item->product,
                $item->quantity,
                $item->fields ?? [],
                $item->delivery_email ?: $deliveryEmail,
            );
        })->all();

        $order = $this->place($lines, $deliveryEmail, $method, $couponCode, $user, $guestEmail, $customerPhone, $customerNote);

        $cart->items()->delete();

        return $order;
    }

    /**
     * @param  array<int, CheckoutLine>  $lines
     */
    private function place(
        array $lines,
        string $deliveryEmail,
        PaymentMethod $method,
        ?string $couponCode,
        ?User $user,
        ?string $guestEmail,
        ?string $customerPhone,
        ?string $customerNote,
    ): Order {
        if ($lines === []) {
            throw new DomainException('Your cart is empty.');
        }

        if ($user === null && ! $this->settings->guest_checkout_enabled) {
            throw new DomainException('Please sign in to complete your order.');
        }

        if (! $method->is_active || ! $method->available_for_orders) {
            throw new DomainException('That payment method is not available right now.');
        }

        if ($method->isWallet() && $user === null) {
            throw new DomainException('Sign in to pay from your wallet.');
        }

        foreach ($lines as $line) {
            $this->assertLineIsSane($line);
        }

        $subtotal = array_sum(array_map(fn (CheckoutLine $line): int => $line->lineTotal(), $lines));

        $couponResult = $couponCode
            ? $this->coupons->validate($couponCode, $subtotal, $user, $guestEmail, array_map(
                fn (CheckoutLine $line): array => [
                    'product_id' => $line->product->getKey(),
                    'quantity' => $line->quantity,
                    'unit_price' => $line->product->price,
                ],
                $lines,
            ))
            : null;

        if ($couponCode && $couponResult !== null && ! $couponResult->ok) {
            throw new DomainException($couponResult->message);
        }

        $discount = $couponResult?->discount ?? 0;
        $fee = $method->feeFor($subtotal - $discount);
        $total = max(0, $subtotal - $discount + $fee);

        if (! $method->acceptsAmount($total)) {
            throw new DomainException("{$method->name} does not accept an amount of ".Money::format($total).'.');
        }

        $order = DB::transaction(function () use ($lines, $deliveryEmail, $method, $couponResult, $user, $guestEmail, $customerPhone, $customerNote, $subtotal, $discount, $fee, $total): Order {
            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'user_id' => $user?->getKey(),
                'guest_email' => $user === null ? $guestEmail ?: $deliveryEmail : null,
                'delivery_email' => $deliveryEmail,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'fee' => $fee,
                'total' => $total,
                'coupon_id' => $couponResult?->coupon?->getKey(),
                'payment_method_id' => $method->getKey(),
                'payment_status' => OrderPaymentStatus::Unpaid,
                'status' => OrderStatus::PendingPayment,
                'customer_note' => $customerNote,
                'customer_phone' => $customerPhone,
                'ip' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
            ]);

            foreach ($lines as $line) {
                // Reserve before writing the item: if the last key just went, the whole
                // order rolls back rather than promising something we cannot deliver.
                $this->stock->reserve($line->product, $line->quantity, $order);

                $order->items()->create([
                    'product_id' => $line->product->getKey(),
                    'product_snapshot' => $line->product->toSnapshot(),
                    'unit_price' => $line->product->price,
                    'quantity' => $line->quantity,
                    'line_total' => $line->lineTotal(),
                    'field_values' => $line->fields,
                    'delivery_email' => $line->deliveryEmail ?: $deliveryEmail,
                ]);
            }

            if ($couponResult?->coupon !== null) {
                $this->coupons->redeem($couponResult->coupon, $order, $discount);
            }

            $order->histories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::PendingPayment,
                'note' => 'Order placed.',
                'actor_type' => $user?->getMorphClass(),
                'actor_id' => $user?->getKey(),
            ]);

            // A wallet order is settled here and now, inside the same transaction that
            // reserved the stock, so the two can never disagree.
            if ($method->isWallet() && $user !== null) {
                $this->wallet->debit(
                    $user,
                    $total,
                    WalletTxType::OrderPayment,
                    $order,
                    "order-payment:{$order->getKey()}",
                    "Payment for order {$order->order_number}",
                );

                $order->forceFill([
                    'payment_status' => OrderPaymentStatus::Paid,
                    'status' => OrderStatus::Pending,
                    'paid_at' => now(),
                ])->save();

                $order->histories()->create([
                    'from_status' => OrderStatus::PendingPayment,
                    'to_status' => OrderStatus::Pending,
                    'note' => 'Paid from wallet.',
                    'actor_type' => $user->getMorphClass(),
                    'actor_id' => $user->getKey(),
                ]);
            }

            return $order;
        }, 3);

        OrderPlaced::dispatch($order);

        if ($order->isPaid()) {
            OrderPaid::dispatch($order);
        }

        return $order->load('items');
    }

    private function assertLineIsSane(CheckoutLine $line): void
    {
        $product = $line->product;

        if (! $product->is_active) {
            throw new DomainException("{$product->name} is no longer available.");
        }

        if ($line->quantity < $product->min_qty) {
            throw new DomainException("{$product->name} has a minimum order of {$product->min_qty}.");
        }

        if ($line->quantity > $product->max_qty) {
            throw new DomainException("{$product->name} is limited to {$product->max_qty} per order.");
        }

        if ($product->stock < $line->quantity) {
            throw new DomainException("{$product->name} only has {$product->stock} left in stock.");
        }

        foreach ($product->fields as $field) {
            $value = trim((string) ($line->fields[$field->key] ?? ''));

            if ($field->required && $value === '') {
                throw new DomainException("{$field->label} is required.");
            }
        }
    }

    /** {prefix}-{YYMM}-{000001}, sequential within the month. */
    private function nextOrderNumber(): string
    {
        $prefix = $this->settings->order_prefix;
        $period = now()->format('ym');
        $stem = "{$prefix}-{$period}-";

        $last = Order::where('order_number', 'like', $stem.'%')
            ->orderByDesc('id')
            ->value('order_number');

        $sequence = $last ? ((int) substr((string) $last, -6)) + 1 : 1;

        return $stem.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
