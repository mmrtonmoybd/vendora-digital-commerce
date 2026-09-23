<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CustomInvoice;
use App\Models\Deposit;
use App\Models\HeroSlide;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Post;
use App\Models\Product;
use App\Models\ReferralCommission;
use App\Models\Testimonial;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Observers\CacheBustingObserver;
use App\Observers\SlugHistoryObserver;
use App\Settings\MailSettings;
use App\View\Composers\StorefrontComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configureModels();
        $this->configureMorphMap();
        $this->configureMailer();
        $this->configureViews();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * A super admin is allowed everything.
     *
     * Shield writes one permission per action per resource, which is the right granularity
     * for the other roles but would mean the owner's access silently depends on somebody
     * having re-run shield:generate after adding a resource. The bypass is explicit, and
     * only the super_admin role on the admin guard gets it.
     */
    private function configureAuthorization(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            if (! $user instanceof Admin) {
                return null;
            }

            // null, not false: anything other than a super admin falls through to the
            // policy rather than being denied here.
            return $user->hasRole('super_admin') ? true : null;
        });
    }

    /**
     * Strict models outside production.
     *
     * Lazy loading is the single easiest way to turn one page into two hundred queries, and
     * it is far cheaper to find in development than in an APM chart.
     */
    private function configureModels(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::unguard(false);

        foreach ([
            Category::class, Product::class, PaymentMethod::class, Page::class,
            Menu::class, MenuItem::class, HeroSlide::class, Testimonial::class, Post::class,
        ] as $model) {
            $model::observe(CacheBustingObserver::class);
        }

        // Renaming a slug in the panel silently breaks every link already shared or
        // indexed. These four have public addresses built from their slug, so a rename is
        // recorded and answered with a 301 rather than a 404.
        foreach ([Product::class, Category::class, Post::class, Page::class] as $model) {
            $model::observe(SlugHistoryObserver::class);
        }
    }

    /**
     * Short, stable names in every morph column.
     *
     * Without this the class name is written into payments, wallet entries and activity
     * logs, and moving a class between namespaces silently orphans years of history.
     */
    private function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'admin' => Admin::class,
            'order' => Order::class,
            'product' => Product::class,
            'deposit' => Deposit::class,
            'invoice' => CustomInvoice::class,
            'payment' => Payment::class,
            'ticket' => Ticket::class,
            'withdrawal' => WithdrawalRequest::class,
            'commission' => ReferralCommission::class,
            'order_item' => OrderItem::class,
        ]);
    }

    /**
     * Mail credentials come from the panel, not the environment.
     *
     * The operator changes SMTP hosts without a deploy, so the mailer is configured from
     * the settings table at boot. A settings table that is not there yet (during the very
     * first migrate) must not take the application down.
     */
    private function configureMailer(): void
    {
        // Spatie settings load their properties on first access, not on resolve, so the
        // whole read has to sit inside the guard — including the property access.
        try {
            $mail = app(MailSettings::class);

            $mailer = $mail->mailer;
            $host = $mail->host;

            if ($mailer === 'smtp' && $host !== '') {
                Config::set('mail.mailers.smtp', [
                    'transport' => 'smtp',
                    'host' => $host,
                    'port' => $mail->port,
                    'encryption' => $mail->encryption,
                    'username' => $mail->username ?: null,
                    'password' => $mail->password ?: null,
                    'timeout' => null,
                    'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
                ]);
            }

            Config::set('mail.default', $mailer);
            Config::set('mail.from', [
                'address' => $mail->from_address,
                'name' => $mail->from_name,
            ]);
        } catch (\Throwable) {
            // No settings table yet (a fresh clone, or a test refreshing the database).
            // The .env mailer stays in force, which is what we want during install.
        }
    }

    private function configureViews(): void
    {
        View::composer('*', StorefrontComposer::class);
    }
}
