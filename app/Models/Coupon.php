<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Coupon extends Model
{
    /** @use HasFactory<\Database\Factories\CouponFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_order' => 'integer',
            'max_discount' => 'integer',
            'usage_limit' => 'integer',
            'per_user_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $coupon): void {
            $coupon->code = Str::upper(trim($coupon->code));
        });
    }

    /** @return BelongsToMany<Product> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** @return BelongsToMany<Category> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /** @return HasMany<CouponUsage> */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isWithinSchedule(): bool
    {
        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        return ! ($this->expires_at && $now->gt($this->expires_at));
    }

    public function hasUsesLeft(): bool
    {
        return $this->usage_limit === null || $this->used_count < $this->usage_limit;
    }

    /** The discount this coupon gives on a subtotal, capped so it can never exceed it. */
    public function discountFor(int $subtotal): int
    {
        $discount = $this->type === CouponType::Percent
            ? Money::percent($subtotal, (int) $this->value)
            : (int) $this->value;

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $subtotal);
    }

    /** Whether this coupon is limited to certain products or categories at all. */
    public function isScoped(): bool
    {
        return $this->products()->exists() || $this->categories()->exists();
    }
}
