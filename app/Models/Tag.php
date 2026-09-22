<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

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

    /** @return BelongsToMany<Post> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
