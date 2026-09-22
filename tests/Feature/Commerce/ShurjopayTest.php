<?php

declare(strict_types=1);

use App\Enums\DepositStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Payments\Contracts\CustomerDetails;
use App\Services\CheckoutService;
use App\Services\DepositService;
use App\Services\ShurjopayService;
use App\Settings\ShurjopaySettings;
use Illuminate\Support\Facades\Http;

/**
 * shurjoPay, faked at the HTTP boundary.
 *
 * Nothing here trusts a redirect: every assertion is about what the verification endpoint
 * said, which is the only thing the system treats as proof of payment.
 */
beforeEach(function (): void {
    $settings = app(ShurjopaySettings::class);
    $settings->enabled = true;
    $settings->username = 'sp_test';
    $settings->password = 'secret';
    $settings->prefix = 'VD';
    $settings->api_url = 'https://sandbox.shurjopayment.com';
    $settings->save();

    $this->customer = new CustomerDetails(
        name: 'Test Buyer', email: 'buyer@example.com', phone: '01700000000',
        address: 'Dhaka', city: 'Dhaka', postcode: '1000', country: 'Bangladesh', ip: '127.0.0.1',
    );

    $this->gatewayMethod = PaymentMethod::factory()->gateway()->create();
    $this->product = Product::factory()->priced(100_000)->withStock(5)->create();
    $this->user = User::factory()->create();

    $this->shurjopay = app(ShurjopayService::class);

    // Laravel appends stubs rather than replacing them, and the first match wins. So the
    // fake is registered once and reads the payload from the test instance, letting a
    // scenario change what the gateway "says" between calls.
    $this->spOrderId = 'SP123';
    $this->verification = ['sp_code' => '1000'];

    $test = $this;

    Http::fake([
        '*/api/get_token' => Http::response(['token' => 't0ken', 'store_id' => '99']),
        '*/api/secret-pay' => fn () => Http::response([
            'checkout_url' => 'https://gateway.test/pay/'.$test->spOrderId,
            'sp_order_id' => $test->spOrderId,
        ]),
        '*/api/verification' => fn () => Http::response([$test->verification]),
    ]);
});

/** Tell the faked gateway what its verification endpoint should answer next. */
function gatewaySays($test, array $verification, ?string $spOrderId = null): void
{
    $test->verification = $verification;

    if ($spOrderId !== null) {
        $test->spOrderId = $spOrderId;
    }
}

function placeGatewayOrder($test): Order
{
    return app(CheckoutService::class)->placeFromBuyNow(
        product: $test->product,
        quantity: 1,
        fields: [],
        deliveryEmail: 'buyer@example.com',
        method: $test->gatewayMethod,
        couponCode: null,
        user: $test->user,
        customerPhone: '01700000000',
    );
}

it('creates a pending payment and returns a checkout url', function (): void {
    gatewaySays($this, ['sp_code' => '1000']);
    $order = placeGatewayOrder($this);

    $result = $this->shurjopay->start($order, $this->user, $this->customer, $order->total);

    expect($result['checkout_url'])->toBe('https://gateway.test/pay/SP123')
        ->and($result['payment']->status)->toBe(PaymentStatus::Pending)
        ->and($result['payment']->sp_order_id)->toBe('SP123')
        ->and($result['payment']->merchant_order_id)->toStartWith('VD');
});

it('marks the order paid when verification returns 1000 with a matching amount', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $payment = $this->shurjopay->start($order, $this->user, $this->customer, $order->total)['payment'];

    gatewaySays($this, [
        'sp_code' => '1000',
        'received_amount' => '1000.00',
        'currency' => 'BDT',
        'customer_order_id' => $payment->merchant_order_id,
        'method' => 'bKash',
        'bank_trx_id' => 'BT1',
    ]);

    $verified = $this->shurjopay->verifyAndApply('SP123', 'return');

    expect($verified->status)->toBe(PaymentStatus::Paid)
        ->and($verified->method)->toBe('bKash')
        ->and($order->fresh()->payment_status)->toBe(OrderPaymentStatus::Paid);
});

it('settles the order exactly once when return, ipn and reconcile all fire', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $payment = $this->shurjopay->start($order, $this->user, $this->customer, $order->total)['payment'];

    gatewaySays($this, [
        'sp_code' => '1000',
        'received_amount' => '1000.00',
        'currency' => 'BDT',
        'customer_order_id' => $payment->merchant_order_id,
    ]);

    $this->shurjopay->verifyAndApply('SP123', 'return');
    $this->shurjopay->verifyAndApply('SP123', 'ipn');
    $this->shurjopay->verifyAndApply('SP123', 'reconcile');

    $order->refresh();

    expect($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        // One payment confirmation in the history, not three.
        ->and($order->histories()->where('note', 'like', '%Payment confirmed%')->count())->toBe(1);
});

