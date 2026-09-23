<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageTemplate;
use App\Models\Concerns\HasContentTranslations;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @property ?string $body
 * @property PageTemplate $template
 * @property bool $is_active
 * @property bool $show_in_footer
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property ?Carbon $deleted_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Page extends Model
{
    use HasContentTranslations;

    /** @use HasFactory<PageFactory> */
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
            'body',
            'seo_title',
            'seo_description',
        ];
    }

    protected function casts(): array
    {
        return [
            'template' => PageTemplate::class,
            'is_active' => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $page): void {
            $page->slug = $page->slug ? Str::slug($page->slug) : Str::slug($page->title);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
