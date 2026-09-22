<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'price' => 'integer',
            'regular_price' => 'integer',
            'stock' => 'integer',
            'min_qty' => 'integer',
            'max_qty' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'requires_delivery_email' => 'boolean',
            'total_sold' => 'integer',
            'views' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            $product->slug = $product->slug ? Str::slug($product->slug) : Str::slug($product->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Category, self> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductField> */
    public function fields(): HasMany
    {
        return $this->hasMany(ProductField::class)->orderBy('sort_order');
    }

    /** @return BelongsToMany<Faq> */
    public function faqs(): BelongsToMany
    {
        return $this->belongsToMany(Faq::class)->withPivot('sort_order')->orderBy('pivot_sort_order');
    }

    /** @return BelongsToMany<self> */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'related_products', 'product_id', 'related_id')
            ->withPivot('sort_order')
            ->orderBy('pivot_sort_order');
    }

    /** @return HasMany<ProductStockLog> */
    public function stockLogs(): HasMany
    {
        return $this->hasMany(ProductStockLog::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeInStock(Builder $query): void
    {
        $query->where('stock', '>', 0);
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /** The highest quantity a single order may take, bounded by both the cap and the stock. */
    public function maxOrderableQty(): int
    {
        return max(0, min($this->max_qty, $this->stock));
    }

    public function discountPercent(): ?int
    {
        if (! $this->regular_price || $this->regular_price <= $this->price) {
            return null;
        }

        return (int) round(($this->regular_price - $this->price) / $this->regular_price * 100);
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price);
    }

    public function formattedRegularPrice(): ?string
    {
        return $this->regular_price ? Money::format($this->regular_price) : null;
    }

    public function iconSvg(): string
    {
        return config("storefront.icons.{$this->icon_key}.svg", config('storefront.icons.play.svg'));
    }

    public function badgeClass(): string
    {
        return config("storefront.badge_styles.{$this->badge_style}.class", '');
    }

    /** Snapshot stored on an order item, so history survives later edits. */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short' => $this->short,
            'image' => $this->image,
            'icon_key' => $this->icon_key,
            'gradient' => $this->gradient,
            'category_name' => $this->category?->name,
            'price' => $this->price,
            'regular_price' => $this->regular_price,
        ];
    }
}
