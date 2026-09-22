<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Settings;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Spatie\LaravelSettings\Settings;

/**
 * Shared plumbing for the settings screens.
 *
 * Each page names a settings class and describes its fields; this handles loading the
 * current values, saving them, and clearing the caches that would otherwise keep serving
 * the old ones. Saving settings that nobody sees take effect is worse than not having the
 * screen at all.
 */
abstract class BaseSettingsPage extends Page
{
    protected string $view = 'filament.admin.pages.settings.form';

    /** @var array<string, mixed> */
    public array $data = [];

    #[Locked]
    public bool $saved = false;

    /** @return class-string<Settings> */
    abstract public static function settingsClass(): string;

    /** @return array<int, mixed> */
    abstract public function formFields(): array;

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage_settings') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill($this->currentValues());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->components($this->formFields());
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(static::settingsClass());

        foreach ($data as $key => $value) {
            if (property_exists($settings, $key)) {
                $settings->{$key} = $value;
            }
        }

        $settings->save();

        // Settings feed the storefront chrome, the mailer and the gateway. Clearing here
        // means the change is live on the next request rather than up to ten minutes later.
        Cache::forget('spatie.settings.'.static::settingsClass());
        app(\App\Services\StorefrontCache::class)->invalidate();

        Notification::make()->success()->title('Settings saved')->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save'),
        ];
    }

    /** @return array<string, mixed> */
    private function currentValues(): array
    {
        $settings = app(static::settingsClass());
        $values = [];

        foreach ((new \ReflectionClass($settings))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $values[$property->getName()] = $settings->{$property->getName()};
        }

        return $values;
    }
}
