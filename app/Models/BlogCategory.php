<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasContentTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class BlogCategory extends Model
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
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $category): void {
            $category->slug = $category->slug ? Str::slug($category->slug) : Str::slug($category->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Post> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
