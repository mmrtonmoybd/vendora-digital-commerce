<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoActivitySeeder;
use Database\Seeders\DemoParitySeeder;
use Database\Seeders\OrderSourceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Every public page, rendered against a shop that actually has stock in it.
 *
 * Two things this catches that an empty database cannot. First, lazy loading is disabled
 * outside production, so any relation a view touches without it having been eager-loaded
 * throws here rather than becoming a silent query-per-row in production. Second, the query
 * counts below turn an N+1 into a failing assertion instead of a slow page nobody profiles.
 */
beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OrderSourceSeeder::class);
    $this->seed(DemoParitySeeder::class);
    $this->seed(DemoActivitySeeder::class);
});

/** @return array{status: int, queries: int} */
function measure($test, string $url): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $test->get($url);

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    return ['status' => $response->getStatusCode(), 'queries' => $queries];
}

it('renders every public page without a lazy-load violation', function (): void {
    $product = Product::query()->where('is_active', true)->firstOrFail();
    $category = Category::query()->where('is_active', true)->firstOrFail();
    $post = Post::query()->where('is_published', true)->first();

    $urls = [
        '/',
        '/cart',
        '/contact',
        '/blog',
        '/our-story',
        '/search?q=a',
        '/login',
        '/register',
        '/product/'.$product->slug,
        '/category/'.$category->slug,
    ];

    if ($post !== null) {
        $urls[] = '/blog/'.$post->slug;
    }

    $failures = [];

    foreach ($urls as $url) {
        $result = measure($this, $url);

        if ($result['status'] !== 200) {
            $failures[] = $url.' → '.$result['status'];
        }
    }

    expect($failures)->toBe([]);
});

/**
 * A budget, not a target.
 *
 * The numbers are deliberately loose — this is here to catch a page that starts issuing one
 * query per product, not to police an exact count. A category page listing twenty products
 * should not be anywhere near twenty queries.
 */
it('does not issue a query per row on the listing pages', function (): void {
    $category = Category::query()->where('is_active', true)->firstOrFail();

    $home = measure($this, '/');
    $listing = measure($this, '/category/'.$category->slug);
    $search = measure($this, '/search?q=a');

    // Measured: home 29, category 17, search 13 on the demo data. The headroom is for
    // fixture drift, not for a new query per row.
    expect($home['queries'])->toBeLessThan(34)
        ->and($listing['queries'])->toBeLessThan(22)
        ->and($search['queries'])->toBeLessThan(18);
});

it('renders a paid order page without a lazy-load violation', function (): void {
    $order = Order::query()->whereNotNull('paid_at')->with('user')->firstOrFail();

    $this->actingAs($order->user)
        ->get('/order/'.$order->order_number.'/pay')
        ->assertOk();
});

/**
 * The account chrome must not use x-cloak to express who the visitor is.
 *
 * Alpine ships an x-cloak directive that strips the attribute as soon as it initialises an
 * element. The account block is an Alpine root — it owns the dropdown — so Alpine removed
 * the cloak for signed-out visitors too, and the header showed "Sign in" and "My account"
 * side by side. Visibility is decided by html[data-auth] and CSS instead.
 */
it('does not hide the account chrome with x-cloak', function (): void {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('data-guest-only')
        ->and($html)->toContain('data-auth-only')
        ->and($html)->not->toMatch('/data-(guest|auth)-only[^>]*x-cloak/')
        ->and($html)->not->toMatch('/x-cloak[^>]*data-(guest|auth)-only/');
});

it('tells the browser the visitor is signed out', function (): void {
    $this->getJson('/storefront/state')
        ->assertOk()
        ->assertJson(['authenticated' => false, 'user' => null]);
});

it('tells the browser the visitor is signed in, and who', function (): void {
    $customer = User::query()->firstOrFail();

    $this->actingAs($customer)
        ->getJson('/storefront/state')
        ->assertOk()
        ->assertJson(['authenticated' => true])
        ->assertJsonPath('user.email', $customer->email);
});
