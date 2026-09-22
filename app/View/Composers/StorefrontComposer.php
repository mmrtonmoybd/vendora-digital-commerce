<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Services\StorefrontData;
use Illuminate\View\View;

/** Puts the shared chrome data on every storefront view without each controller passing it. */
final class StorefrontComposer
{
    public function __construct(private readonly StorefrontData $data) {}

    public function compose(View $view): void
    {
        $view->with('store', $this->data);
    }
}
