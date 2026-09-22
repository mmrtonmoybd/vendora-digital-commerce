<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Http\Middleware\EnforceAdminIpAllowlist;
use App\Http\Middleware\EnforceAdminTwoFactor;
use App\Models\Admin;
use App\Support\SafeSettings;
use Awcodes\Overlook\OverlookPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color as FilamentColor;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Northwestern\FilamentTheme\NorthwesternTheme;

final class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Row actions sit on the left of every table, collapsed into one three-dot menu.
        // With a dozen actions on an order, a row of buttons would push the data off the
        // screen; a single trigger in a fixed position is also easier to hit repeatedly.
        Table::configureUsing(function (Table $table): void {
            $table->recordActionsPosition(RecordActionsPosition::BeforeCells);
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('admin')
            ->authPasswordBroker('admins')
            ->login()
            ->passwordReset()
            ->spa()
            ->spaUrlExceptions(fn (): array => [
                // Responses that are a file, not a page. With wire:navigate on their links
                // Livewire would swap the raw bytes into the document.
                url('/admin/orders/*/invoice'),
                url('/admin/reports/export/*'),
            ])
            ->brandName(fn (): string => SafeSettings::siteName())
            ->brandLogo(fn () => view('filament.admin.brand'))
            ->darkModeBrandLogo(fn () => view('filament.admin.brand'))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): ?string => ($icon = SafeSettings::favicon())
                ? Storage::disk('public')->url($icon)
                : null)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->maxContentWidth(Width::Full)
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->unsavedChangesAlerts()
            ->defaultThemeMode(ThemeMode::Light)
            ->colors(['primary' => FilamentColor::hex(SafeSettings::adminPrimaryHex())])
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => auth('admin')->check() ? view('filament.admin.footer')->render() : '',
            )
            ->userMenuItems([
                Action::make('viewStore')
                    ->label('View store')
                    ->icon(Heroicon::ShoppingBag)
                    ->url('/', shouldOpenInNewTab: true),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->pages([Dashboard::class])
            ->navigationGroups([
                NavigationGroup::make()->label('Overview'),
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Payments'),
                NavigationGroup::make()->label('Customers'),
                NavigationGroup::make()->label('Catalog'),
                NavigationGroup::make()->label('Support'),
                NavigationGroup::make()->label('Affiliate'),
                NavigationGroup::make()->label('Content'),
                NavigationGroup::make()->label('Reports'),
                NavigationGroup::make()->label('Settings'),
                NavigationGroup::make()->label('System'),
            ])
            ->plugins([
                NorthwesternTheme::make()->withoutAssetRegistration(),
                AuthDesignerPlugin::make()
                    ->login(fn ($config) => $config
                        ->mediaPosition(MediaPosition::Left)
                        ->blur(1)
                        ->mediaSize('60%')
                    ),
                BreezyCore::make()
                    ->myProfile(hasAvatars: true, slug: 'profile', userMenuLabel: 'My profile')
                    ->enableTwoFactorAuthentication()
                    ->enableBrowserSessions(),
                GlobalSearchModalPlugin::make(),
                OverlookPlugin::make()
                    ->sort(2)
                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4, 'xl' => 6]),
                FilamentShieldPlugin::make()
                    ->gridColumns(['default' => 2])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns(['default' => 2])
                    ->resourceCheckboxListColumns(['default' => 2])
                    ->navigationLabel('Roles & permissions')
                    ->navigationGroup('Settings')
                    ->navigationSort(90)
                    ->navigationIcon(Heroicon::ShieldCheck),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->switchable(true)
                    ->users(fn (): array => Admin::query()->pluck('email', 'name')->all()),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnforceAdminIpAllowlist::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnforceAdminTwoFactor::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
