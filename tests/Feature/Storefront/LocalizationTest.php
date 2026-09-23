<?php

declare(strict_types=1);

use App\Http\Middleware\SetLiteSpeedVaryCookie;
use App\Models\ContentTranslation;
use App\Models\Product;
use App\Models\User;
use App\Services\ContentTranslator;
use App\Services\StorefrontData;
use App\Settings\HomepageSettings;
use App\Settings\LocaleSettings;
use App\Support\Locales;
use Illuminate\Contracts\Encryption\DecryptException;

function setLocales(array $enabled, string $default = 'en', bool $autoDetect = true): void
{
    $settings = app(LocaleSettings::class);
    $settings->enabled_locales = $enabled;
    $settings->default_locale = $default;
    $settings->auto_detect = $autoDetect;
    $settings->show_switcher = true;
    $settings->save();
}

beforeEach(function (): void {
    setLocales(['en', 'bn']);
    app(ContentTranslator::class)->flush();
});

it('answers in the language the browser asks for', function (): void {
    $this->get('/', ['Accept-Language' => 'bn-BD,bn;q=0.9,en;q=0.5'])
        ->assertOk()
        ->assertSee(__('store.my_cart', [], 'bn'), escape: false);
});

it('answers in English for an English browser', function (): void {
    $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])
        ->assertOk()
        ->assertSee('My Cart');
});

it('re-reads the browser on every request until the visitor actually chooses', function (): void {
    // Detection is not remembered, so a change of default language reaches everyone who has
    // never picked one — and nobody is silently pinned to a guess.
    $this->get('/', ['Accept-Language' => 'bn-BD,bn'])->assertOk();

    $this->get('/', ['Accept-Language' => 'en-GB,en'])
        ->assertOk()
        ->assertSee('My Cart');
});

it('ignores a browser asking for a language that is switched off', function (): void {
    setLocales(['en'], 'en');

    $this->get('/', ['Accept-Language' => 'bn-BD,bn;q=0.9'])
        ->assertOk()
        ->assertSee('My Cart')
        ->assertDontSee(__('store.my_cart', [], 'bn'), escape: false);
});

it('leaves detection alone when the administrator turns it off', function (): void {
    setLocales(['en', 'bn'], 'en', autoDetect: false);

    $this->get('/', ['Accept-Language' => 'bn-BD,bn;q=0.9'])
        ->assertOk()
        ->assertSee('My Cart');
});

it('honours the quality values in an Accept-Language header', function (): void {
    expect(Locales::fromAcceptLanguage('en;q=0.2,bn;q=0.9'))->toBe('bn')
        ->and(Locales::fromAcceptLanguage('bn;q=0.2,en;q=0.9'))->toBe('en')
        ->and(Locales::fromAcceptLanguage('fr-FR,fr;q=0.9'))->toBeNull()
        ->and(Locales::fromAcceptLanguage(null))->toBeNull();
});

it('remembers a deliberate switch for the rest of the session', function (): void {
    $this->get(route('locale.switch', 'bn'))->assertRedirect();

    $this->get('/', ['Accept-Language' => 'en-GB,en'])
        ->assertOk()
        ->assertSee(__('store.my_cart', [], 'bn'), escape: false);
});

it('writes a signed-in customer\'s choice to their account', function (): void {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get(route('locale.switch', 'bn'))->assertRedirect();

    expect($user->fresh()->locale)->toBe('bn');
});

it('refuses to switch to a language that is not enabled', function (): void {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get(route('locale.switch', 'fr'))->assertRedirect();

    expect($user->fresh()->locale)->toBeNull();
});

it('will not bounce a visitor to another site from the switcher', function (): void {
    $this->get(route('locale.switch', 'bn'), ['referer' => 'https://evil.example.com/pay'])
        ->assertRedirect(route('home'));
});

it('stops honouring a stored choice once the language is switched off', function (): void {
    $user = User::factory()->create(['locale' => 'bn']);
    setLocales(['en'], 'en');

    $this->actingAs($user)->get('/')->assertOk()->assertSee('My Cart');
});

it('splits the edge cache by language so one reader never gets the other\'s page', function (): void {
    $cookie = SetLiteSpeedVaryCookie::COOKIE;

    // decrypt: false — the vary cookie is deliberately not encrypted; see the test below.
    $enValue = $this->get('/', ['Accept-Language' => 'en-GB,en'])->getCookie($cookie, false)?->getValue();
    $bnValue = $this->get('/', ['Accept-Language' => 'bn-BD,bn'])->getCookie($cookie, false)?->getValue();

    expect($enValue)->toBe('guest:en')
        ->and($bnValue)->toBe('guest:bn');
});

