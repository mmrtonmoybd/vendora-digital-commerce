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
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $category_id
 * @property ?int $platform_id
 * @property ?string $access_role
 * @property int $fulfillment_sla_minutes
 * @property ?int $warranty_days
 * @property ?string $customer_instructions
 * @property string $name
 * @property string $slug
 * @property ?string $short
 * @property ?string $features
 * @property ?string $description
 * @property ?string $delivery_info
 * @property ?string $image
 * @property ?array $gallery
 * @property string $icon_key
 * @property string $gradient
 * @property int $price
 * @property ?int $regular_price
 * @property int $stock
 * @property int $min_qty
 * @property int $max_qty
 * @property ?string $badge_text
 * @property string $badge_style
 * @property bool $is_featured
 * @property bool $is_active
 * @property bool $requires_delivery_email
 * @property int $sort_order
 * @property int $total_sold
 * @property int $rating
 * @property int $reviews_count
 * @property int $views
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property ?Carbon $deleted_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
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
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return [
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
    }

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

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Platform, $this> */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /** @return HasMany<ProductField, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(ProductField::class)->orderBy('sort_order');
    }

    /** @return BelongsToMany<Faq, $this> */
    public function faqs(): BelongsToMany
    {
        // orderByPivot, not orderBy('pivot_sort_order'): the aliased form only exists when
        // Laravel builds the select for a relation load. Filament's relationship picker
        // joins the pivot without that alias, and ordering on it fatals the edit page.
        return $this->belongsToMany(Faq::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /** @return BelongsToMany<self, $this> */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'related_products', 'product_id', 'related_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /** @return HasMany<ProductStockLog, $this> */
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
