<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuLinkType;
use App\Models\Concerns\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

final class MenuItem extends Model
{
    use HasContentTranslations;

    protected $guarded = ['id'];

    /**
     * Text an administrator typed that a second language needs its own version of.
     *
     * Read through t(), never through the attribute directly — see the trait.
     *
     * @var array<int, string>
     */
    protected array $translatable = [
        'label',
    ];

    protected function casts(): array
    {
        return [
            'link_type' => MenuLinkType::class,
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Menu, self> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return BelongsTo<self, self> */
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
