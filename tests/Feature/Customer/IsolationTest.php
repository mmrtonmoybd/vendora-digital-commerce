<?php

declare(strict_types=1);

use App\Enums\DepositStatus;
use App\Enums\InvoiceStatus;
use App\Enums\UserStatus;
use App\Enums\WalletTxType;
use App\Exceptions\DomainException;
use App\Filament\Customer\Concerns\BelongsToCustomer;
use App\Filament\Customer\Resources\Deposits\DepositResource;
use App\Filament\Customer\Resources\Invoices\InvoiceResource;
use App\Filament\Customer\Resources\Orders\OrderResource;
use App\Filament\Customer\Resources\Tickets\TicketResource;
use App\Filament\Customer\Resources\WalletTransactions\WalletTransactionResource;
use App\Models\CustomInvoice;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\TicketService;
use App\Services\WalletService;
use Filament\Facades\Filament;

/**
 * One customer must never reach another's records.
 *
 * Checked at the query level and through real HTTP, because a policy that is never
 * consulted protects nothing.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel('customer');

    $this->alice = User::factory()->create(['email' => 'alice@example.com']);
    $this->bob = User::factory()->create(['email' => 'bob@example.com']);

    $this->method = PaymentMethod::factory()->create();
    $this->product = Product::factory()->priced(10_000)->withStock(10)->create();

    $this->orderFor = fn (User $user) => app(CheckoutService::class)->placeFromBuyNow(
        product: $this->product,
        quantity: 1,
        fields: [],
        deliveryEmail: $user->email,
        method: $this->method,
        couponCode: null,
        user: $user,
    );
});

it('shows a customer only their own orders', function (): void {
    $aliceOrder = ($this->orderFor)($this->alice);
    $bobOrder = ($this->orderFor)($this->bob);

    $this->actingAs($this->alice);

    $visible = OrderResource::getEloquentQuery()->pluck('id')->all();

    expect($visible)->toContain($aliceOrder->id)
        ->and($visible)->not->toContain($bobOrder->id);

    // And through a real request, so the scope is proven where it actually runs.
    $this->get(OrderResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee($aliceOrder->order_number)
        ->assertDontSee($bobOrder->order_number);
});

it('refuses to open another customer\'s order', function (): void {
    $bobOrder = ($this->orderFor)($this->bob);

    $this->actingAs($this->alice)
        ->get(OrderResource::getUrl('view', ['record' => $bobOrder]))
        ->assertNotFound();
});

it('shows a customer only their own wallet ledger', function (): void {
    app(WalletService::class)->credit($this->alice, 10_000, WalletTxType::Deposit, null, 'a');
    app(WalletService::class)->credit($this->bob, 90_000, WalletTxType::Deposit, null, 'b');

    $this->actingAs($this->alice);

    $rows = WalletTransactionResource::getEloquentQuery()->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->user_id)->toBe($this->alice->id)
        ->and($rows->pluck('amount')->all())->not->toContain(90_000);
});

it('shows a customer only their own deposits', function (): void {
    $bobDeposit = Deposit::create([
        'deposit_number' => 'DP-B', 'user_id' => $this->bob->id,
        'payment_method_id' => $this->method->id, 'amount' => 5_000,
        'fee' => 0, 'payable' => 5_000, 'status' => DepositStatus::Pending,
    ]);

    $this->actingAs($this->alice);

    expect(DepositResource::getEloquentQuery()->pluck('id')->all())
        ->not->toContain($bobDeposit->id);
});

it('refuses to open another customer\'s ticket', function (): void {
    $bobTicket = app(TicketService::class)->open($this->bob, null, 'Bob needs help', 'Private details here.');

    $this->actingAs($this->alice)
        ->get(TicketResource::getUrl('view', ['record' => $bobTicket]))
        ->assertNotFound();
});

it('refuses to open another customer\'s invoice list entry', function (): void {
    $bobInvoice = CustomInvoice::create([
        'invoice_number' => 'INV-B', 'user_id' => $this->bob->id,
        'product_name' => 'Private work', 'amount' => 20_000,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $this->actingAs($this->alice);

    expect(InvoiceResource::getEloquentQuery()->pluck('id')->all())
        ->not->toContain($bobInvoice->id);
});

it('will not attach another customer\'s order to a ticket', function (): void {
    $bobOrder = ($this->orderFor)($this->bob);

    expect(fn () => app(TicketService::class)->open(
        $this->alice,
        $bobOrder,
        'Prying',
        'Let me see this order.',
    ))->toThrow(DomainException::class, 'does not belong to you');
});

it('keeps the panel closed to a guest', function (): void {
    $this->get('/customer')->assertRedirect();
});

it('keeps the panel closed to a suspended customer', function (): void {
    $this->alice->update(['status' => UserStatus::Suspended]);

    expect($this->alice->canAccessPanel(Filament::getPanel('customer')))->toBeFalse();
});

it('keeps the panel closed to an unverified customer', function (): void {
    $unverified = User::factory()->unverified()->create();

    expect($unverified->canAccessPanel(Filament::getPanel('customer')))->toBeFalse();
});

/**
 * Adding eager loading to a customer resource must not drop the ownership scope.
 *
 * A resource that defines its own getEloquentQuery() replaces the one in
 * BelongsToCustomer, scoping and all. That happened once while adding a with() for
 * performance, and every customer could read every order. Eager loading now goes through
 * eagerLoad(); this fails if anyone reaches for the other method again.
 */
it('never lets a customer resource replace the ownership scope', function (): void {
    $offenders = [];

    foreach (Filament::getPanel('customer')->getResources() as $resource) {
        if (! in_array(BelongsToCustomer::class, class_uses_recursive($resource), true)) {
            continue;
        }

        // Reflection is no help here: PHP flattens a trait's methods into the using class,
        // so getDeclaringClass() names the resource either way. The question is whether the
        // resource's own file defines it.
        $source = (string) file_get_contents((new ReflectionClass($resource))->getFileName());

        if (str_contains($source, 'function getEloquentQuery')) {
            $offenders[] = class_basename($resource);
        }
    }

    expect($offenders)->toBe([]);
});
