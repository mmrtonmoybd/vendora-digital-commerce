<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\OrderInvoiceController as AdminOrderInvoiceController;
use App\Http\Controllers\Storefront\AuthController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\LocaleController;
use App\Http\Controllers\Storefront\OrderAccessController;
use App\Http\Controllers\Storefront\OrderPayController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PrivateFileController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\SeoController;
use App\Http\Controllers\Storefront\SocialLoginController;
use App\Http\Controllers\Storefront\StorefrontStateController;
use Illuminate\Support\Facades\Route;

/*
 | The storefront. Every path here renders the Vendora template; the panels live on
 | /admin and /customer and are routed by Filament itself.
 */

Route::get('/', HomeController::class)->name('home');

Route::get('/category/{category:slug}', CategoryController::class)->name('category.show');
Route::get('/search', SearchController::class)->name('search');
Route::get('/product/{product:slug}', ProductController::class)->name('product.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');

Route::get('/order/{order:order_number}/pay', [OrderPayController::class, 'show'])->name('order.pay');
Route::get('/order/{order:order_number}/invoice', [OrderPayController::class, 'invoice'])
    ->name('order.invoice');
Route::get('/order/{order:order_number}/invoice/print', [OrderPayController::class, 'invoicePrint'])
    ->name('order.invoice.print');

/*
 | Access-grant delivery. The confirm and problem links are signed so they work from an
 | email for guests too, without a session.
 */
Route::middleware('signed')->group(function (): void {
    Route::get('/access/{item}/confirm', [OrderAccessController::class, 'confirm'])->name('access.confirm');
    Route::get('/access/{item}/problem', [OrderAccessController::class, 'problemForm'])->name('access.problem');
});
Route::post('/access/{item}/problem', [OrderAccessController::class, 'submitProblem'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('access.problem.store');

Route::get('/page/{page:slug}', PageController::class)->name('page.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/our-story', [PageController::class, 'ourStory'])->name('our-story');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

/*
 | Authentication. Fortify owns the actions; these routes render the template's own pages.
 */
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');

    Route::get('/auth/google', [SocialLoginController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialLoginController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
});

/*
 | Private uploads (payment screenshots, ticket attachments) are never served from a public
 | disk. This route checks ownership on every request.
 */
Route::get('/files/{path}', PrivateFileController::class)
    ->where('path', '.*')
    ->middleware(['auth', 'signed'])
    ->name('files.show');

/*
 | Per-visitor state for pages served from the edge cache. See the controller for why the
 | cached HTML deliberately contains none of this.
 */
Route::get('/storefront/state', StorefrontStateController::class)->name('storefront.state');

Route::get('/language/{locale}', LocaleController::class)
    ->where('locale', '[a-z]{2}')
    ->name('locale.switch');

Route::get('/currency/{currency}', [LocaleController::class, 'currency'])
    ->where('currency', '[A-Z]{3}')
    ->name('currency.switch');

/*
 | Staff invoice copies. Separate from the customer routes because the authorisation
 | question is different — see the controller.
 */
Route::middleware('auth:admin')->prefix('admin/order')->name('admin.order.')->group(function (): void {
    Route::get('/{order:order_number}/invoice', [AdminOrderInvoiceController::class, 'download'])->name('invoice');
    Route::get('/{order:order_number}/invoice/print', [AdminOrderInvoiceController::class, 'print'])->name('invoice.print');
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
/*
 | A chunked child sitemap. The unnumbered form is chunk 1, so an existing submission of
 | /sitemap-products.xml keeps working after the catalogue grows past one chunk.
 */
Route::get('/sitemap-{section}-{page}.xml', [SeoController::class, 'sitemapChunk'])
    ->where('section', 'pages|products|categories|posts')
    ->where('page', '[0-9]+')
    ->name('sitemap.chunk');

Route::get('/sitemap-{section}.xml', [SeoController::class, 'sitemapSection'])
    ->where('section', 'pages|products|categories|posts')
    ->name('sitemap.section');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

require __DIR__.'/payments.php';
