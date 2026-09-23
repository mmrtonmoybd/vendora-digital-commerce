<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasContentTranslations;
use App\Support\Money;
use Database\Factories\ProductFactory;
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
    use HasContentTranslations;

    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * Text an administrator typed that a second language needs its own version of.
     *
     * Read through t(), never through the attribute directly — see the trait.
     *
     * @var array<int, string>
     */
    protected array $translatable = [
        'name',
        'short',
        'features',
        'description',
        'delivery_info',
        'customer_instructions',
        'badge_text',
        'seo_title',
        'seo_description',
    ];

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
            'fulfillment_sla_minutes' => 'integer',
            'warranty_days' => 'integer',
            'rating' => 'integer',
            'reviews_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $product): void {
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

    /** @return BelongsTo<Platform, self> */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
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

    /**
     * The bullet list on the product page, one feature per line.
     *
     * @return array<int, string>
     */
    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->features) ?: [])));
    }

    /** Stored in tenths so the column stays an integer. */
    public function ratingValue(): float
    {
        return $this->rating / 10;
    }

    /** Whole stars to fill, rounded to the nearest. */
    public function ratingStars(): int
    {
        return max(0, min(5, (int) round($this->ratingValue())));
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
            'platform_name' => $this->platform?->name,
            'access_role' => $this->access_role,
            'fulfillment_sla_minutes' => $this->fulfillment_sla_minutes,
            'warranty_days' => $this->warranty_days,
            'price' => $this->price,
            'regular_price' => $this->regular_price,
        ];
    }
}
