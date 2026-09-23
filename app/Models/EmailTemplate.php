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
 * @property string $key
 * @property string $name
 * @property string $subject
 * @property string $body
 * @property ?array $available_variables
 * @property bool $is_active
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class EmailTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * Substitute {{ variable }} placeholders.
     *
     * Values are inserted as-is into a Markdown body that is later escaped by the mailer,
     * so a customer name containing HTML cannot inject markup into the email.
     *
     * @param  array<string, string|int|null>  $variables
     */
    public function render(string $field, array $variables): string
    {
        $body = (string) $this->{$field};

        foreach ($variables as $key => $value) {
            $body = str_replace(['{{ '.$key.' }}', '{{'.$key.'}}'], (string) $value, $body);
        }

        return $body;
    }
}
