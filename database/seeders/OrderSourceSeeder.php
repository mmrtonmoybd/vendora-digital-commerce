<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\OrderSource;
use Illuminate\Database\Seeder;

/**
 * The channels an order can be attributed to.
 *
 * Aliases and hosts are data so marketing can add a channel without a deploy; these are
 * the defaults that cover the platforms this shop actually advertises on.
 */
final class OrderSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['key' => 'website', 'name' => 'Website (direct)', 'aliases' => ['website', 'web', 'direct'], 'hosts' => [], 'color' => 'bg-stone-900', 'icon_key' => 'shield', 'is_default' => true],
            ['key' => 'facebook', 'name' => 'Facebook', 'aliases' => ['facebook', 'fb', 'meta'], 'hosts' => ['facebook', 'fb.com', 'messenger'], 'color' => 'bg-sky-600', 'icon_key' => 'shield'],
            ['key' => 'instagram', 'name' => 'Instagram', 'aliases' => ['instagram', 'ig'], 'hosts' => ['instagram'], 'color' => 'bg-pink-600', 'icon_key' => 'pen'],
            ['key' => 'tiktok', 'name' => 'TikTok', 'aliases' => ['tiktok', 'tt'], 'hosts' => ['tiktok'], 'color' => 'bg-stone-900', 'icon_key' => 'play'],
            ['key' => 'google', 'name' => 'Google', 'aliases' => ['google', 'ggl', 'adwords', 'googleads'], 'hosts' => ['google'], 'color' => 'bg-emerald-600', 'icon_key' => 'key'],
            ['key' => 'youtube', 'name' => 'YouTube', 'aliases' => ['youtube', 'yt'], 'hosts' => ['youtube', 'youtu.be'], 'color' => 'bg-brand-700', 'icon_key' => 'play'],
            ['key' => 'whatsapp', 'name' => 'WhatsApp', 'aliases' => ['whatsapp', 'wa'], 'hosts' => ['whatsapp', 'wa.me'], 'color' => 'bg-emerald-600', 'icon_key' => 'shield'],
            ['key' => 'telegram', 'name' => 'Telegram', 'aliases' => ['telegram', 'tg'], 'hosts' => ['t.me', 'telegram'], 'color' => 'bg-sky-600', 'icon_key' => 'shield'],
            ['key' => 'referral', 'name' => 'Affiliate referral', 'aliases' => ['referral', 'affiliate', 'ref'], 'hosts' => [], 'color' => 'bg-violet-700', 'icon_key' => 'gift'],
        ];

        foreach ($sources as $index => $source) {
            OrderSource::updateOrCreate(
                ['key' => $source['key']],
                $source + ['sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
