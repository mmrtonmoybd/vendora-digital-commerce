<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

final readonly class CustomerDetails
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $address,
        public string $city,
        public string $postcode,
        public string $country,
        public string $ip,
    ) {}
}
