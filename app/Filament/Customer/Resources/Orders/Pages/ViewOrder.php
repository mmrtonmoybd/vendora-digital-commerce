<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Orders\Pages;

use App\Filament\Customer\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
}
