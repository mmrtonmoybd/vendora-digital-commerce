<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use App\Models\ContentTranslation;
use App\Services\ContentTranslator;
use App\Services\StorefrontCache;
use App\Settings\ContactSettings;
use App\Settings\GeneralSettings;
use App\Settings\HomepageSettings;
use App\Settings\SeoSettings;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\LaravelSettings\Settings;
use UnitEnum;

/**
 * Bengali (and any other language) versions of the copy that lives in Settings.
 *
 * Most of the storefront's words are not in the codebase — the hero, the section headings,
 * the tagline, the footer line are all typed by an administrator. Translating only the
 * developer-written strings would leave a Bengali visitor reading an English hero, so this
 * screen covers the rest.
 *
 * Fields that are not words — URLs, pixel ids, colours, toggles — are deliberately absent.
 */
final class ContentTranslationsPage extends Page
{
    protected string $view = 'filament.admin.pages.settings.form';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Storefront copy translations';

    /** @var array<string, mixed> */
    public array $data = [];

    /**
     * The settings copy worth translating, grouped the way the screens are.
     *
     * @var array<string, array{class: class-string<Settings>, fields: array<int, string>}>
     */
    private const GROUPS = [
        'Shop identity' => [
            'class' => GeneralSettings::class,
            'fields' => ['tagline', 'support_hours', 'copyright_text'],
        ],
        'Home page' => [
            'class' => HomepageSettings::class,
            'fields' => [
                'hero_badge', 'hero_title_before', 'hero_title_script', 'hero_title_after',
                'hero_subtitle', 'hero_features', 'hero_primary_text', 'hero_secondary_text',
                'categories_eyebrow', 'categories_title',
                'testimonials_eyebrow', 'testimonials_title', 'testimonials_subtitle',
                'faq_eyebrow', 'faq_title', 'faq_subtitle', 'faq_button_text',
                'cta_title', 'cta_subtitle', 'cta_primary_text', 'cta_secondary_text',
            ],
        ],
        'Contact' => [
            'class' => ContactSettings::class,
            'fields' => ['address', 'floating_support_message'],
        ],
        'SEO' => [
            'class' => SeoSettings::class,
            'fields' => ['default_keywords'],
        ],
    ];

    private const LONG_FIELDS = ['hero_subtitle', 'hero_features', 'faq_subtitle', 'cta_subtitle', 'address', 'default_keywords', 'floating_support_message', 'testimonials_subtitle'];

    public static function canAccess(): bool
    {
        return (auth('admin')->user()?->can('manage_settings') ?? false) && Locales::isMultilingual();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Locales::isMultilingual();
    }

    public function mount(): void
    {
        $this->form->fill($this->currentValues());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([$this->localeTabs()]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $translator = app(ContentTranslator::class);

        foreach ($this->otherLocales() as $locale) {
            foreach (self::GROUPS as $group) {
                $settingsGroup = $group['class']::group();

                foreach ($group['fields'] as $field) {
                    $translator->put(
                        ContentTranslation::SETTINGS_TYPE,
                        $settingsGroup,
                        $field,
                        $locale,
                        $data[$this->key($locale, $settingsGroup, $field)] ?? null,
                    );
                }
            }
        }

        // The storefront serves this copy from its own cache; without this the translation
        // would not appear until the cache expired on its own.
        app(StorefrontCache::class)->invalidate();

        Notification::make()->success()->title('Translations saved')->send();
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Save translations')->submit('save')];
    }

    private function localeTabs(): Tabs
    {
        $tabs = [];

        foreach ($this->otherLocales() as $locale) {
            $sections = [];

            foreach (self::GROUPS as $heading => $group) {
                $settingsGroup = $group['class']::group();
                $settings = app($group['class']);

                $sections[] = Section::make($heading)
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema(array_map(
                        fn (string $field) => $this->field($locale, $settingsGroup, $field, $settings),
                        $group['fields'],
                    ));
            }

            $tabs[] = Tab::make(Locales::nativeName($locale))->schema($sections);
        }

        return Tabs::make('locales')->tabs($tabs)->columnSpanFull();
    }

    private function field(string $locale, string $group, string $field, Settings $settings): TextInput|Textarea
    {
        $key = $this->key($locale, $group, $field);
        $label = str($field)->replace('_', ' ')->ucfirst()->toString();

        $original = (string) ($settings->{$field} ?? '');
        $helper = $original === ''
            ? 'Nothing written in the default language yet.'
            : str($original)->squish()->limit(160)->toString();

        return in_array($field, self::LONG_FIELDS, true)
            ? Textarea::make($key)->label($label)->helperText($helper)->rows(3)->columnSpanFull()
            : TextInput::make($key)->label($label)->helperText($helper)->columnSpanFull();
    }

    /** @return array<string, mixed> */
    private function currentValues(): array
    {
        $translator = app(ContentTranslator::class);
        $values = [];

        foreach (self::GROUPS as $group) {
            $settingsGroup = $group['class']::group();
            $existing = $translator->existingFor(ContentTranslation::SETTINGS_TYPE, $settingsGroup);

            foreach ($this->otherLocales() as $locale) {
                foreach ($group['fields'] as $field) {
                    $values[$this->key($locale, $settingsGroup, $field)] = $existing[$locale.'.'.$field] ?? null;
                }
            }
        }

        return $values;
    }

    /** @return array<int, string> */
    private function otherLocales(): array
    {
        return array_values(array_diff(Locales::enabled(), [Locales::default()]));
    }

    private function key(string $locale, string $group, string $field): string
    {
        return $locale.'__'.$group.'__'.$field;
    }
}
