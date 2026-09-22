<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Enums\PaymentStatus;

final readonly class VerificationResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public PaymentStatus $status,
        public int $amount,
        public string $currency,
        public ?string $code = null,
        public ?string $message = null,
        public ?string $method = null,
        public ?string $bankTrxId = null,
        public ?string $invoiceNo = null,
        public ?string $merchantOrderId = null,
        public array $raw = [],
    ) {}
}
