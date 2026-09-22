<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Orders\Support\OrderActions;
use Filament\Resources\Pages\ViewRecord;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return $this->record->order_number;
    }

    protected function getHeaderActions(): array
    {
        return OrderActions::all();
    }
}
