<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

final readonly class InitiationResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public bool $ok,
        public ?string $checkoutUrl = null,
        public ?string $gatewayOrderId = null,
        public ?string $message = null,
        public array $raw = [],
    ) {}

    public static function failed(string $message, array $raw = []): self
    {
        return new self(false, null, null, $message, $raw);
    }
}
