<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Stats\Pages;

use App\Filament\Admin\Resources\Stats\StatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListStats extends ListRecords
{
    protected static string $resource = StatResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
