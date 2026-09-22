<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Stats\Pages;

use App\Filament\Admin\Resources\Stats\StatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditStat extends EditRecord
{
    protected static string $resource = StatResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
