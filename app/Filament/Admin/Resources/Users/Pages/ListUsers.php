<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Resources\Users\Widgets\CustomerStats;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /** Figures that describe the rows currently filtered, not the whole table. */
    protected function getHeaderWidgets(): array
    {
        return [CustomerStats::class];
    }
}
