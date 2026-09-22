<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Models\Payment;

interface PaymentGateway
{
    public function name(): string;

    /** Ask the gateway for a checkout URL for this payment attempt. */
    public function initiate(Payment $payment, CustomerDetails $customer): InitiationResult;

    /** Ask the gateway what actually happened. This is the only source of truth. */
    public function verify(string $gatewayOrderId): VerificationResult;
}