it('refuses to settle when the gateway reports a different amount', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $payment = $this->shurjopay->start($order, $this->user, $this->customer, $order->total)['payment'];

    // The customer paid 10 taka for a 1,000 taka order.
    gatewaySays($this, [
        'sp_code' => '1000',
        'received_amount' => '10.00',
        'currency' => 'BDT',
        'customer_order_id' => $payment->merchant_order_id,
    ]);

    $verified = $this->shurjopay->verifyAndApply('SP123', 'return');

    expect($verified->status)->toBe(PaymentStatus::Failed)
        ->and($verified->sp_message)->toContain('mismatch')
        ->and($order->fresh()->payment_status)->toBe(OrderPaymentStatus::Unpaid);
});

it('refuses to settle when the merchant order id does not match', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $this->shurjopay->start($order, $this->user, $this->customer, $order->total);

    gatewaySays($this, [
        'sp_code' => '1000',
        'received_amount' => '1000.00',
        'currency' => 'BDT',
        'customer_order_id' => 'SOMEONE-ELSES-ORDER',
    ]);

    $verified = $this->shurjopay->verifyAndApply('SP123', 'return');

    expect($verified->status)->toBe(PaymentStatus::Failed)
        ->and($order->fresh()->payment_status)->toBe(OrderPaymentStatus::Unpaid);
});

it('leaves the order unpaid when the gateway declines', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $this->shurjopay->start($order, $this->user, $this->customer, $order->total);

    gatewaySays($this, ['sp_code' => '1001', 'sp_message' => 'Declined by issuer']);

    $verified = $this->shurjopay->verifyAndApply('SP123', 'return');

    expect($verified->status)->toBe(PaymentStatus::Failed)
        ->and($order->fresh()->payment_status)->toBe(OrderPaymentStatus::Unpaid);
});

it('keeps an unknown status pending so reconcile can ask again', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $this->shurjopay->start($order, $this->user, $this->customer, $order->total);

    gatewaySays($this, ['sp_code' => '9999', 'sp_message' => 'Who knows']);

    $verified = $this->shurjopay->verifyAndApply('SP123', 'return');

    expect($verified->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->payment_status)->toBe(OrderPaymentStatus::Unpaid);
});

it('credits a wallet deposit exactly once through the gateway', function (): void {
    $method = PaymentMethod::factory()->gateway()->create(['key' => 'card-dep']);

    $deposit = app(DepositService::class)->createGateway($this->user, $method, 50_000);

    gatewaySays($this, ['sp_code' => '1000'], 'SPDEP');
    $payment = $this->shurjopay->start($deposit, $this->user, $this->customer, $deposit->payable)['payment'];

    gatewaySays($this, [
        'sp_code' => '1000',
        'received_amount' => '500.00',
        'currency' => 'BDT',
        'customer_order_id' => $payment->merchant_order_id,
    ], 'SPDEP');

    $this->shurjopay->verifyAndApply('SPDEP', 'return');
    $this->shurjopay->verifyAndApply('SPDEP', 'ipn');

    expect($this->user->fresh()->balance)->toBe(50_000)
        ->and($deposit->fresh()->status)->toBe(DepositStatus::Completed)
        ->and(WalletTransaction::where('user_id', $this->user->id)->count())->toBe(1);
});

it('records every callback it receives', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $this->shurjopay->start($order, $this->user, $this->customer, $order->total);

    gatewaySays($this, ['sp_code' => '1001']);
    $this->shurjopay->verifyAndApply('SP123', 'return');
    $this->shurjopay->verifyAndApply('SP123', 'ipn');

    $payment = Payment::where('sp_order_id', 'SP123')->first();

    // One event for the initiation plus one per callback: the audit trail for a dispute.
    expect($payment->events()->count())->toBe(3)
        ->and($payment->events()->pluck('source')->all())->toContain('return', 'ipn', 'initiate');
});

it('never writes the gateway password into a stored payload', function (): void {
    $order = placeGatewayOrder($this);

    gatewaySays($this, ['sp_code' => '1000']);
    $payment = $this->shurjopay->start($order, $this->user, $this->customer, $order->total)['payment'];

    $stored = json_encode([$payment->fresh()->request_payload, $payment->fresh()->verify_response]);

    expect($stored)->not->toContain('secret');
});
