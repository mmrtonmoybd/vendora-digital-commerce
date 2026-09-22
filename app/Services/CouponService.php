<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;

/**
 * The outcome of checking a code. The messages match the template's wording exactly, so the
 * storefront never has to invent copy for a rejected coupon.
 */
final readonly class CouponResult
{
    public function __construct(
        public bool $ok,
        public int $discount,
        public string $message,
        public ?Coupon $coupon = null,
    ) {}

    public static function fail(string $message): self
    {
        return new self(false, 0, $message);
    }
}

final class CouponService
{
    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int}>  $lines
     */
    public function validate(?string $code, int $subtotal, ?User $user, ?string $guestEmail, array $lines = []): CouponResult
    {
        $code = mb_strtoupper(trim((string) $code));

        if ($code === '') {
            return CouponResult::fail('Invalid or expired coupon.');
        }

        $coupon = Coupon::active()->where('code', $code)->first();

        if ($coupon === null || ! $coupon->isWithinSchedule()) {
            return CouponResult::fail('Invalid or expired coupon.');
        }

        if ($subtotal < $coupon->min_order) {
            return CouponResult::fail('Minimum order '.Money::format((int) $coupon->min_order).' for this coupon.');
        }

        if (! $coupon->hasUsesLeft()) {
            return CouponResult::fail('This coupon has reached its usage limit.');
        }

        if ($this->userLimitReached($coupon, $user, $guestEmail)) {
            return CouponResult::fail('You have already used this coupon.');
        }

        // A scoped coupon must actually match something in the basket, otherwise a customer
        // could stack a gaming-only code onto a gift card order.
        $eligibleSubtotal = $this->eligibleSubtotal($coupon, $lines, $subtotal);

        if ($eligibleSubtotal <= 0) {
            return CouponResult::fail('This coupon does not apply to the items in your order.');
        }

        $discount = $coupon->discountFor($eligibleSubtotal);

        if ($discount <= 0) {
            return CouponResult::fail('This coupon does not apply to the items in your order.');
        }

        return new CouponResult(true, $discount, (string) ($coupon->label ?: 'Coupon applied!'), $coupon);
    }

    /** Record that the coupon was spent on this order. Called inside the checkout transaction. */
    public function redeem(Coupon $coupon, Order $order, int $discount): void
    {
        CouponUsage::create([
            'coupon_id' => $coupon->getKey(),
            'user_id' => $order->user_id,
            'guest_email' => $order->user_id ? null : $order->guest_email,
            'order_id' => $order->getKey(),
            'discount' => $discount,
        ]);

        $coupon->increment('used_count');
    }

    private function userLimitReached(Coupon $coupon, ?User $user, ?string $guestEmail): bool
    {
        if ($coupon->per_user_limit === null) {
            return false;
        }

        $query = $coupon->usages();

        if ($user !== null) {
            $query->where('user_id', $user->getKey());
        } elseif ($guestEmail) {
            $query->where('guest_email', $guestEmail);
        } else {
            return false;
        }

        return $query->count() >= $coupon->per_user_limit;
    }

    /**
     * The part of the subtotal the coupon is allowed to discount.
     *
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int}>  $lines
     */
    private function eligibleSubtotal(Coupon $coupon, array $lines, int $subtotal): int
    {
        if (! $coupon->isScoped() || $lines === []) {
            return $subtotal;
        }

        $productIds = $coupon->products()->pluck('products.id')->all();
        $categoryIds = $coupon->categories()->pluck('categories.id')->all();

        $allowedByCategory = $categoryIds === []
            ? []
            : Product::whereIn('category_id', $categoryIds)->pluck('id')->all();

        $allowed = array_flip([...$productIds, ...$allowedByCategory]);

        $eligible = 0;

        foreach ($lines as $line) {
            if (isset($allowed[$line['product_id']])) {
                $eligible += $line['unit_price'] * $line['quantity'];
            }
        }

        return $eligible;
    }
}
