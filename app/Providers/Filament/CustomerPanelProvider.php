<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectGuestsToStorefrontLogin;
use App\Support\SafeSettings;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color as FilamentColor;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Northwestern\FilamentTheme\NorthwesternTheme;

/**
 * The customer portal.
 *
 * It has no login page of its own: signing in happens on the storefront, in the Vendora
 * template's own design, and unauthenticated visitors are sent there.
 */
final class CustomerPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table->recordActionsPosition(RecordActionsPosition::BeforeCells);
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('customer')
            ->path('customer')
            ->authGuard('web')
            ->brandName(fn (): string => SafeSettings::siteName())
            ->brandLogo(fn () => view('filament.admin.brand'))
            ->darkModeBrandLogo(fn () => view('filament.admin.brand'))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): ?string => ($icon = SafeSettings::favicon())
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($icon)
                : null)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->maxContentWidth(Width::Full)
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->defaultThemeMode(ThemeMode::Light)
            ->colors(['primary' => FilamentColor::hex(SafeSettings::brandHex())])
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => auth()->check() ? view('filament.customer.wallet-pill')->render() : '',
            )
            ->navigationItems([
                NavigationItem::make('Back to shop')
                    ->url('/')
                    ->icon(Heroicon::ShoppingBag)
                    ->group('Overview')
                    ->sort(-1),
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Overview'),
                NavigationGroup::make()->label('Orders'),
                NavigationGroup::make()->label('Wallet'),
                NavigationGroup::make()->label('Affiliate'),
                NavigationGroup::make()->label('Support'),
            ])
            ->discoverResources(in: app_path('Filament/Customer/Resources'), for: 'App\Filament\Customer\Resources')
            ->discoverPages(in: app_path('Filament/Customer/Pages'), for: 'App\Filament\Customer\Pages')
            ->discoverWidgets(in: app_path('Filament/Customer/Widgets'), for: 'App\Filament\Customer\Widgets')
            ->plugins([
                NorthwesternTheme::make()->withoutAssetRegistration(),
                BreezyCore::make()
                    ->myProfile(hasAvatars: true, slug: 'profile', userMenuLabel: 'My profile')
                    ->enableTwoFactorAuthentication()
                    ->enableBrowserSessions(),
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
            ])
            ->authMiddleware([
                RedirectGuestsToStorefrontLogin::class,
                EnsureEmailIsVerified::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
