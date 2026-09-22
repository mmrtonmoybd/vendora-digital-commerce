<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Menus\Pages;

use App\Filament\Admin\Resources\Menus\MenuResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;
}