it('leaves the vary cookie unencrypted so the edge cache can actually vary on it', function (): void {
    // Laravel encrypts with a random IV, so an encrypted vary cookie would hold a different
    // value for every visitor — giving each of them a private cache entry and no hits at
    // all. Decrypting it must therefore fail.
    $cookie = SetLiteSpeedVaryCookie::COOKIE;
    $response = $this->get('/', ['Accept-Language' => 'bn-BD,bn']);

    expect(fn () => $response->getCookie($cookie))
        ->toThrow(DecryptException::class);
});

it('refuses to cache a page rendered before the language cookie exists', function (): void {
    config(['storefront-cache.enabled' => true]);

    // This copy was chosen from Accept-Language. Storing it under the no-cookie variant
    // would hand a Bengali page to the next English visitor.
    $this->get('/', ['Accept-Language' => 'bn-BD,bn'])
        ->assertHeader('x-litespeed-cache-control', 'no-cache');
});

it('caches a cookie-less request when detection is off, so crawlers get hits too', function (): void {
    config(['storefront-cache.enabled' => true]);
    setLocales(['en', 'bn'], 'en', autoDetect: false);

    // Search engines never send cookies. With detection off the cookie-less variant is
    // always the default language, so there is nothing to poison and it can be cached.
    $response = $this->get('/', ['Accept-Language' => 'bn-BD,bn']);

    expect($response->headers->get('x-litespeed-cache-control'))->toStartWith('public,max-age=');
    $response->assertSee('My Cart');
});

it('caches per language once the visitor carries the vary cookie', function (): void {
    config(['storefront-cache.enabled' => true]);

    $cookie = SetLiteSpeedVaryCookie::COOKIE;

    $response = $this->withUnencryptedCookie($cookie, 'guest:bn')
        ->get('/', ['Accept-Language' => 'bn-BD,bn']);

    expect($response->headers->get('x-litespeed-cache-control'))->toStartWith('public,max-age=')
        ->and($response->headers->get('x-litespeed-vary'))->toBe('cookie='.$cookie);
});

it('falls back to the original wording when nothing has been translated', function (): void {
    $product = Product::factory()->create(['name' => 'StreamBox Premium']);

    app()->setLocale('bn');

    expect($product->t('name'))->toBe('StreamBox Premium');
});

it('uses the translated wording once an administrator writes one', function (): void {
    $product = Product::factory()->create(['name' => 'StreamBox Premium']);

    app(ContentTranslator::class)->put(Product::class, $product->getKey(), 'name', 'bn', 'স্ট্রিমবক্স প্রিমিয়াম');

    app()->setLocale('bn');
    expect($product->t('name'))->toBe('স্ট্রিমবক্স প্রিমিয়াম');

    app()->setLocale('en');
    expect($product->t('name'))->toBe('StreamBox Premium');
});

it('drops the row rather than storing an empty translation', function (): void {
    $product = Product::factory()->create(['name' => 'StreamBox Premium']);
    $translator = app(ContentTranslator::class);

    $translator->put(Product::class, $product->getKey(), 'name', 'bn', 'স্ট্রিমবক্স');
    $translator->put(Product::class, $product->getKey(), 'name', 'bn', '   ');

    app()->setLocale('bn');

    expect(ContentTranslation::count())->toBe(0)
        ->and($product->t('name'))->toBe('StreamBox Premium');
});

it('never translates a field the model has not declared', function (): void {
    $product = Product::factory()->create(['slug' => 'streambox-premium']);

    app(ContentTranslator::class)->put(Product::class, $product->getKey(), 'slug', 'bn', 'স্ট্রিমবক্স');

    app()->setLocale('bn');

    // A translated slug would mean a second URL for the same product, and a 404 from every
    // link already in the wild.
    expect($product->t('slug'))->toBe('streambox-premium');
});

it('translates the copy an administrator typed into settings', function (): void {
    app(ContentTranslator::class)->put(
        ContentTranslation::SETTINGS_TYPE, 'homepage', 'hero_badge', 'bn', 'বিশ্বস্ত',
    );

    $store = app(StorefrontData::class);
    $homepage = app(HomepageSettings::class);

    app()->setLocale('bn');
    expect($store->st($homepage, 'hero_badge'))->toBe('বিশ্বস্ত');

    app()->setLocale('en');
    expect($store->st($homepage, 'hero_badge'))->toBe($homepage->hero_badge);
});

it('hides the switcher when there is only one language', function (): void {
    setLocales(['en'], 'en');

    expect(Locales::showsSwitcher())->toBeFalse()
        ->and(Locales::isMultilingual())->toBeFalse();

    $this->get('/')->assertOk()->assertDontSee(route('locale.switch', 'bn'));
});

it('keeps the default language available even if it is left out of the enabled list', function (): void {
    setLocales(['bn'], 'en');

    expect(Locales::enabled())->toContain('en');
});
