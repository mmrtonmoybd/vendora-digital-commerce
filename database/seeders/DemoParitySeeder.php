<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\FaqGroup;
use App\Enums\MenuLinkType;
use App\Enums\MenuLocation;
use App\Enums\PageTemplate;
use App\Enums\PaymentMethodType;
use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Menu;
use App\Models\Page;
use App\Models\PaymentMethod;
use App\Models\Platform;
use App\Models\Post;
use App\Models\Product;
use App\Models\Stat;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Reproduces the Vendora template's demo content in the database.
 *
 * The product, payment and coupon data is parsed out of design/vendora/assets/data.js at
 * seed time rather than transcribed, so the visual-parity test compares the rendered pages
 * against the very same numbers the static template was built with. Copying them by hand
 * would guarantee a drift nobody would notice until a screenshot diff failed.
 */
final class DemoParitySeeder extends Seeder
{
    private const DATA_FILE = 'design/vendora/assets/data.js';

    public function run(): void
    {
        $site = $this->readTemplateData();

        $this->seedPlatforms();
        $categories = $this->seedCategories($site['products'] ?? []);
        $this->seedProducts($site['products'] ?? [], $categories);
        $this->seedPaymentMethods($site['payments'] ?? []);
        $this->seedCoupons($site['coupons'] ?? []);
        $this->seedHeroSlides();
        $this->seedContent();
        $this->seedMenus();
    }

    /**
     * Pull `window.SITE = {...};` out of the template's JavaScript.
     *
     * @return array<string, mixed>
     */
    private function readTemplateData(): array
    {
        $path = base_path(self::DATA_FILE);

        if (! is_file($path)) {
            $this->command?->warn('Template data file not found; seeding structure only.');

            return [];
        }

        $source = (string) file_get_contents($path);
        $start = strpos($source, '{');
        $end = strrpos($source, '}');

        if ($start === false || $end === false) {
            return [];
        }

        return (array) json_decode(substr($source, $start, $end - $start + 1), true);
    }

    private function seedPlatforms(): void
    {
        $platforms = [
            ['name' => 'TikTok Business Center', 'icon_key' => 'play', 'invite_instructions' => 'Open business.tiktok.com → Notifications → accept the invitation we sent to your email.'],
            ['name' => 'Meta Business Manager', 'icon_key' => 'shield', 'invite_instructions' => 'Open business.facebook.com → Notifications → accept the partner request sent to your email.'],
            ['name' => 'Google Ads', 'icon_key' => 'key', 'invite_instructions' => 'Open ads.google.com → Tools → Access and security → accept the invitation sent to your email.'],
            ['name' => 'Direct account access', 'icon_key' => 'gift', 'invite_instructions' => 'Check your inbox for the invitation and follow the link inside it.'],
        ];

        foreach ($platforms as $index => $platform) {
            Platform::updateOrCreate(
                ['slug' => Str::slug($platform['name'])],
                $platform + ['sort_order' => $index, 'is_active' => true],
            );
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $products
     * @return array<string, Category>
     */
    private function seedCategories(array $products): array
    {
        // Category order follows first appearance in the template, which is the order the
        // home page sections are laid out in.
        $seen = [];

        foreach ($products as $product) {
            $slug = (string) $product['category'];

            if (! isset($seen[$slug])) {
                $seen[$slug] = [
                    'name' => (string) $product['categoryName'],
                    'gradient' => (string) $product['grad'],
                    'icon_key' => (string) $product['icon'],
                ];
            }
        }

        $descriptions = [
            'streaming' => 'Movies, series, music and anime — shared or private profiles.',
            'software' => 'Genuine retail keys with lifetime activation support.',
            'gaming' => 'Instant game top-ups using only your Player ID. No passwords, no waiting.',
            'giftcards' => 'Store credit delivered to your email in minutes.',
            'vpn' => 'No-logs VPN and device security, multi-device plans.',
            'design' => 'Design suites and AI writing tools on annual plans.',
            'cloud' => 'Cloud storage and backup with sync across devices.',
            'courses' => 'Video courses and eBooks with lifetime access.',
        ];

        $categories = [];
        $order = 0;

        foreach ($seen as $slug => $data) {
            $categories[$slug] = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'short_description' => $descriptions[$slug] ?? null,
                    'icon_key' => $data['icon_key'],
                    'gradient' => $data['gradient'],
                    'sort_order' => $order++,
                    'is_active' => true,
                    'show_on_home' => true,
                    'show_in_nav' => false,
                ],
            );
        }

        return $categories;
    }

