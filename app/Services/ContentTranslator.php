<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContentTranslation;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Looks up the administrator-written text for the current language.
 *
 * Every translation for a locale is loaded in one query and held for the request, because
 * the alternative — a lookup per field per record — would put a query behind every product
 * name on a category page. The map is small (a few hundred short strings even for a large
 * catalogue) and is cached until something is saved.
 *
 * A missing translation is not an error: it falls back to the text the administrator
 * originally typed, so turning Bengali on does not blank out a shop that has not been
 * translated yet.
 */
final class ContentTranslator
{
    private const CACHE_TTL = 3600;

    /** @var array<string, array<string, string>> locale => "type|id|field" => value */
    private array $loaded = [];

    /** The translated value, or null when there is not one. */
    public function get(string $type, string|int $id, string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        // The default language is what the base columns already hold; looking it up would
        // be a query to discover we already had the answer.
        if ($locale === Locales::default()) {
            return null;
        }

        $map = $this->map($locale);
        $value = $map[$type.'|'.$id.'|'.$field] ?? null;

        return $value === null || $value === '' ? null : $value;
    }

    public function forModel(Model $model, string $field, ?string $locale = null): ?string
    {
        return $this->get($model::class, (string) $model->getKey(), $field, $locale);
    }

    public function forSetting(string $group, string $field, ?string $locale = null): ?string
    {
        return $this->get(ContentTranslation::SETTINGS_TYPE, $group, $field, $locale);
    }

    /**
     * Write one translation, or clear it when the administrator empties the box.
     *
     * An empty value deletes the row rather than storing "" — otherwise a cleared field
     * would translate to blank instead of falling back to the original text.
     */
    public function put(string $type, string|int $id, string $field, string $locale, ?string $value): void
    {
        $key = ['translatable_type' => $type, 'translatable_id' => (string) $id, 'field' => $field, 'locale' => $locale];

        if ($value === null || trim($value) === '') {
            ContentTranslation::query()->where($key)->delete();
        } else {
            ContentTranslation::query()->updateOrCreate($key, ['value' => $value]);
        }

        $this->flush();
    }

    /**
     * Everything already stored for one record, keyed by "locale.field".
     *
     * Used to fill the translation form, so it reads from the database rather than the
     * request-scoped map — an administrator who has just saved must see what they saved.
     *
     * @return array<string, string>
     */
    public function existingFor(string $type, string|int $id): array
    {
        return ContentTranslation::query()
            ->where('translatable_type', $type)
            ->where('translatable_id', (string) $id)
            ->get()
            ->mapWithKeys(fn (ContentTranslation $row): array => [
                $row->locale.'.'.$row->field => (string) $row->value,
            ])
            ->all();
    }

    public function flush(): void
    {
        $this->loaded = [];

        foreach (Locales::codes() as $locale) {
            Cache::forget($this->cacheKey($locale));
        }
    }

    /** @return array<string, string> */
    private function map(string $locale): array
    {
        if (isset($this->loaded[$locale])) {
            return $this->loaded[$locale];
        }

        $map = Cache::remember(
            $this->cacheKey($locale),
            self::CACHE_TTL,
            fn (): array => $this->load($locale),
        );

        return $this->loaded[$locale] = $map;
    }

    /** @return array<string, string> */
    private function load(string $locale): array
    {
        try {
            return ContentTranslation::query()
                ->where('locale', $locale)
                ->get(['translatable_type', 'translatable_id', 'field', 'value'])
                ->mapWithKeys(fn (ContentTranslation $row): array => [
                    $row->translatable_type.'|'.$row->translatable_id.'|'.$row->field => (string) $row->value,
                ])
                ->all();
        } catch (\Throwable) {
            // The panel boots during `migrate` on a fresh install, before this table exists.
            return [];
        }
    }

    private function cacheKey(string $locale): string
    {
        return 'content-translations:'.$locale;
    }
}
