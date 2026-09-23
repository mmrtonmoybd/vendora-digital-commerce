<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MenuLocation;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\PaymentMethod;
use App\Settings\CommerceSettings;
use App\Settings\ContactSettings;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use App\Settings\ThemeSettings;
use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

/**
 * Everything the storefront chrome needs, resolved once and cached.
 *
 * The header, the mobile drawer and the four footer columns appear on every page; reading
 * them from the database on each request would be five queries before any page content.
 * Model observers bump the cache version, so an edit in the panel shows up immediately.
 */
final class StorefrontData
{
    public function __construct(private readonly StorefrontCache $cache) {}

    /** @return Collection<int, MenuItem> */
    public function menu(MenuLocation $location): Collection
    {
        return $this->cache->remember("menu:{$location->value}", function () use ($location): Collection {
            $menu = Menu::with(['items' => fn ($q) => $q->where('is_active', true)->with('children')])
                ->where('location', $location->value)
                ->where('is_active', true)
                ->first();

            $items = $menu?->items ?? collect();

            // One lookup for every link target in the menu, instead of one per item.
            MenuItem::preloadTargets($items);

            return $items;
        });
    }

    /** @return Collection<int, Menu> */
    public function footerMenus(): Collection
    {
        return $this->cache->remember('menus:footer', function (): Collection {
            return Menu::with(['items' => fn ($q) => $q->where('is_active', true)])
                ->whereIn('location', [
                    MenuLocation::Footer1->value,
                    MenuLocation::Footer2->value,
                    MenuLocation::Footer3->value,
                    MenuLocation::Footer4->value,
                ])
                ->where('is_active', true)
                ->orderBy('location')
                ->get()
                ->each(fn (Menu $menu) => MenuItem::preloadTargets($menu->items));
        });
    }

    /** @return Collection<int, Category> */
    public function navCategories(): Collection
    {
        return $this->cache->remember('categories:nav', fn (): Collection => Category::query()
            ->active()
            ->where('show_in_nav', true)
            ->ordered()
            ->get());
    }

    /** @return Collection<int, Category> */
    public function tileCategories(): Collection
    {
        return $this->cache->remember('categories:tiles', fn (): Collection => Category::query()
            ->active()
            ->whereNull('parent_id')
            ->ordered()
            ->get());
    }

    /** @return Collection<int, PaymentMethod> */
    public function footerPaymentMethods(): Collection
    {
        return $this->cache->remember('payment-methods:footer', fn (): Collection => PaymentMethod::query()
            ->active()
            ->where('type', '!=', 'wallet')
            ->orderBy('sort_order')
            ->get());
    }

    /**
     * A settings value in the language this request is being answered in.
     *
     * Almost all the storefront's copy is editable in the panel, so translating only the
     * developer-written strings would leave a Bengali visitor reading an English hero and
     * English category headings. Falls back to what the administrator typed when a
     * translation has not been written yet.
     *
     *     {{ $store->st($home, 'hero_badge') }}
     */
    public function st(Settings $settings, string $field): mixed
    {
        $translated = app(ContentTranslator::class)->forSetting($settings::group(), $field);

        return $translated ?? $settings->{$field};
    }

    /**
     * A multi-line settings value, translated and split into trimmed lines.
     *
     * The hero feature list and similar fields are edited as one textarea, so the split has
     * to happen after translation rather than before it.
     *
     * @return array<int, string>
     */
    public function stLines(Settings $settings, string $field): array
    {
        $value = (string) $this->st($settings, $field);

        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $value) ?: [])));
    }

    public function general(): GeneralSettings
    {
        return app(GeneralSettings::class);
    }

    public function contact(): ContactSettings
    {
        return app(ContactSettings::class);
    }

    public function theme(): ThemeSettings
    {
        return app(ThemeSettings::class);
    }

    public function seo(): SeoSettings
    {
        return app(SeoSettings::class);
    }

    public function commerce(): CommerceSettings
    {
        return app(CommerceSettings::class);
    }

    /**
     * Where the floating support button goes.
     *
     * @return array{url: string, label: string}
     */
    public function supportTarget(): array
    {
        $contact = $this->contact();
        $message = rawurlencode((string) $contact->floating_support_message);

        return match ($contact->floating_support_target) {
            'telegram' => [
                'url' => $contact->telegram ? 'https://t.me/'.ltrim($contact->telegram, '@') : '#',
                'label' => 'Telegram support',
            ],
            'ticket' => ['url' => url('/customer/tickets/create'), 'label' => 'Open a ticket'],
            default => [
                'url' => $contact->whatsapp
                    ? 'https://wa.me/'.preg_replace('/\D/', '', $contact->whatsapp).'?text='.$message
                    : '#',
                'label' => 'WhatsApp support',
            ],
        };
    }
}
