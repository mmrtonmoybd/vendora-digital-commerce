<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasContentTranslations;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $excerpt
 * @property ?string $body
 * @property ?string $cover
 * @property string $gradient
 * @property ?int $blog_category_id
 * @property ?int $author_id
 * @property ?Carbon $published_at
 * @property bool $is_published
 * @property int $read_minutes
 * @property int $views
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property ?Carbon $deleted_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Post extends Model
{
    use HasContentTranslations;

    /** @use HasFactory<PostFactory> */
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
            'title',
            'excerpt',
            'body',
            'seo_title',
            'seo_description',
        ];
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'read_minutes' => 'integer',
            'views' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $post): void {
            $post->slug = $post->slug ? Str::slug($post->slug) : Str::slug($post->title);
            $post->read_minutes = max(1, (int) ceil(str_word_count(strip_tags((string) $post->body)) / 200));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<BlogCategory, $this> */
    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'author_id');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @param Builder<self> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