    /**
     * @param  array<string, array<string, mixed>>  $products
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(array $products, array $categories): void
    {
        $platforms = Platform::pluck('id', 'slug');
        $order = 0;

        foreach ($products as $slug => $data) {
            $category = $categories[$data['category']] ?? null;

            if ($category === null) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->getKey(),
                    'platform_id' => $this->platformFor((string) $data['category'], $platforms),
                    'name' => (string) $data['name'],
                    'short' => (string) ($data['short'] ?? ''),
                    'description' => '<p>'.e((string) ($data['short'] ?? '')).'</p>',
                    'delivery_info' => '<p>Access is granted to the email you enter at checkout. Accept the invitation from your inbox to activate it.</p>',
                    'icon_key' => (string) $data['icon'],
                    'gradient' => (string) $data['grad'],
                    // The template's prices are whole taka; the ledger is paisa.
                    'price' => ((int) $data['price']) * 100,
                    'regular_price' => isset($data['regular']) ? ((int) $data['regular']) * 100 : null,
                    'stock' => (int) $data['stock'],
                    'min_qty' => (int) $data['min'],
                    'max_qty' => (int) $data['max'],
                    'badge_text' => $data['badge'] ?? null,
                    'badge_style' => $this->badgeStyleFor($data['badge'] ?? null),
                    'is_active' => true,
                    'sort_order' => $order++,
                    'access_role' => 'Member access',
                    'fulfillment_sla_minutes' => 30,
                    'warranty_days' => 7,
                    'customer_instructions' => 'Check your inbox (and spam) for the invitation, then accept it to get access.',
                    'features' => "Access granted to your own email\nWorks on every device you sign in from\n7-day replacement warranty\nReal human support, 9AM – 2AM",
                    'rating' => 49,
                    'reviews_count' => 100 + ((int) $data['stock'] * 7),
                ],
            );

            $product->fields()->delete();

            foreach (($data['fields'] ?? []) as $index => $field) {
                $product->fields()->create([
                    'key' => (string) $field['key'],
                    'label' => (string) $field['label'],
                    'type' => (string) $field['type'],
                    'options' => $field['options'] ?? null,
                    'placeholder' => $field['placeholder'] ?? null,
                    'required' => (bool) ($field['required'] ?? false),
                    'sort_order' => $index,
                ]);
            }
        }
    }

    /** @param Collection<string, int> $platforms */
    private function platformFor(string $categorySlug, $platforms): ?int
    {
        return match ($categorySlug) {
            'gaming' => $platforms['tiktok-business-center'] ?? null,
            'design', 'software' => $platforms['meta-business-manager'] ?? null,
            default => $platforms['direct-account-access'] ?? null,
        };
    }

    private function badgeStyleFor(?string $badge): string
    {
        return match ($badge) {
            'NEW', 'INSTANT', 'HOT' => 'amber',
            null => 'brand',
            default => 'brand',
        };
    }

