<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Admins\Pages;

use App\Filament\Admin\Resources\Admins\AdminResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
