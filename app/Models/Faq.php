<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FaqGroup;
use App\Models\Concerns\HasContentTranslations;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Faq extends Model
{
    use HasContentTranslations;

    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Text an administrator typed that a second language needs its own version of.
     *
     * Read through t(), never through the attribute directly — see the trait.
     *
     * @var array<int, string>
     */
    protected array $translatable = [
        'question',
        'answer',
    ];

    protected function casts(): array
    {
        return [
            'group' => FaqGroup::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
