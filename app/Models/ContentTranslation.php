<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentTranslation extends Model
{
    protected $guarded = ['id'];

    /** Settings groups are stored under this type rather than a model class name. */
    public const SETTINGS_TYPE = 'settings';
}
