<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Tag extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::saving(function (self $tag): void {
            $tag->slug = $tag->slug ? Str::slug($tag->slug) : Str::slug($tag->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Post, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
