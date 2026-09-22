<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Admin;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Page;
use App\Models\PaymentMethod;
use App\Models\Post;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\User;
use App\Observers\CacheBustingObserver;
use App\Settings\MailSettings;
use App\View\Composers\StorefrontComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
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
        $this->configureModels();
        $this->configureMorphMap();
        $this->configureMailer();
        $this->configureViews();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
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
            'deposit' => \App\Models\Deposit::class,
            'invoice' => \App\Models\CustomInvoice::class,
            'payment' => \App\Models\Payment::class,
            'ticket' => \App\Models\Ticket::class,
            'withdrawal' => \App\Models\WithdrawalRequest::class,
            'commission' => \App\Models\ReferralCommission::class,
            'order_item' => \App\Models\OrderItem::class,
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
        try {
            $mail = app(MailSettings::class);
        } catch (\Throwable) {
            return;
        }

        if ($mail->mailer === 'smtp' && $mail->host !== '') {
            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $mail->host,
                'port' => $mail->port,
                'encryption' => $mail->encryption,
                'username' => $mail->username ?: null,
                'password' => $mail->password ?: null,
                'timeout' => null,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ]);
        }

        Config::set('mail.default', $mail->mailer);
        Config::set('mail.from', [
            'address' => $mail->from_address,
            'name' => $mail->from_name,
        ]);
    }

    private function configureViews(): void
    {
        View::composer('*', StorefrontComposer::class);
    }
}
