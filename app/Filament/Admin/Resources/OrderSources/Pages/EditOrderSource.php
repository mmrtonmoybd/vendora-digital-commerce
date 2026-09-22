<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderSources\Pages;

use App\Filament\Admin\Resources\OrderSources\OrderSourceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditOrderSource extends EditRecord
{
    protected static string $resource = OrderSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
