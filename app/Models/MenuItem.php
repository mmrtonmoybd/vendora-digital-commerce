<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuLinkType;
use App\Models\Concerns\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $menu_id
 * @property ?int $parent_id
 * @property string $label
 * @property MenuLinkType $link_type
 * @property ?int $link_target_id
 * @property ?string $url
 * @property bool $open_in_new_tab
 * @property int $sort_order
 * @property bool $is_active
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class MenuItem extends Model
{
    use HasContentTranslations;

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
            'label',
        ];
    }

    protected function casts(): array
    {
        return [
            'link_type' => MenuLinkType::class,
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Resolve the item to a real URL.
     *
     * A target that has since been deleted or unpublished degrades to the home page rather
     * than rendering a broken link or throwing on a public page.
     */
    public function resolvedUrl(): string
    {
        return match ($this->link_type) {
            MenuLinkType::Url => (string) ($this->url ?: '#'),
            MenuLinkType::Route => $this->safeRoute((string) $this->url),
            MenuLinkType::Page => $this->modelUrl(Page::class, 'page.show'),
            MenuLinkType::Category => $this->modelUrl(Category::class, 'category.show'),
            MenuLinkType::Product => $this->modelUrl(Product::class, 'product.show'),
            MenuLinkType::Post => $this->modelUrl(Post::class, 'blog.show'),
        };
    }

    private function safeRoute(string $name): string
    {
        return Route::has($name) ? route($name) : url('/');
    }

    /** @param class-string<Model> $model */
    private function modelUrl(string $model, string $routeName): string
    {
        $record = $this->link_target_id ? $model::find($this->link_target_id) : null;

        return $record ? route($routeName, $record) : url('/');
    }

    public function isActiveUrl(): bool
    {
        return url()->current() === $this->resolvedUrl();
    }
}
