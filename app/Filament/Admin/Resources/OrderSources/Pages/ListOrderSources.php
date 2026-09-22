<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderSources\Pages;

use App\Filament\Admin\Resources\OrderSources\OrderSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListOrderSources extends ListRecords
{
    protected static string $resource = OrderSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
