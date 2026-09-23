<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Where a customer came from: TikTok, Facebook, Google, a direct visit.
 *
 * The matching rules are data, not code, so marketing can add a new channel (a newsletter,
 * an influencer's short link) without a deploy.
 */
final class OrderSource extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'hosts' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $source): void {
            $source->key = Str::slug($source->key ?: $source->name);
        });

        // Exactly one default, enforced here rather than hoped for.
        self::saved(function (self $source): void {
            if ($source->is_default) {
                self::whereKeyNot($source->getKey())->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
