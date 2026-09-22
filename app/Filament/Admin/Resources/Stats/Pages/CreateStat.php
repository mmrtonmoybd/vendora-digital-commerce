<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Stats\Pages;

use App\Filament\Admin\Resources\Stats\StatResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateStat extends CreateRecord
{
    protected static string $resource = StatResource::class;
}
