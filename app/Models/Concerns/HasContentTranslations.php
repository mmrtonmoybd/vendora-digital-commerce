<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\ContentTranslator;

/**
 * Gives a model a translated reading of its own text columns.
 *
 * Deliberately an explicit `t()` call rather than an override of getAttribute: the admin
 * panel reads the same attributes to fill its edit forms, and a model that silently handed
 * back Bengali would let an administrator working in Bengali overwrite the English original
 * without ever seeing it.
 *
 * A model using this declares which fields are translatable:
 *
 *     protected array $translatable = ['name', 'short', 'description'];
 */
trait HasContentTranslations
{
    /** The translated value for the current language, falling back to the stored one. */
    public function t(string $field): mixed
    {
        if (! in_array($field, $this->translatableFields(), true)) {
            return $this->getAttribute($field);
        }

        return app(ContentTranslator::class)->forModel($this, $field) ?? $this->getAttribute($field);
    }

    /** @return array<int, string> */
    public function translatableFields(): array
    {
        return $this->translatable ?? [];
    }
}
