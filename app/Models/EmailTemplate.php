<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
