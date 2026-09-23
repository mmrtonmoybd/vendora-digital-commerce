<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Services\ContentTranslator;
use App\Support\Locales;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;

/**
 * Translate one record's administrator-written text.
 *
 * One action rather than a translated copy of every resource form: a record is the same
 * record in every language — only its wording changes — so duplicating products or pages
 * per locale would fork stock, price and slug along with the prose.
 *
 * The record's own columns hold the default language. Each additional enabled language gets
 * a tab, and a field left empty simply falls back to the default, which is what makes it
 * safe to enable Bengali before anything has been translated.
 *
 * Long fields are detected rather than declared: whatever the resource form uses for
 * `description` should be what the translator gets for `description` too, and asking every
 * resource to repeat that would go stale the first time somebody changed one.
 */
final class TranslateAction
{
    /** Fields that hold HTML rather than plain text. */
    private const RICH_FIELDS = ['body', 'description', 'delivery_info', 'features', 'answer', 'instructions', 'invite_instructions', 'customer_instructions', 'bio', 'quote'];

    /** Fields worth more than a single-line box. */
    private const LONG_FIELDS = ['short', 'short_description', 'excerpt', 'subtitle', 'seo_description', 'hero_subtitle', 'admin_delivery_note'];

    public static function make(string $name = 'translate'): Action
    {
        return Action::make($name)
            ->label('Translate')
            ->icon('heroicon-o-language')
            ->color('gray')
            ->modalHeading(fn (Model $record): string => 'Translate: '.self::recordTitle($record))
            ->modalDescription('Leave a box empty to keep the original wording in that language.')
            ->modalSubmitActionLabel('Save translations')
            ->visible(fn (): bool => Locales::isMultilingual())
            ->schema(fn (Model $record): array => [self::tabs($record)])
            ->fillForm(fn (Model $record): array => self::currentValues($record))
            ->action(function (Model $record, array $data): void {
                $translator = app(ContentTranslator::class);

                foreach (self::otherLocales() as $locale) {
                    foreach (self::fieldsOf($record) as $field) {
                        $translator->put(
                            $record::class,
                            (string) $record->getKey(),
                            $field,
                            $locale,
                            $data[self::key($locale, $field)] ?? null,
                        );
                    }
                }

                Notification::make()->success()->title('Translations saved')->send();
            });
    }

    private static function tabs(Model $record): Tabs
    {
        $tabs = [];

        foreach (self::otherLocales() as $locale) {
            $tabs[] = Tab::make(Locales::nativeName($locale))
                ->schema(array_map(
                    fn (string $field) => self::field($locale, $field, $record),
                    self::fieldsOf($record),
                ));
        }

        return Tabs::make('locales')->tabs($tabs)->columnSpanFull();
    }

    private static function field(string $locale, string $field, Model $record): TextInput|Textarea|RichEditor
    {
        $key = self::key($locale, $field);
        $label = str($field)->replace('_', ' ')->ucfirst()->toString();

        // The original is shown under every box. Translating text you cannot see is how a
        // price ends up in the name field.
        $original = (string) ($record->getAttribute($field) ?? '');
        $helper = $original === ''
            ? 'Nothing written in the default language yet.'
            : str($original)->stripTags()->squish()->limit(160)->toString();

        if (in_array($field, self::RICH_FIELDS, true)) {
            return RichEditor::make($key)->label($label)->helperText($helper)->columnSpanFull();
        }

        if (in_array($field, self::LONG_FIELDS, true)) {
            return Textarea::make($key)->label($label)->helperText($helper)->rows(3)->columnSpanFull();
        }

        return TextInput::make($key)->label($label)->helperText($helper)->columnSpanFull();
    }

    /** @return array<string, string> */
    private static function currentValues(Model $record): array
    {
        $existing = app(ContentTranslator::class)->existingFor($record::class, (string) $record->getKey());
        $values = [];

        foreach (self::otherLocales() as $locale) {
            foreach (self::fieldsOf($record) as $field) {
                $values[self::key($locale, $field)] = $existing[$locale.'.'.$field] ?? null;
            }
        }

        return $values;
    }

    /**
     * Every enabled language except the one the record's own columns are written in.
     *
     * @return array<int, string>
     */
    private static function otherLocales(): array
    {
        return array_values(array_diff(Locales::enabled(), [Locales::default()]));
    }

    /** @return array<int, string> */
    private static function fieldsOf(Model $record): array
    {
        return method_exists($record, 'translatableFields') ? $record->translatableFields() : [];
    }

    /**
     * Dots would make Filament nest the state, so the two halves are joined with a
     * double underscore instead.
     */
    private static function key(string $locale, string $field): string
    {
        return $locale.'__'.$field;
    }

    private static function recordTitle(Model $record): string
    {
        foreach (['name', 'title', 'label', 'question', 'eyebrow'] as $candidate) {
            $value = $record->getAttribute($candidate);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return class_basename($record).' #'.$record->getKey();
    }
}
