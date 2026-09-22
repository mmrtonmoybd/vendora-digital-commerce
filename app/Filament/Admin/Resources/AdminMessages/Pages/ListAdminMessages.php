<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdminMessages\Pages;

use App\Filament\Admin\Resources\AdminMessages\AdminMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAdminMessages extends ListRecords
{
    protected static string $resource = AdminMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Compose')];
    }
}
