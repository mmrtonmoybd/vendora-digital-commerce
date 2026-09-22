<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Orders\Pages;

use App\Filament\Customer\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
