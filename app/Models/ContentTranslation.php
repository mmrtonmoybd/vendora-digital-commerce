<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $translatable_type
 * @property string $translatable_id
 * @property string $field
 * @property string $locale
 * @property ?string $value
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class ContentTranslation extends Model
{
    protected $guarded = ['id'];

    /** Settings groups are stored under this type rather than a model class name. */
    public const SETTINGS_TYPE = 'settings';
}