    /** @param array<int, array<string, mixed>> $payments */
    private function seedPaymentMethods(array $payments): void
    {
        foreach ($payments as $index => $method) {
            $type = PaymentMethodType::from((string) $method['type']);

            PaymentMethod::updateOrCreate(
                ['key' => (string) $method['key']],
                [
                    'name' => (string) $method['name'],
                    'type' => $type,
                    'driver' => $type === PaymentMethodType::Gateway ? 'shurjopay' : null,
                    'chip_color' => (string) $method['color'],
                    'short' => (string) $method['short'],
                    'account_number' => $method['number'] ?? null,
                    'account_type' => $method['account'] ?? null,
                    'instructions' => (string) ($method['instructions'] ?? ''),
                    'min_amount' => $type === PaymentMethodType::Wallet ? 0 : 5000,
                    'available_for_orders' => true,
                    'available_for_deposits' => $type !== PaymentMethodType::Wallet,
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }

    /** @param array<string, array<string, mixed>> $coupons */
    private function seedCoupons(array $coupons): void
    {
        foreach ($coupons as $code => $coupon) {
            $type = CouponType::from((string) $coupon['type']);

            Coupon::updateOrCreate(
                ['code' => $code],
                [
                    'label' => (string) $coupon['label'],
                    'type' => $type,
                    // A percentage stays a percentage; a fixed amount becomes paisa.
                    'value' => $type === CouponType::Percent
                        ? (int) $coupon['value']
                        : ((int) $coupon['value']) * 100,
                    'min_order' => ((int) ($coupon['min'] ?? 0)) * 100,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedHeroSlides(): void
    {
        $slides = [
            ['eyebrow' => 'Weekend deal', 'title' => 'StreamBox Premium from ৳ 349', 'subtitle' => '4K plan · private profile · 30-day replacement', 'icon_key' => 'play', 'gradient' => 'from-amber-300 to-amber-400', 'text_class' => 'text-ink'],
            ['eyebrow' => "Gamers' corner", 'title' => 'Top-up in under 2 minutes', 'subtitle' => 'Just your Player ID — no password, ever', 'icon_key' => 'gamepad', 'gradient' => 'from-white to-brand-50', 'text_class' => 'text-ink'],
            ['eyebrow' => 'Pay your way', 'title' => 'bKash · Nagad · Rocket · Card', 'subtitle' => 'Or pay instantly from your wallet', 'icon_key' => 'wallet', 'gradient' => 'from-stone-900 to-stone-800', 'text_class' => 'text-white'],
        ];

        foreach ($slides as $index => $slide) {
            HeroSlide::updateOrCreate(
                ['title' => $slide['title']],
                $slide + ['button_text' => 'Shop now', 'button_url' => '#categories', 'sort_order' => $index, 'is_active' => true],
            );
        }
    }

    private function seedContent(): void
    {
        $faqs = [
            ['How fast is delivery?', 'Most orders are delivered automatically within 1–5 minutes. Manual payments (bKash/Nagad/Rocket) are verified within 5–15 minutes during support hours.'],
            ['Which payment methods do you accept?', 'bKash, Nagad, Rocket, cards via our online gateway, and your wallet balance for one-click checkout.'],
            ["What if a product doesn't work?", 'Every product is covered by our replacement policy. Open a ticket from your order page or message support and we will replace or refund.'],
            ['Do I need an account to buy?', 'No — you can check out as a guest with just your email. An account gives you a wallet, order history and referral rewards.'],
            ['How does the referral program work?', 'Share your referral link. When friends sign up and buy, you earn wallet credit on each of their first orders.'],
        ];

        foreach ($faqs as $index => [$question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                ['answer' => $answer, 'group' => FaqGroup::General, 'sort_order' => $index, 'is_active' => true],
            );
        }

        $testimonials = [
            ['Sadia Rahman', 'Dhaka · verified purchase', 'Delivered in literally 3 minutes after my bKash payment. The support guy even walked me through activation.'],
            ['Tanvir Ahmed', 'Chattogram · verified purchase', 'I top up diamonds every week — never had a failed order. Wallet payments make it even faster.'],
            ['Nusrat Hossain', 'Sylhet · verified purchase', 'Bought an office key for our small agency. Genuine, activated first try, got an invoice for our records.'],
        ];

        foreach ($testimonials as $index => [$name, $role, $quote]) {
            Testimonial::updateOrCreate(
                ['name' => $name],
                ['role' => $role, 'quote' => $quote, 'rating' => 5, 'sort_order' => $index, 'is_active' => true],
            );
        }

        $pages = [
            ['How to Buy?', 'how-to-buy', PageTemplate::Default],
            ['Our Story', 'our-story', PageTemplate::OurStory],
            ['Contact Us', 'contact-us', PageTemplate::Contact],
            ['Terms & conditions', 'terms', PageTemplate::Default],
            ['Privacy policy', 'privacy', PageTemplate::Default],
            ['Refund policy', 'refund-policy', PageTemplate::Default],
            ['Replacement policy', 'replacement-policy', PageTemplate::Default],
            ['Payment methods', 'payment-methods', PageTemplate::Default],
            ['Delivery time', 'delivery-time', PageTemplate::Default],
            ['Become a reseller', 'become-a-reseller', PageTemplate::Default],
        ];

        foreach ($pages as [$title, $slug, $template]) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'template' => $template,
                    'body' => '<p>Replace this text in the admin panel under Content → Pages.</p>',
                    'is_active' => true,
                    'show_in_footer' => true,
                ],
            );
        }

        foreach ([
            ['25,000+', 'Happy customers'],
            ['120,000+', 'Orders delivered'],
            ['4.9/5', 'Average rating'],
            ['< 5 min', 'Median delivery'],
        ] as $index => [$value, $label]) {
            Stat::updateOrCreate(['label' => $label], ['value' => $value, 'sort_order' => $index, 'is_active' => true]);
        }

        foreach ([
            ['Moshiur Rahman', 'Founder', 'from-rose-500 to-red-700'],
            ['Support Lead', 'Customer success', 'from-amber-500 to-orange-600'],
            ['Fulfilment Team', 'Order delivery', 'from-teal-600 to-emerald-700'],
        ] as $index => [$name, $role, $avatar]) {
            TeamMember::updateOrCreate(
                ['name' => $name],
                ['role' => $role, 'avatar_class' => $avatar, 'sort_order' => $index, 'is_active' => true],
            );
        }

        $blogCategory = BlogCategory::updateOrCreate(
            ['slug' => 'guides'],
            ['name' => 'Guides', 'color_class' => 'bg-brand-50 text-brand-700', 'is_active' => true],
        );

        foreach ([
            ['How to pay with bKash safely', 'Send money to the number shown at checkout, then paste the transaction ID. We verify it within minutes.'],
            ['Getting access to a Business Manager', 'Once we invite your email, open your notifications on the platform and accept the invitation.'],
            ['Why we ask for your delivery email', 'The email you enter at checkout is the account we add to the asset. Getting it right saves everyone time.'],
        ] as $index => [$title, $excerpt]) {
            Post::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'body' => "<p>{$excerpt}</p><p>Replace this article in the admin panel under Content → Posts.</p>",
                    'blog_category_id' => $blogCategory->getKey(),
                    'gradient' => ['from-rose-500 to-red-700', 'from-amber-500 to-orange-600', 'from-teal-600 to-emerald-700'][$index],
                    'is_published' => true,
                    'published_at' => now()->subDays(($index + 1) * 3),
                ],
            );
        }
    }

    private function seedMenus(): void
    {
        $definitions = [
            [MenuLocation::Header, 'Header', null, [
                ['Home', MenuLinkType::Route, 'home'],
                ['How to Buy?', MenuLinkType::Page, 'how-to-buy'],
                ['Our Story', MenuLinkType::Route, 'our-story'],
                ['Blogs', MenuLinkType::Route, 'blog.index'],
                ['Contact Us', MenuLinkType::Route, 'contact'],
            ]],
            [MenuLocation::Mobile, 'Mobile', null, [
                ['Home', MenuLinkType::Route, 'home'],
                ['How to Buy?', MenuLinkType::Page, 'how-to-buy'],
                ['Our Story', MenuLinkType::Route, 'our-story'],
                ['Blogs', MenuLinkType::Route, 'blog.index'],
                ['Contact Us', MenuLinkType::Route, 'contact'],
            ]],
            [MenuLocation::Footer1, 'Footer 1', 'Inquiries', [
                ['Contact us', MenuLinkType::Route, 'contact'],
                ['Become a reseller', MenuLinkType::Page, 'become-a-reseller'],
                ['Bulk orders', MenuLinkType::Route, 'contact'],
                ['Partnerships', MenuLinkType::Route, 'contact'],
            ]],
            [MenuLocation::Footer2, 'Footer 2', 'Help', [
                ['How to buy?', MenuLinkType::Page, 'how-to-buy'],
                ['Payment methods', MenuLinkType::Page, 'payment-methods'],
                ['Delivery time', MenuLinkType::Page, 'delivery-time'],
                ['FAQ', MenuLinkType::Url, '/#faq'],
            ]],
            [MenuLocation::Footer3, 'Footer 3', 'Policies', [
                ['Terms & conditions', MenuLinkType::Page, 'terms'],
                ['Privacy policy', MenuLinkType::Page, 'privacy'],
                ['Refund policy', MenuLinkType::Page, 'refund-policy'],
                ['Replacement policy', MenuLinkType::Page, 'replacement-policy'],
            ]],
            [MenuLocation::Footer4, 'Footer 4', 'Useful links', [
                ['Blogs', MenuLinkType::Route, 'blog.index'],
                ['Our team', MenuLinkType::Route, 'our-story'],
                ['Sign in', MenuLinkType::Route, 'login'],
            ]],
        ];

        foreach ($definitions as [$location, $name, $columnTitle, $items]) {
            $menu = Menu::updateOrCreate(
                ['location' => $location->value],
                ['name' => $name, 'column_title' => $columnTitle, 'is_active' => true],
            );

            $menu->allItems()->delete();

            foreach ($items as $index => [$label, $type, $target]) {
                $menu->allItems()->create([
                    'label' => $label,
                    'link_type' => $type,
                    'link_target_id' => $type === MenuLinkType::Page
                        ? Page::where('slug', $target)->value('id')
                        : null,
                    'url' => in_array($type, [MenuLinkType::Route, MenuLinkType::Url], true) ? $target : null,
                    'sort_order' => $index,
                    'is_active' => true,
                ]);
            }
        }
    }
}
