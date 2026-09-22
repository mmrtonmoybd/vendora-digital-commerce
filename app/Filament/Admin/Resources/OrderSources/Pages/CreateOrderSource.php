<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderSources\Pages;

use App\Filament\Admin\Resources\OrderSources\OrderSourceResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOrderSource extends CreateRecord
{
    protected static string $resource = OrderSourceResource::class;
}
