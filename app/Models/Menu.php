<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuLocation;
use App\Models\Concerns\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Menu extends Model
{
    use HasContentTranslations;

    protected $guarded = ['id'];

    /**
     * Text an administrator typed that a second language needs its own version of.
     *
     * @var array<int, string>
     */
    protected array $translatable = [
        'name',
        'column_title',
    ];

    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<MenuItem> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<MenuItem> */
    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }
}
