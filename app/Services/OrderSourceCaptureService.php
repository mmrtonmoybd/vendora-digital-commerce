<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Collection;

/**
 * Works out which platform sent this visitor, and remembers it until they buy.
 *
 * Detection is deliberately ordered from most to least reliable: an explicit ?utm_source
 * beats a click id, a click id beats the referrer header, and the referrer beats nothing.
 * The result is stored in the session for this visit and in a 30-day cookie so a customer
 * who comes back a week later is still credited to the ad that found them.
 */
final class OrderSourceCaptureService
{
    private const COOKIE_LAST = 'vd_src';

    private const COOKIE_FIRST = 'vd_src_first';

    private const COOKIE_DAYS = 30;

    /** Campaign parameters worth keeping on the order. */
    public const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    /** Ad-click identifiers the platforms use to match a conversion. */
    public const CLICK_KEYS = ['fbclid', 'ttclid', 'gclid', 'gbraid', 'wbraid', 'msclkid'];

    public function capture(Request $request): ?OrderSource
    {
        $source = $this->detect($request);
        $attribution = $this->readAttribution($request);

        if ($attribution !== []) {
            session(['attribution' => array_merge((array) session('attribution', []), $attribution)]);
        }

        if ($source === null) {
            return null;
        }

        session(['order_source_id' => $source->getKey(), 'order_source_key' => $source->key]);

        Cookie::queue(self::COOKIE_LAST, $source->key, 60 * 24 * self::COOKIE_DAYS);

        // First touch is written once and never overwritten.
        if (! $request->cookies->has(self::COOKIE_FIRST)) {
            Cookie::queue(self::COOKIE_FIRST, $source->key, 60 * 24 * 365);
            session(['first_touch_source_id' => $source->getKey()]);
        }

        return $source;
    }

    public function detect(Request $request): ?OrderSource
    {
        $utm = $this->normalise((string) $request->input('utm_source', ''));

        if ($utm !== '' && ($match = $this->matchAlias($utm)) !== null) {
            return $match;
        }

        foreach (['fbclid' => 'facebook', 'ttclid' => 'tiktok'] as $param => $key) {
            if (filled($request->input($param))) {
                return $this->byKey($key);
            }
        }

        foreach (['gclid', 'gbraid', 'wbraid'] as $param) {
            if (filled($request->input($param))) {
                return $this->byKey('google');
            }
        }

        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (is_string($host) && $host !== '' && ! str_contains($host, (string) parse_url(config('app.url'), PHP_URL_HOST))) {
            return $this->matchHost($this->normalise($host));
        }

        return null;
    }

    /** The source to stamp on an order being placed right now. */
    public function resolveForOrder(): ?OrderSource
    {
        $id = session('order_source_id');

        if (is_numeric($id)) {
            return $this->sources()->firstWhere('id', (int) $id);
        }

        $cookieKey = (string) request()->cookie(self::COOKIE_LAST, '');

        if ($cookieKey !== '') {
            return $this->byKey($cookieKey);
        }

        return $this->sources()->firstWhere('is_default', true);
    }

    public function resolveFirstTouch(): ?OrderSource
    {
        $id = session('first_touch_source_id');

        if (is_numeric($id)) {
            return $this->sources()->firstWhere('id', (int) $id);
        }

        $cookieKey = (string) request()->cookie(self::COOKIE_FIRST, '');

        return $cookieKey !== '' ? $this->byKey($cookieKey) : null;
    }

    /**
     * The campaign columns to write onto an order.
     *
     * @return array<string, string|null>
     */
    public function orderAttributes(): array
    {
        $stored = (array) session('attribution', []);
        $source = $this->resolveForOrder();
        $first = $this->resolveFirstTouch();

        $attributes = [
            'order_source_id' => $source?->getKey(),
            'first_touch_source_id' => $first?->getKey() ?? $source?->getKey(),
            'referrer' => $stored['referrer'] ?? null,
            'landing_url' => $stored['landing_url'] ?? null,
            'fbp' => request()->cookie('_fbp'),
            'fbc' => request()->cookie('_fbc'),
        ];

        foreach ([...self::UTM_KEYS, 'fbclid', 'ttclid', 'gclid'] as $key) {
            $attributes[$key] = $stored[$key] ?? null;
        }

        return $attributes;
    }

    /**
     * Campaign data worth carrying through the visit.
     *
     * @return array<string, string>
     */
    private function readAttribution(Request $request): array
    {
        $data = [];

        foreach ([...self::UTM_KEYS, ...self::CLICK_KEYS] as $key) {
            $value = $request->input($key);

            if (filled($value)) {
                $data[$key] = mb_substr((string) $value, 0, 255);
            }
        }

        if ($data !== [] || ! session()->has('attribution')) {
            $referer = (string) $request->headers->get('referer', '');

            if ($referer !== '') {
                $data['referrer'] = mb_substr($referer, 0, 2000);
            }

            $data['landing_url'] = mb_substr($request->fullUrl(), 0, 2000);
        }

        return $data;
    }

    private function matchAlias(string $value): ?OrderSource
    {
        return $this->sources()->first(function (OrderSource $source) use ($value): bool {
            foreach ((array) $source->aliases as $alias) {
                $alias = $this->normalise((string) $alias);

                if ($alias !== '' && ($value === $alias || str_starts_with($value, $alias))) {
                    return true;
                }
            }

            return $this->normalise($source->key) === $value;
        });
    }

    private function matchHost(string $host): ?OrderSource
    {
        return $this->sources()->first(function (OrderSource $source) use ($host): bool {
            foreach ((array) $source->hosts as $pattern) {
                $pattern = $this->normalise((string) $pattern);

                if ($pattern !== '' && str_contains($host, $pattern)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function byKey(string $key): ?OrderSource
    {
        return $this->sources()->firstWhere('key', $key);
    }

    /** @return Collection<int, OrderSource> */
    private function sources(): Collection
    {
        return Cache::remember(
            'order-sources:active',
            now()->addMinutes(30),
            fn (): Collection => OrderSource::active()->get(),
        );
    }

    private function normalise(string $value): string
    {
        return str_replace([' ', '-', '_', '.'], '', mb_strtolower(trim($value)));
    }
}
