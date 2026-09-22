<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CannedResponses\Pages;

use App\Filament\Admin\Resources\CannedResponses\CannedResponseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCannedResponses extends ListRecords
{
    protected static string $resource = CannedResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
