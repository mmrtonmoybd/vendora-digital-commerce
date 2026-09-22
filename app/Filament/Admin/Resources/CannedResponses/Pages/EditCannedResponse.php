<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CannedResponses\Pages;

use App\Filament\Admin\Resources\CannedResponses\CannedResponseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCannedResponse extends EditRecord
{
    protected static string $resource = CannedResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
